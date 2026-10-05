<?php

namespace App\Mcp\Tools;

use App\Enums\InvoiceStatus;
use App\Mcp\Concerns\ResolvesActingOrganization;
use App\Models\Invoice;
use App\Models\Payment;
use BackedEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Description(
    'Revenue for the current organization over a period: how much was invoiced (documents issued in '
    .'the period) versus how much was collected (payments received in the period, whenever the '
    .'invoice was raised). These answer different questions and will not match. Estimates and voided '
    .'invoices are excluded. Reported per currency.'
)]
#[Name('revenue-summary')]
class RevenueSummaryTool extends Tool
{
    use ResolvesActingOrganization;

    public function handle(Request $request): Response|ResponseFactory
    {
        $organization = $this->actingOrganization($request);

        if ($organization instanceof Response) {
            return $organization;
        }

        $validated = $request->validate([
            'period' => 'in:this_month,last_month,this_quarter,this_year,last_12_months',
            'from' => 'date',
            'to' => 'date|after_or_equal:from',
        ], [
            'period.in' => 'period must be one of: this_month, last_month, this_quarter, this_year, last_12_months.',
            'to.after_or_equal' => 'to must fall on or after from.',
        ]);

        [$from, $to] = $this->resolveRange($validated);

        $invoiced = Invoice::query()
            ->forOrganization($organization->id)
            ->invoices()
            ->where('status', '!=', InvoiceStatus::VOID)
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('currency, COUNT(*) as document_count, SUM(total) as total_cents, SUM(amount_paid) as paid_cents')
            ->groupBy('currency')
            ->get()
            ->keyBy(fn ($row): string => $this->currencyCode($row->currency));

        // Payment carries no organization_id and no global scope, so it is
        // reachable only by joining to an explicitly pinned invoice. A bare
        // Payment aggregate here would silently total every organization.
        $collected = Payment::query()
            ->whereHas('invoice', fn ($invoice) => $invoice->withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->where('type', 'invoice'))
            ->whereBetween('payment_date', [$from, $to])
            ->selectRaw('currency, COUNT(*) as payment_count, SUM(amount) as total_cents')
            ->groupBy('currency')
            ->get()
            ->keyBy(fn ($row): string => $this->currencyCode($row->currency));

        $currencies = $invoiced->keys()
            ->merge($collected->keys())
            ->unique()
            ->values();

        return Response::structured([
            'organization' => $organization->name,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'note' => '"invoiced" counts documents issued in the period; "collected" counts payments '
                .'received in the period against invoices raised at any time. Totals are per currency '
                .'and must not be added across currencies.',
            'by_currency' => $currencies->map(function (string $currency) use ($invoiced, $collected): array {
                $invoicedRow = $invoiced->get($currency);
                $collectedRow = $collected->get($currency);

                return [
                    'currency' => $currency,
                    'invoiced_cents' => (int) ($invoicedRow->total_cents ?? 0),
                    'invoiced_document_count' => (int) ($invoicedRow->document_count ?? 0),
                    'collected_cents' => (int) ($collectedRow->total_cents ?? 0),
                    'collected_payment_count' => (int) ($collectedRow->payment_count ?? 0),
                    'outstanding_on_period_invoices_cents' => (int) ($invoicedRow->total_cents ?? 0)
                        - (int) ($invoicedRow->paid_cents ?? 0),
                ];
            })->all(),
        ]);
    }

    /**
     * Normalise a currency attribute to its code, since it may arrive as a
     * backed enum and enums cannot be used as collection keys.
     */
    private function currencyCode(mixed $currency): string
    {
        return $currency instanceof BackedEnum ? $currency->value : (string) $currency;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(array $validated): array
    {
        if (isset($validated['from']) || isset($validated['to'])) {
            return [
                isset($validated['from'])
                    ? Carbon::parse($validated['from'])->startOfDay()
                    : Carbon::now()->subYear()->startOfDay(),
                isset($validated['to'])
                    ? Carbon::parse($validated['to'])->endOfDay()
                    : Carbon::now()->endOfDay(),
            ];
        }

        $now = Carbon::now();

        return match ($validated['period'] ?? 'this_month') {
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_12_months' => [$now->copy()->subYear()->startOfDay(), $now->copy()->endOfDay()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()
                ->enum(['this_month', 'last_month', 'this_quarter', 'this_year', 'last_12_months'])
                ->description('Named period to report on. Ignored when "from" or "to" is given. Defaults to this_month.')
                ->default('this_month'),

            'from' => $schema->string()
                ->description('Explicit start date, inclusive (YYYY-MM-DD). Overrides "period".'),

            'to' => $schema->string()
                ->description('Explicit end date, inclusive (YYYY-MM-DD). Overrides "period".'),
        ];
    }
}
