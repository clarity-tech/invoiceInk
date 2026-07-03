<?php

namespace App\Services;

use App\Contracts\Services\InvoiceNumberingServiceInterface;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class EstimateToInvoiceConverter
{
    public function __construct(
        private InvoiceCalculator $invoiceCalculator,
        private InvoiceNumberingServiceInterface $numberingService,
        private GstBreakdownService $gstBreakdownService = new GstBreakdownService,
    ) {}

    public function convert(Invoice $estimate): Invoice
    {
        if (! $estimate->isEstimate()) {
            throw new \InvalidArgumentException('Only estimates can be converted to invoices');
        }

        $estimate->load('items', 'organization', 'organizationLocation');

        // Generate invoice number using new numbering service
        $invoiceNumberData = $this->numberingService->generateInvoiceNumber(
            $estimate->organization,
            $estimate->organizationLocation
        );

        $invoice = new Invoice([
            'type' => 'invoice',
            'organization_id' => $estimate->organization_id,
            'customer_id' => $estimate->customer_id,
            'organization_location_id' => $estimate->organization_location_id,
            'customer_location_id' => $estimate->customer_location_id,
            'invoice_number' => $invoiceNumberData['invoice_number'],
            'invoice_numbering_series_id' => $invoiceNumberData['series_id'],
            'status' => 'draft',
            'issued_at' => $estimate->issued_at,
            'due_at' => $estimate->due_at,
            'currency' => $estimate->currency,
            'place_of_supply' => $estimate->place_of_supply,
            'exchange_rate' => $estimate->exchange_rate,
            'subtotal' => $estimate->subtotal,
            'tax' => $estimate->tax,
            'total' => $estimate->total,
            'tds_rate' => $estimate->tds_rate,
            'tds_section' => $estimate->tds_section,
            'tcs_rate' => $estimate->tcs_rate,
            'tcs_section' => $estimate->tcs_section,
        ]);

        $invoice->save();

        foreach ($estimate->items as $estimateItem) {
            $invoiceItem = new InvoiceItem([
                'invoice_id' => $invoice->id,
                'description' => $estimateItem->description,
                'sac_code' => $estimateItem->sac_code,
                'quantity' => $estimateItem->quantity,
                'unit_price' => $estimateItem->unit_price,
                'tax_rate' => $estimateItem->tax_rate,
            ]);
            $invoiceItem->save();
        }

        $this->invoiceCalculator->updateInvoiceTotals($invoice);
        $invoice->save();

        $invoice->load(['items', 'organizationLocation', 'customerLocation']);
        $breakdown = $this->gstBreakdownService->build($invoice);
        $invoice->update([
            'tax_breakdown' => $breakdown ?: null,
            'tax_type' => $breakdown ? 'GST' : null,
        ]);

        return $invoice;
    }
}
