<?php

namespace App\Livewire;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public string $period = 'this_month';

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function dateRange(): array
    {
        return match ($this->period) {
            'this_week' => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'this_month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'last_month' => ['start' => now()->subMonth()->startOfMonth(), 'end' => now()->subMonth()->endOfMonth()],
            'this_quarter' => ['start' => now()->firstOfQuarter(), 'end' => now()->lastOfQuarter()->endOfDay()],
            'this_year' => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            'all_time' => ['start' => Carbon::createFromDate(2000, 1, 1), 'end' => now()->endOfDay()],
            default => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
        };
    }

    /**
     * Aggregate stats + status breakdown in a single query.
     */
    #[Computed]
    public function stats(): array
    {
        $range = $this->dateRange();
        $organization = auth()->user()->currentTeam;

        if (! $organization) {
            return $this->emptyStats();
        }

        // Single query for all KPI stats
        $result = Invoice::invoices()
            ->whereBetween('issued_at', [$range['start'], $range['end']])
            ->selectRaw('
                count(*) as invoice_count,
                coalesce(sum(total), 0) as total_revenue,
                coalesce(sum(amount_paid), 0) as total_collected,
                count(case when status = ? then 1 end) as paid_count
            ', [InvoiceStatus::PAID->value])
            ->first();

        $overdueCount = Invoice::invoices()->overdue()->count();

        $totalRevenue = (int) $result->total_revenue;
        $totalCollected = (int) $result->total_collected;

        return [
            'total_revenue' => $totalRevenue,
            'total_collected' => $totalCollected,
            'total_outstanding' => $totalRevenue - $totalCollected,
            'invoice_count' => (int) $result->invoice_count,
            'paid_count' => (int) $result->paid_count,
            'overdue_count' => $overdueCount,
            'collection_rate' => $totalRevenue > 0 ? round(($totalCollected / $totalRevenue) * 100, 1) : 0,
            'currency' => $organization->currency,
        ];
    }

    #[Computed]
    public function statusBreakdown(): array
    {
        $range = $this->dateRange();

        $counts = Invoice::invoices()
            ->whereBetween('issued_at', [$range['start'], $range['end']])
            ->selectRaw('status, count(*) as count, coalesce(sum(total), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $statuses = [
            InvoiceStatus::DRAFT,
            InvoiceStatus::SENT,
            InvoiceStatus::ACCEPTED,
            InvoiceStatus::PARTIALLY_PAID,
            InvoiceStatus::PAID,
            InvoiceStatus::VOID,
        ];

        return collect($statuses)->map(function ($status) use ($counts) {
            $data = $counts->get($status->value);

            return [
                'status' => $status,
                'count' => $data?->count ?? 0,
                'total' => $data?->total_amount ?? 0,
            ];
        })->toArray();
    }

    #[Computed]
    public function recentInvoices()
    {
        return Invoice::invoices()
            ->with(['customer', 'organizationLocation.locatable'])
            ->latest('issued_at')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function overdueInvoices()
    {
        return Invoice::invoices()
            ->overdue()
            ->with(['customer'])
            ->orderBy('due_at')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentPayments()
    {
        $orgId = auth()->user()->currentTeam?->id;

        if (! $orgId) {
            return collect();
        }

        return Payment::whereHas('invoice', fn ($q) => $q->withoutGlobalScopes()->where('organization_id', $orgId))
            ->with(['invoice.customer'])
            ->latest('payment_date')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function topCustomers(): array
    {
        $range = $this->dateRange();

        return Invoice::invoices()
            ->whereBetween('issued_at', [$range['start'], $range['end']])
            ->selectRaw('customer_id, count(*) as invoice_count, coalesce(sum(total), 0) as total_amount, coalesce(sum(amount_paid), 0) as total_paid')
            ->groupBy('customer_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->with('customer')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->customer?->name ?? 'Unknown',
                'invoice_count' => $row->invoice_count,
                'total' => $row->total_amount,
                'paid' => $row->total_paid,
                'outstanding' => $row->total_amount - $row->total_paid,
            ])
            ->toArray();
    }

    /**
     * 6-month trend in a single query using date grouping.
     */
    #[Computed]
    public function monthlyTrend(): array
    {
        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();

        // Single query grouped by month
        $driver = DB::getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', issued_at)"
            : "to_char(issued_at, 'YYYY-MM')";

        $rows = Invoice::invoices()
            ->whereBetween('issued_at', [$start, $end])
            ->selectRaw("{$monthExpr} as month_key, coalesce(sum(total), 0) as invoiced, coalesce(sum(amount_paid), 0) as collected")
            ->groupByRaw($monthExpr)
            ->get()
            ->keyBy('month_key');

        // Build result with all 6 months (fill zeros for empty months)
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');

            $row = $rows->get($key);

            $months[] = [
                'label' => $date->format('M Y'),
                'short' => $date->format('M'),
                'invoiced' => (int) ($row?->invoiced ?? 0),
                'collected' => (int) ($row?->collected ?? 0),
            ];
        }

        return $months;
    }

    #[Computed]
    public function customerCount(): int
    {
        return Customer::count();
    }

    /**
     * Estimate stats in a single query.
     */
    #[Computed]
    public function estimateStats(): array
    {
        $range = $this->dateRange();

        $result = Invoice::estimates()
            ->whereBetween('issued_at', [$range['start'], $range['end']])
            ->selectRaw('
                count(*) as count,
                coalesce(sum(total), 0) as total,
                count(case when status = ? then 1 end) as accepted
            ', [InvoiceStatus::ACCEPTED->value])
            ->first();

        return [
            'count' => (int) $result->count,
            'total' => (int) $result->total,
            'accepted' => (int) $result->accepted,
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total_revenue' => 0,
            'total_collected' => 0,
            'total_outstanding' => 0,
            'invoice_count' => 0,
            'paid_count' => 0,
            'overdue_count' => 0,
            'collection_rate' => 0,
            'currency' => null,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
