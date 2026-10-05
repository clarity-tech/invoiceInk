<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Mcp\Concerns\ResolvesActingOrganization;
use App\Models\Invoice;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Accounts-receivable aging for the current organization: how much is owed, by how overdue it is '
    .'(not yet due, 1-30, 31-60, 61-90, 90+ days). Reported per currency, since an organization may '
    .'invoice in several and those totals cannot be added together.'
)]
#[Name('outstanding-report')]
class OutstandingReportTool extends Tool
{
    use ResolvesActingOrganization;

    /** @var list<array{key: string, label: string, from: int|null, to: int|null}> */
    private const BUCKETS = [
        ['key' => 'not_yet_due', 'label' => 'Not yet due', 'from' => null, 'to' => 0],
        ['key' => 'overdue_1_30', 'label' => '1-30 days overdue', 'from' => 1, 'to' => 30],
        ['key' => 'overdue_31_60', 'label' => '31-60 days overdue', 'from' => 31, 'to' => 60],
        ['key' => 'overdue_61_90', 'label' => '61-90 days overdue', 'from' => 61, 'to' => 90],
        ['key' => 'overdue_90_plus', 'label' => 'Over 90 days overdue', 'from' => 91, 'to' => null],
    ];

    public function handle(Request $request): Response|ResponseFactory
    {
        $organization = $this->actingOrganization($request);

        if ($organization instanceof Response) {
            return $organization;
        }

        $validated = $request->validate([
            'customer' => 'string|max:255',
        ]);

        $asOf = Carbon::now()->startOfDay();

        // Everything with a balance, not just wholly unpaid documents: the
        // Invoice::unpaid() scope means "amount_paid = 0" and would silently
        // drop partially paid invoices, understating what is owed.
        $query = Invoice::query()
            ->forOrganization($organization->id)
            ->invoices()
            ->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::VOID])
            ->whereColumn('amount_paid', '<', 'total')
            ->with('customer');

        if (isset($validated['customer'])) {
            $name = $validated['customer'];
            $query->whereHas(
                'customer',
                fn ($customer) => $customer->withoutGlobalScopes()
                    ->where('organization_id', $organization->id)
                    ->where('name', 'like', '%'.$name.'%')
            );
        }

        $invoices = $query->get();

        $byCurrency = $invoices
            ->groupBy(fn (Invoice $invoice): string => $invoice->currency?->value ?? 'unknown')
            ->map(fn (Collection $group, string $currency): array => $this->summariseCurrency($group, $currency, $asOf))
            ->values()
            ->all();

        return Response::structured([
            'organization' => $organization->name,
            'as_of' => $asOf->toDateString(),
            'open_invoice_count' => $invoices->count(),
            'note' => 'Totals are per currency and must not be added across currencies.',
            'by_currency' => $byCurrency,
        ]);
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array<string, mixed>
     */
    private function summariseCurrency(Collection $invoices, string $currency, Carbon $asOf): array
    {
        $buckets = [];

        foreach (self::BUCKETS as $bucket) {
            $matching = $invoices->filter(
                fn (Invoice $invoice): bool => $this->bucketKeyFor($invoice, $asOf) === $bucket['key']
            );

            $buckets[] = [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'invoice_count' => $matching->count(),
                'balance_cents' => $matching->sum(fn (Invoice $invoice): int => $invoice->remaining_balance),
            ];
        }

        $totalCents = $invoices->sum(fn (Invoice $invoice): int => $invoice->remaining_balance);

        return [
            'currency' => $currency,
            'invoice_count' => $invoices->count(),
            'total_outstanding_cents' => $totalCents,
            'total_outstanding' => $invoices->first()?->formatMoney($totalCents),
            'buckets' => $buckets,
            'oldest_due_date' => $invoices->min(fn (Invoice $invoice): ?string => $invoice->due_at?->toDateString()),
        ];
    }

    private function bucketKeyFor(Invoice $invoice, Carbon $asOf): string
    {
        if ($invoice->due_at === null || $invoice->due_at->startOfDay()->greaterThanOrEqualTo($asOf)) {
            return 'not_yet_due';
        }

        $daysOverdue = $invoice->due_at->startOfDay()->diffInDays($asOf);

        foreach (self::BUCKETS as $bucket) {
            if ($bucket['from'] === null) {
                continue;
            }

            $withinLowerBound = $daysOverdue >= $bucket['from'];
            $withinUpperBound = $bucket['to'] === null || $daysOverdue <= $bucket['to'];

            if ($withinLowerBound && $withinUpperBound) {
                return $bucket['key'];
            }
        }

        return 'overdue_90_plus';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer' => $schema->string()
                ->description('Limit the report to customers whose name matches this partial string.'),
        ];
    }
}
