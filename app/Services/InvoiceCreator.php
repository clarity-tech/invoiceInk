<?php

namespace App\Services;

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Enums\NumberingMode;
use App\Exceptions\ExchangeRateUnavailable;
use App\Exceptions\InvoiceNumberUnavailable;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\EmailRecipients;
use App\ValueObjects\InvoiceInput;
use App\ValueObjects\InvoiceLineInput;
use App\ValueObjects\InvoiceTotals;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates an invoice or estimate: the one place that decides its number,
 * currency, exchange rate, totals, TDS/TCS, items and GST split. The invoice
 * form and the recurring generator both create through here, so an invoice
 * comes out the same whichever of them made it.
 *
 * Every lookup filters by the organization id in the input rather than relying
 * on {@see OrganizationScope}, which filters nothing when nobody is signed in:
 * a background job would otherwise read every organization's series and
 * customers.
 */
class InvoiceCreator
{
    public function __construct(
        private readonly InvoiceCalculator $calculator,
        private readonly ExchangeRateService $exchangeRates,
        private readonly InvoiceNumberingServiceInterface $numbering,
        private readonly GstBreakdownService $gstBreakdown,
        private readonly TdsTcsCalculator $tdsTcs,
    ) {}

    /**
     * Whatever can fail without writing runs first: the references are loaded
     * and the exchange rate resolved before a number is minted, so a failure
     * never uses up a number. The number, the invoice, its items and its GST
     * split are then written in one transaction, whose rollback also restores
     * the series counter.
     *
     * @throws ExchangeRateUnavailable when a foreign-currency rate cannot be found. Left
     *                                 to the caller: the form shows it beside the issue
     *                                 date, an unattended caller can retry later.
     * @throws InvoiceNumberUnavailable in strict mode, when the series cannot supply the number
     * @throws ModelNotFoundException in strict mode, for a reference outside the organization or archived
     * @throws InvalidArgumentException in strict mode, for an estimate
     */
    public function create(InvoiceInput $input, NumberingMode $mode): Invoice
    {
        if ($mode === NumberingMode::Strict && $input->type !== 'invoice') {
            throw new InvalidArgumentException('Strict numbering creates invoices only, not estimates.');
        }

        $organization = Organization::findOrFail($input->organizationId);
        $organizationLocation = $this->findOrganizationLocation($input, $mode);
        $customer = $this->findCustomer($input, $mode);
        $series = $this->findPinnedSeries($input, $mode);

        $currency = $customer?->currency?->value;
        $exchangeRate = $this->resolveExchangeRate($currency, $organization->currency?->value, $input->issuedAt);

        $totals = $this->totalsFor($input->lines);
        $sourceTaxes = $this->tdsTcs->compute($totals->subtotal, $totals->tax, $input->tdsRate, $input->tcsRate);

        return DB::transaction(function () use ($input, $mode, $organization, $organizationLocation, $customer, $series, $currency, $exchangeRate, $totals, $sourceTaxes) {
            $number = $this->mintNumber($input, $mode, $organization, $organizationLocation, $series);

            $invoice = Invoice::create([
                'type' => $input->type,
                'organization_id' => $organization->id,
                'customer_id' => $input->customerId,
                'organization_location_id' => $input->organizationLocationId,
                'customer_location_id' => $input->customerLocationId,
                'customer_shipping_location_id' => $input->customerShippingLocationId,
                'invoice_number' => $number['invoice_number'],
                'order_number' => $input->orderNumber,
                'invoice_numbering_series_id' => $number['series_id'],
                'status' => $input->status,
                'issued_at' => $input->issuedAt,
                'due_at' => $input->dueAt,
                'subtotal' => $totals->subtotal,
                'tax' => $totals->tax,
                'total' => $sourceTaxes['total'],
                'currency' => $currency,
                // An export has no Indian place of supply.
                'place_of_supply' => $customer?->isOverseas() ? null : $input->placeOfSupply,
                'tds_rate' => $input->tdsRate,
                'tds_amount' => $sourceTaxes['tds_amount'],
                'tds_section' => $input->tdsSection,
                'tcs_rate' => $input->tcsRate,
                'tcs_amount' => $sourceTaxes['tcs_amount'],
                'tcs_section' => $input->tcsSection,
                'exchange_rate' => $exchangeRate,
                'subject' => $input->subject,
                'notes' => $input->notes,
                'terms' => $input->terms,
                'email_recipients' => EmailRecipients::normalize($input->emailRecipients),
            ]);

            $this->persistItems($invoice, $input->lines);
            $this->applyGstBreakdown($invoice);

            return $invoice;
        });
    }

