<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceCalculator;
use App\ValueObjects\InvoiceTotals;
use Illuminate\Support\Collection;

test('calculates invoice with no items', function () {
    $invoice = new Invoice;
    $invoice->setRelation('items', new Collection);

    $calculator = new InvoiceCalculator;
    $result = $calculator->calculateInvoice($invoice);

    expect($result)->toBeInstanceOf(InvoiceTotals::class);
    expect($result->subtotal)->toBe(0);
    expect($result->tax)->toBe(0);
    expect($result->total)->toBe(0);
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

    $invoice = new Invoice;
    $invoice->setRelation('items', new Collection([$item1, $item2]));

    $calculator = new InvoiceCalculator;
    $result = $calculator->calculateInvoice($invoice);

    expect($result)->toBeInstanceOf(InvoiceTotals::class);
    expect($result->subtotal)->toBe(3500); // $35.00
    expect($result->tax)->toBe(0);
    expect($result->total)->toBe(3500);
});

test('calculates invoice with items with tax', function () {
    $item1 = new InvoiceItem([
        'quantity' => 2,
        'unit_price' => 1000, // $10.00
        'tax_rate' => 10, // 10% as users would enter
    ]);

    $item2 = new InvoiceItem([
        'quantity' => 1,
        'unit_price' => 1500, // $15.00
        'tax_rate' => 20, // 20% as users would enter
    ]);

    $invoice = new Invoice;
    $invoice->setRelation('items', new Collection([$item1, $item2]));

    $calculator = new InvoiceCalculator;
    $result = $calculator->calculateInvoice($invoice);

    expect($result)->toBeInstanceOf(InvoiceTotals::class);
    expect($result->subtotal)->toBe(3500); // $35.00
    expect($result->tax)->toBe(500); // $2.00 + $3.00 = $5.00
    expect($result->total)->toBe(4000); // $40.00
});

test('calculates from items collection', function () {
    $items = new Collection([
        new InvoiceItem([
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 10, // 10% as users would enter
        ]),
        new InvoiceItem([
            'quantity' => 2,
            'unit_price' => 500,
            'tax_rate' => 5, // 5% as users would enter
        ]),
    ]);

    $calculator = new InvoiceCalculator;
    $result = $calculator->calculateFromItems($items);

    expect($result)->toBeInstanceOf(InvoiceTotals::class);
    expect($result->subtotal)->toBe(2000); // $10.00 + $10.00 = $20.00
    expect($result->tax)->toBe(150); // $1.00 + $0.50 = $1.50
    expect($result->total)->toBe(2150); // $21.50
});

test('updates invoice totals', function () {
    $item = new InvoiceItem([
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 10, // 10% as users would enter
    ]);

    $invoice = new Invoice([
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
    ]);
    $invoice->setRelation('items', new Collection([$item]));

    $calculator = new InvoiceCalculator;
    $updatedInvoice = $calculator->updateInvoiceTotals($invoice);

    expect($updatedInvoice->subtotal)->toBe(1000);
    expect($updatedInvoice->tax)->toBe(100);
    expect($updatedInvoice->total)->toBe(1100);
});

test('invoice totals value object has zero factory method', function () {
    $totals = InvoiceTotals::zero();

    expect($totals->subtotal)->toBe(0);
    expect($totals->tax)->toBe(0);
    expect($totals->total)->toBe(0);
});

test('invoice totals value object can convert to array', function () {
    $totals = new InvoiceTotals(1000, 100, 1100);

    expect($totals->toArray())->toBe([
        'subtotal' => 1000,
        'tax' => 100,
        'total' => 1100,
    ]);
});
