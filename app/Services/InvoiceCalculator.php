<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Collection;

class InvoiceCalculator
{
    public function calculateInvoice(Invoice $invoice): array
    {
        $items = $invoice->items;
        
        if ($items->isEmpty()) {
            return [
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
            ];
        }

        return $this->calculateFromItems($items);
    }

    public function calculateFromItems(Collection $items): array
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($items as $item) {
            $lineTotal = $item->getLineTotal();
            $lineTax = $item->getTaxAmount();
            
            $subtotal += $lineTotal;
            $taxAmount += $lineTax;
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ];
    }

    public function updateInvoiceTotals(Invoice $invoice): Invoice
    {
        $calculations = $this->calculateInvoice($invoice);
        
        $invoice->subtotal = $calculations['subtotal'];
        $invoice->tax = $calculations['tax'];
        $invoice->total = $calculations['total'];
        
        return $invoice;
    }

    public function recalculateInvoice(Invoice $invoice): Invoice
    {
        $invoice->refresh();
        $invoice->load('items');
        
        return $this->updateInvoiceTotals($invoice);
    }
}