    /**
     * Subtotal and GST, each line rounded the way {@see InvoiceItem} rounds it.
     *
     * @param  list<InvoiceLineInput>  $lines
     */
    public function totalsFor(array $lines): InvoiceTotals
    {
        $items = collect($lines)->map(fn (InvoiceLineInput $line) => new InvoiceItem([
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => $line->unitPrice,
            'tax_rate' => $line->taxRate,
        ]));

        return $this->calculator->calculateFromItems($items);
    }

    /**
     * @param  list<InvoiceLineInput>  $lines
     */
    public function persistItems(Invoice $invoice, array $lines): void
    {
        foreach ($lines as $line) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $line->description,
                'sac_code' => $line->sacCode,
                'quantity' => $line->quantity,
                'unit_price' => $line->unitPrice,
                'tax_rate' => $line->taxRate,
            ]);
        }
    }

    /**
     * Derive the CGST/SGST/UTGST/IGST split from the place of supply and store
     * it on the invoice. Leaves both fields null for non-GST invoices so the
     * document falls back to a single aggregate tax line.
     */
    public function applyGstBreakdown(Invoice $invoice): void
    {
        $invoice->load(['items', 'organizationLocation', 'customerLocation']);

        $breakdown = $this->gstBreakdown->build($invoice);

        $invoice->update([
            'tax_breakdown' => $breakdown ?: null,
            'tax_type' => $breakdown ? 'GST' : null,
        ]);
    }

    /**
     * The rate is persisted at issue time and never re-read, so a wrong one is
     * permanent. When none can be resolved this throws rather than storing 1.0,
     * which would understate the invoice by the whole rate.
     *
     * @throws ExchangeRateUnavailable
     */
    public function resolveExchangeRate(?string $from, ?string $to, CarbonInterface $date): float
    {
        if (! $from || ! $to) {
            return 1.0;
        }

        return $this->exchangeRates->getRate($from, $to, Carbon::instance($date));
    }

    /**
     * Lenient tolerates a missing (archived) location as the form always has,
     * which only costs it a location-specific series.
     */
    private function findOrganizationLocation(InvoiceInput $input, NumberingMode $mode): ?Location
    {
        $query = Location::query()
            ->where('locatable_type', Organization::class)
            ->where('locatable_id', $input->organizationId);

        return $mode === NumberingMode::Strict
            ? $query->findOrFail($input->organizationLocationId)
            : $query->find($input->organizationLocationId);
    }

    /**
     * Lenient tolerates a missing (archived) customer as the form always has,
     * which leaves the invoice without a currency. Strict refuses, and also
     * requires both customer locations to be this customer's.
     */
    private function findCustomer(InvoiceInput $input, NumberingMode $mode): ?Customer
    {
        $query = Customer::withoutGlobalScope(OrganizationScope::class)
            ->forOrganization($input->organizationId);

        if ($mode === NumberingMode::Lenient) {
            return $query->find($input->customerId);
        }

        $customer = $query->findOrFail($input->customerId);

        $locationIds = array_unique([$input->customerLocationId, $input->customerShippingLocationId]);
        $found = Location::query()
            ->where('locatable_type', Customer::class)
            ->where('locatable_id', $customer->id)
            ->whereKey($locationIds)
            ->count();

        if ($found !== count($locationIds)) {
            throw (new ModelNotFoundException)->setModel(Location::class, $locationIds);
        }

        return $customer;
    }

    /**
     * Strict fails here, before the exchange rate is looked up, when the pinned
     * series is not the organization's. Lenient ignores it and numbers from the
     * default series, as the form always has.
     */
    private function findPinnedSeries(InvoiceInput $input, NumberingMode $mode): ?InvoiceNumberingSeries
    {
        if ($input->numberingSeriesId === null) {
            return null;
        }

        $series = InvoiceNumberingSeries::withoutGlobalScope(OrganizationScope::class)
            ->forOrganization($input->organizationId)
            ->find($input->numberingSeriesId);

        if (! $series && $mode === NumberingMode::Strict) {
            throw InvoiceNumberUnavailable::seriesNotInOrganization($input->numberingSeriesId, $input->organizationId);
        }

        return $series;
    }

    /**
     * @return array{invoice_number: string, series_id: int|null}
     */
    private function mintNumber(
        InvoiceInput $input,
        NumberingMode $mode,
        Organization $organization,
        ?Location $organizationLocation,
        ?InvoiceNumberingSeries $series,
    ): array {
        // Estimates have no series; the dated EST number is their only scheme.
        if ($input->type !== 'invoice') {
            return ['invoice_number' => $this->generateFallbackNumber($input->type, $organization->id), 'series_id' => null];
        }

        return match ($mode) {
            NumberingMode::Lenient => $this->mintLeniently($organization, $organizationLocation, $series),
            NumberingMode::Strict => $this->mintStrictly($organization, $organizationLocation, $series),
        };
    }

    /**
     * What the invoice form has always done. A pinned series is matched by
     * name, which can pick a same-named sibling, and any failure (inactive
     * series, FY pattern without FY setup) silently gives a dated number.
     *
     * @return array{invoice_number: string, series_id: int|null}
     */
    private function mintLeniently(Organization $organization, ?Location $organizationLocation, ?InvoiceNumberingSeries $series): array
    {
        try {
            $data = $series
                ? $this->numbering->generateInvoiceNumber($organization, $organizationLocation, $series->name)
                : $this->numbering->generateInvoiceNumber($organization, $organizationLocation);

            return ['invoice_number' => $data['invoice_number'], 'series_id' => $data['series_id']];
        } catch (InvalidArgumentException) {
            return ['invoice_number' => $this->generateFallbackNumber('invoice', $organization->id), 'series_id' => null];
        }
    }

    /**
     * A pinned series is used by id. Without one, the location's or default
     * series is used (and created if the organization has none yet), but a
     * failure still throws instead of falling back.
     *
     * @return array{invoice_number: string, series_id: int}
     *
     * @throws InvoiceNumberUnavailable
     */
    private function mintStrictly(Organization $organization, ?Location $organizationLocation, ?InvoiceNumberingSeries $series): array
    {
        try {
            $data = $series
                ? $this->numbering->generateInvoiceNumberForSeries($organization, $series)
                : $this->numbering->generateInvoiceNumber($organization, $organizationLocation);
        } catch (InvalidArgumentException $e) {
            throw InvoiceNumberUnavailable::refused($e, $series?->id);
        }

        return ['invoice_number' => $data['invoice_number'], 'series_id' => $data['series_id']];
    }

    private function generateFallbackNumber(string $type, int $organizationId): string
    {
        return DB::transaction(function () use ($type, $organizationId) {
            $prefix = match ($type) {
                'invoice' => 'INV',
                default => 'EST',
            };
            $year = now()->year;
            $month = now()->format('m');

            $lastDocument = Invoice::where('type', $type)
                ->where('organization_id', $organizationId)
                ->where('invoice_number', 'like', "{$prefix}-{$year}-{$month}-%")
                ->lockForUpdate()
                ->orderBy('invoice_number', 'desc')
                ->first();

            $sequence = $lastDocument
                ? (int) last(explode('-', $lastDocument->invoice_number)) + 1
                : 1;

            return sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $sequence);
        });
    }
}
