<?php

namespace App\Services;

use App\Enums\IndianState;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Collection;

/**
 * Splits an Indian GST invoice's tax into its constituent components.
 *
 * The per-line {@see InvoiceItem::$tax_rate} holds the *total* GST
 * rate (e.g. 1800 for 18%). Whether that manifests as CGST + SGST/UTGST
 * (intra-state) or IGST (inter-state) depends on the supplier's state versus
 * the place of supply — so it is derived here rather than entered. A supply to
 * an overseas customer is an export, which is inter-state and therefore IGST.
 */
class GstBreakdownService
{
    /**
     * Build the tax breakdown map for an invoice, keyed by display label
     * (e.g. "CGST @ 9%") with amounts in cents.
     *
     * Returns an empty array when the invoice is not an Indian GST supply or
     * the intra/inter-state split cannot be determined — callers should fall
     * back to the single aggregate tax line in that case.
     *
     * @return array<string, int>
     */
    public function build(Invoice $invoice): array
    {
        if (! $this->isIndianGstSupply($invoice)) {
            return [];
        }

        $supplierCode = $this->supplierStateCode($invoice);

        if ($supplierCode === null) {
            return [];
        }

        // A supply to an overseas customer is an export — always an inter-state
        // supply, so IGST applies (never CGST/SGST) regardless of any place of
        // supply.
        $isExport = $this->isExportSupply($invoice);
        $placeCode = $this->placeOfSupplyCode($invoice);

        if (! $isExport && $placeCode === null) {
            return [];
        }

        $isIntraState = ! $isExport && $supplierCode === $placeCode;

        $breakdown = [];

        foreach ($this->taxByRate($invoice) as $rate => $taxAmount) {
            if ($taxAmount <= 0) {
                continue;
            }

            if ($isIntraState) {
                $stateLabel = IndianState::tryFrom($placeCode)?->leviesUtgst() ? 'UTGST' : 'SGST';
                $halfRate = $this->formatRate($rate / 2);

                // Split the already-rounded line tax so the components always
                // reconcile exactly to the invoice total; any odd cent goes to
                // the state component.
                $cgst = intdiv($taxAmount, 2);
                $stateTax = $taxAmount - $cgst;

                $breakdown["CGST @ {$halfRate}"] = ($breakdown["CGST @ {$halfRate}"] ?? 0) + $cgst;
                $breakdown["{$stateLabel} @ {$halfRate}"] = ($breakdown["{$stateLabel} @ {$halfRate}"] ?? 0) + $stateTax;

                continue;
            }

            $label = 'IGST @ '.$this->formatRate($rate);
            $breakdown[$label] = ($breakdown[$label] ?? 0) + $taxAmount;
        }

        return $breakdown;
    }

    /**
     * Aggregate each line's rounded tax amount grouped by its GST rate (basis
     * points), so per-rate rows can be produced for mixed-rate invoices.
     *
     * @return array<int, int> keyed by rate in basis points
     */
    private function taxByRate(Invoice $invoice): array
    {
        return $invoice->items
            ->reduce(function (Collection $carry, $item) {
                $rate = (int) ($item->tax_rate ?? 0);

                if ($rate <= 0) {
                    return $carry;
                }

                $carry[$rate] = ($carry[$rate] ?? 0) + $item->getTaxAmount();

                return $carry;
            }, collect())
            ->toArray();
    }

    private function isIndianGstSupply(Invoice $invoice): bool
    {
        $orgLocation = $invoice->organizationLocation;

        return ! empty($orgLocation?->tax_id) && (bool) $orgLocation?->isIndian();
    }

    /**
     * Whether this is an export — a supply to a customer located outside India.
     * Exports are inter-state supplies, so IGST applies.
     */
    private function isExportSupply(Invoice $invoice): bool
    {
        $customerLocation = $invoice->customerLocation;

        return $customerLocation !== null && ! $customerLocation->isIndian();
    }

    /**
     * The supplier's GST state code — the first two digits of the org GSTIN.
     */
    private function supplierStateCode(Invoice $invoice): ?string
    {
        $taxId = $invoice->organizationLocation?->tax_id;

        if (! $taxId || strlen($taxId) < 2) {
            return null;
        }

        return IndianState::tryFrom(substr($taxId, 0, 2))?->value;
    }

    /**
     * The place of supply state code, preferring the explicit invoice field and
     * falling back to the customer GSTIN for legacy invoices.
     */
    private function placeOfSupplyCode(Invoice $invoice): ?string
    {
        if ($invoice->place_of_supply) {
            return IndianState::tryFrom($invoice->place_of_supply)?->value;
        }

        $customerLocation = $invoice->customerLocation;

        // Only fall back to the customer GSTIN for Indian customers. A foreign
        // tax ID could coincidentally begin with digits matching an Indian
        // state code and produce a bogus intra/inter-state split.
        if (! $customerLocation?->isIndian()) {
            return null;
        }

        $customerTaxId = $customerLocation->tax_id;

        if ($customerTaxId && strlen($customerTaxId) >= 2) {
            return IndianState::tryFrom(substr($customerTaxId, 0, 2))?->value;
        }

        return null;
    }

    /**
     * Format a basis-point rate as a trimmed percentage (900 → "9%",
     * 250 → "2.5%").
     */
    private function formatRate(float $basisPoints): string
    {
        return sprintf('%g', $basisPoints / 100).'%';
    }
}
