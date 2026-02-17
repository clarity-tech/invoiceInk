<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $period = $request->query('period', 'this_month');
        $range = $this->dateRange($period);
        $organization = $request->user()->currentTeam;

        return Inertia::render('Dashboard', [
            'period' => $period,
            'organizationName' => $organization?->company_name ?? $organization?->name ?? 'Dashboard',
            'stats' => fn () => $this->getStats($range, $organization),
            'statusBreakdown' => fn () => $this->getStatusBreakdown($range),
            'recentInvoices' => fn () => $this->getRecentInvoices(),
            'overdueInvoices' => fn () => $this->getOverdueInvoices(),
            'recentPayments' => fn () => $this->getRecentPayments($organization),
            'topCustomers' => fn () => $this->getTopCustomers($range),
            'monthlyTrend' => fn () => $this->getMonthlyTrend(),
            'customerCount' => fn () => Customer::count(),
            'estimateStats' => fn () => $this->getEstimateStats($range),
        ]);
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function dateRange(string $period): array
    {
        return match ($period) {
            'this_week' => ['start' => now()->startOfWeek(), 'end' => now()->endOfWeek()],
            'this_month' => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
            'last_month' => ['start' => now()->subMonth()->startOfMonth(), 'end' => now()->subMonth()->endOfMonth()],
            'this_quarter' => ['start' => now()->firstOfQuarter(), 'end' => now()->lastOfQuarter()->endOfDay()],
            'this_year' => ['start' => now()->startOfYear(), 'end' => now()->endOfYear()],
            'all_time' => ['start' => Carbon::createFromDate(2000, 1, 1), 'end' => now()->endOfDay()],
            default => ['start' => now()->startOfMonth(), 'end' => now()->endOfMonth()],
        };
    }

    private function getStats(array $range, mixed $organization): array
    {
        if (! $organization) {
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
            'currency' => $organization->currency?->value ?? $organization->currency,
        ];
    }

    private function getStatusBreakdown(array $range): array
    {
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
                'status' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($data?->count ?? 0),
                'total' => (int) ($data?->total_amount ?? 0),
            ];
        })->toArray();
    }

    private function getRecentInvoices(): array
    {
        return Invoice::invoices()
            ->with(['customer'])
            ->latest('issued_at')
            ->limit(5)
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'status' => $inv->status->value,
                'customer_name' => $inv->customer?->name ?? 'N/A',
                'issued_at' => $inv->issued_at?->format('M j'),
                'total' => $inv->total,
                'currency' => $inv->currency?->value ?? $inv->currency,
            ])
            ->toArray();
    }

    private function getOverdueInvoices(): array
    {
        return Invoice::invoices()
            ->overdue()
            ->with(['customer'])
            ->orderBy('due_at')
            ->limit(5)
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'customer_name' => $inv->customer?->name ?? 'N/A',
                'remaining_balance' => $inv->remaining_balance,
                'currency' => $inv->currency?->value ?? $inv->currency,
                'due_at_human' => $inv->due_at->diffForHumans(),
            ])
            ->toArray();
    }

    private function getRecentPayments(mixed $organization): array
    {
        $orgId = $organization?->id;

        if (! $orgId) {
            return [];
        }

        return Payment::whereHas('invoice', fn ($q) => $q->withoutGlobalScopes()->where('organization_id', $orgId))
            ->with(['invoice.customer'])
            ->latest('payment_date')
            ->limit(5)
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice?->invoice_number,
                'customer_name' => $payment->invoice?->customer?->name ?? 'N/A',
                'amount' => $payment->amount,
                'currency' => $payment->currency?->value ?? $payment->currency,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date->format('M j, Y'),
            ])
            ->toArray();
    }

    private function getTopCustomers(array $range): array
    {
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
                'invoice_count' => (int) $row->invoice_count,
                'total' => (int) $row->total_amount,
                'paid' => (int) $row->total_paid,
                'outstanding' => (int) $row->total_amount - (int) $row->total_paid,
            ])
            ->toArray();
    }

    private function getMonthlyTrend(): array
    {
        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();

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

    private function getEstimateStats(array $range): array
    {
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
}
