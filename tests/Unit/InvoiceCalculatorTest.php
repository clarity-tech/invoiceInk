<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceCalculator;
use Illuminate\Support\Collection;

test('calculates invoice with no items', function () {
    $invoice = new Invoice();
    $invoice->setRelation('items', new Collection());
    
    $calculator = new InvoiceCalculator();
    $result = $calculator->calculateInvoice($invoice);
    
    expect($result)->toBe([
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
    ]);
});

test('calculates invoice with items without tax', function () {
    $item1 = new InvoiceItem([
        'quantity' => 2,
        'unit_price' => 1000, // $10.00
        'tax_rate' => null,
    ]);
    
    $item2 = new InvoiceItem([
        'quantity' => 1,
        'unit_price' => 1500, // $15.00
        'tax_rate' => null,
    ]);
    
    $invoice = new Invoice();
    $invoice->setRelation('items', new Collection([$item1, $item2]));
    
    $calculator = new InvoiceCalculator();
    $result = $calculator->calculateInvoice($invoice);
    
    expect($result)->toBe([
        'subtotal' => 3500, // $35.00
        'tax' => 0,
        'total' => 3500,
    ]);
});

test('calculates invoice with items with tax', function () {
    $item1 = new InvoiceItem([
        'quantity' => 2,
        'unit_price' => 1000, // $10.00
        'tax_rate' => 10, // 10%
    ]);
    
    $item2 = new InvoiceItem([
        'quantity' => 1,
        'unit_price' => 1500, // $15.00
        'tax_rate' => 20, // 20%
    ]);
    
    $invoice = new Invoice();
    $invoice->setRelation('items', new Collection([$item1, $item2]));
    
    $calculator = new InvoiceCalculator();
    $result = $calculator->calculateInvoice($invoice);
    
    expect($result)->toBe([
        'subtotal' => 3500, // $35.00
        'tax' => 500, // $2.00 + $3.00 = $5.00
        'total' => 4000, // $40.00
    ]);
});

test('calculates from items collection', function () {
    $items = new Collection([
        new InvoiceItem([
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 10,
        ]),
        new InvoiceItem([
            'quantity' => 2,
            'unit_price' => 500,
            'tax_rate' => 5,
        ]),
    ]);
    
    $calculator = new InvoiceCalculator();
    $result = $calculator->calculateFromItems($items);
    
    expect($result)->toBe([
        'subtotal' => 2000, // $10.00 + $10.00 = $20.00
        'tax' => 150, // $1.00 + $0.50 = $1.50
        'total' => 2150, // $21.50
    ]);
});

test('updates invoice totals', function () {
    $item = new InvoiceItem([
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 10,
    ]);
    
    $invoice = new Invoice([
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
    ]);
    $invoice->setRelation('items', new Collection([$item]));
    
    $calculator = new InvoiceCalculator();
    $updatedInvoice = $calculator->updateInvoiceTotals($invoice);
    
    expect($updatedInvoice->subtotal)->toBe(1000);
    expect($updatedInvoice->tax)->toBe(100);
    expect($updatedInvoice->total)->toBe(1100);
});
