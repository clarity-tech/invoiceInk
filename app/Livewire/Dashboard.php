<?php

namespace App\Livewire;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Carbon;
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

    #[Computed]
    public function stats(): array
    {
        $range = $this->dateRange();
        $organization = auth()->user()->currentTeam;

        if (! $organization) {
            return $this->emptyStats();
        }

        $invoices = Invoice::invoices()
            ->whereBetween('issued_at', [$range['start'], $range['end']]);

        $totalRevenue = (clone $invoices)->sum('total');
        $totalCollected = (clone $invoices)->sum('amount_paid');
        $totalOutstanding = $totalRevenue - $totalCollected;
        $invoiceCount = (clone $invoices)->count();
        $paidCount = (clone $invoices)->where('status', InvoiceStatus::PAID)->count();
        $overdueCount = Invoice::invoices()->overdue()->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_collected' => $totalCollected,
            'total_outstanding' => $totalOutstanding,
            'invoice_count' => $invoiceCount,
            'paid_count' => $paidCount,
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
            ->selectRaw('status, count(*) as count, sum(total) as total_amount')
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

        return Payment::whereHas('invoice', fn ($q) => $q->where('organization_id', $orgId))
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
            ->selectRaw('customer_id, count(*) as invoice_count, sum(total) as total_amount, sum(amount_paid) as total_paid')
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

    #[Computed]
    public function monthlyTrend(): array
    {
        $months = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $invoiced = Invoice::invoices()
                ->whereBetween('issued_at', [$start, $end])
                ->sum('total');

            $collected = Invoice::invoices()
                ->whereBetween('issued_at', [$start, $end])
                ->sum('amount_paid');

            $months->push([
                'label' => $date->format('M Y'),
                'short' => $date->format('M'),
                'invoiced' => $invoiced,
                'collected' => $collected,
            ]);
        }

        return $months->toArray();
    }

    #[Computed]
    public function customerCount(): int
    {
        return Customer::count();
    }

    #[Computed]
    public function estimateStats(): array
    {
        $range = $this->dateRange();

        $estimates = Invoice::estimates()
            ->whereBetween('issued_at', [$range['start'], $range['end']]);

        return [
            'count' => (clone $estimates)->count(),
            'total' => (clone $estimates)->sum('total'),
            'accepted' => (clone $estimates)->where('status', InvoiceStatus::ACCEPTED)->count(),
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
