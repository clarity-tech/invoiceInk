<?php

namespace App\Services;

use App\Models\Invoice;
use App\ValueObjects\InvoiceTotals;
use Illuminate\Support\Collection;

class InvoiceCalculator
{
    public function __construct(
        private TdsTcsCalculator $tdsTcsCalculator = new TdsTcsCalculator,
    ) {}

    public function calculateInvoice(Invoice $invoice): InvoiceTotals
    {
        $items = $invoice->items;

        if ($items->isEmpty()) {
            return InvoiceTotals::zero();
        }

        return $this->calculateFromItems($items);
    }

    public function calculateFromItems(Collection $items): InvoiceTotals
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($items as $item) {
            $lineTotal = $item->getLineTotal();
            $lineTax = $item->getTaxAmount();

            $subtotal += $lineTotal;
            $taxAmount += $lineTax;
        }

        return new InvoiceTotals(
            subtotal: $subtotal,
            tax: $taxAmount,
            total: $subtotal + $taxAmount
        );
    }

    public function updateInvoiceTotals(Invoice $invoice): Invoice
    {
        $totals = $this->calculateInvoice($invoice);

        $sourceTaxes = $this->tdsTcsCalculator->compute(
            $totals->subtotal,
            $totals->tax,
            $invoice->tds_rate,
            $invoice->tcs_rate,
        );

        $invoice->subtotal = $totals->subtotal;
        $invoice->tax = $totals->tax;
        $invoice->tds_amount = $sourceTaxes['tds_amount'];
        $invoice->tcs_amount = $sourceTaxes['tcs_amount'];
        $invoice->total = $sourceTaxes['total'];

        return $invoice;
    }

    public function recalculateInvoice(Invoice $invoice): Invoice
    {
        $invoice->refresh();
        $invoice->load('items');

        return $this->updateInvoiceTotals($invoice);
    }
}
