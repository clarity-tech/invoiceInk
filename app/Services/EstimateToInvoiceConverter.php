<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;

class EstimateToInvoiceConverter
{
    public function __construct(
        private InvoiceCalculator $invoiceCalculator
    ) {}

    public function convert(Invoice $estimate): Invoice
    {
        if (! $estimate->isEstimate()) {
            throw new \InvalidArgumentException('Only estimates can be converted to invoices');
        }

        $estimate->load('items');

        $invoice = new Invoice([
            'type' => 'invoice',
            'organization_id' => $estimate->organization_id,
            'customer_id' => $estimate->customer_id,
            'organization_location_id' => $estimate->organization_location_id,
            'customer_location_id' => $estimate->customer_location_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'status' => 'draft',
            'issued_at' => $estimate->issued_at,
            'due_at' => $estimate->due_at,
            'currency' => $estimate->currency,
            'exchange_rate' => $estimate->exchange_rate,
            'subtotal' => $estimate->subtotal,
            'tax' => $estimate->tax,
            'total' => $estimate->total,
        ]);

        $invoice->save();

        foreach ($estimate->items as $estimateItem) {
            $invoiceItem = new InvoiceItem([
                'invoice_id' => $invoice->id,
                'description' => $estimateItem->description,
                'quantity' => $estimateItem->quantity,
                'unit_price' => $estimateItem->unit_price,
                'tax_rate' => $estimateItem->tax_rate,
            ]);
            $invoiceItem->save();
        }

        $this->invoiceCalculator->updateInvoiceTotals($invoice);
        $invoice->save();

        return $invoice;
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->year;
        $month = now()->format('m');

        $lastInvoice = Invoice::where('type', 'invoice')
            ->where('invoice_number', 'like', "{$prefix}-{$year}-{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if (! $lastInvoice) {
            $sequence = 1;
        } else {
            $lastNumber = $lastInvoice->invoice_number;
            $parts = explode('-', $lastNumber);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $sequence);
    }
}
