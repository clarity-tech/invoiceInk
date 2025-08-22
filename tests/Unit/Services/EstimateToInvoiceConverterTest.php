<?php

use App\Models\Invoice;
use App\Services\EstimateToInvoiceConverter;
use App\Services\InvoiceCalculator;

test('can convert estimate to invoice', function () {
    // Create an estimate with items
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-001',
        'status' => 'sent',
        'issued_at' => now(),
        'due_at' => now()->addDays(30),
        'subtotal' => 10000,
        'tax' => 1800,
        'total' => 11800,
    ], [
        [
            'description' => 'Website Development',
            'quantity' => 1,
            'unit_price' => 5000,
            'tax_rate' => 18, // 18% as users would enter
        ],
        [
            'description' => 'Mobile App Development',
            'quantity' => 1,
            'unit_price' => 5000,
            'tax_rate' => 18, // 18% as users would enter
        ],
    ]);

    $converter = new EstimateToInvoiceConverter(new InvoiceCalculator);
    $invoice = $converter->convert($estimate);

    expect($invoice)->toBeInstanceOf(Invoice::class);
    expect($invoice->type)->toBe('invoice');
    expect($invoice->company_location_id)->toBe($estimate->company_location_id);
    expect($invoice->customer_location_id)->toBe($estimate->customer_location_id);
    expect($invoice->subtotal)->toBe($estimate->subtotal);
    expect($invoice->tax)->toBe($estimate->tax);
    expect($invoice->total)->toBe($estimate->total);
    expect($invoice->status)->toBe('draft');
});

test('converted invoice has all items from estimate', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-002',
        'status' => 'sent',
        'subtotal' => 7500,
        'tax' => 1350,
        'total' => 8850,
    ], [[
        'description' => 'Consulting Services',
        'quantity' => 10,
        'unit_price' => 750,
        'tax_rate' => 18, // 18% as users would enter
    ]]);

    $converter = new EstimateToInvoiceConverter(new InvoiceCalculator);
    $invoice = $converter->convert($estimate);

    expect($invoice->items()->count())->toBe(1);

    $invoiceItem = $invoice->items->first();
    expect($invoiceItem->description)->toBe('Consulting Services');
    expect($invoiceItem->quantity)->toBe(10);
    expect($invoiceItem->unit_price)->toBe(750);
    expect($invoiceItem->tax_rate)->toBe(18.0); // Should return percentage for display
});

test('converted invoice gets new invoice number', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-003',
        'status' => 'sent',
        'subtotal' => 5000,
        'tax' => 900,
        'total' => 5900,
    ]);

    $converter = new EstimateToInvoiceConverter(new InvoiceCalculator);
    $invoice = $converter->convert($estimate);

    expect($invoice->invoice_number)->not->toBe($estimate->invoice_number);
    expect($invoice->invoice_number)->toStartWith('INV-');
});

test('converted invoice has new ULID', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-004',
        'status' => 'sent',
        'subtotal' => 3000,
        'tax' => 540,
        'total' => 3540,
    ]);

    $converter = new EstimateToInvoiceConverter(new InvoiceCalculator);
    $invoice = $converter->convert($estimate);

    expect($invoice->ulid)->not->toBe($estimate->ulid);
    expect($invoice->ulid)->not->toBeNull();
    expect(strlen($invoice->ulid))->toBe(26);
});

test('converter preserves dates from estimate', function () {
    $issuedAt = now()->subDays(5);
    $dueAt = now()->addDays(25);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-005',
        'status' => 'sent',
        'issued_at' => $issuedAt,
        'due_at' => $dueAt,
        'subtotal' => 2000,
        'tax' => 360,
        'total' => 2360,
    ]);

    $converter = new EstimateToInvoiceConverter(new InvoiceCalculator);
    $invoice = $converter->convert($estimate);

    expect($invoice->issued_at->format('Y-m-d H:i:s'))->toBe($issuedAt->format('Y-m-d H:i:s'));
    expect($invoice->due_at->format('Y-m-d H:i:s'))->toBe($dueAt->format('Y-m-d H:i:s'));
});

test('converter handles estimate without dates', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-006',
        'status' => 'sent',
        'subtotal' => 1500,
        'tax' => 270,
        'total' => 1770,
    ]);

    $converter = new EstimateToInvoiceConverter(new InvoiceCalculator);
    $invoice = $converter->convert($estimate);

    expect($invoice->issued_at)->toBeNull();
    expect($invoice->due_at)->toBeNull();
});

test('converter works with estimates that have no items', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-007',
        'status' => 'sent',
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
    ], []); // Empty array for no items

    $converter = new EstimateToInvoiceConverter(new InvoiceCalculator);
    $invoice = $converter->convert($estimate);

    expect($invoice->items()->count())->toBe(0);
    expect($invoice->subtotal)->toBe(0);
    expect($invoice->tax)->toBe(0);
    expect($invoice->total)->toBe(0);
});

test('converter preserves complex item configurations', function () {
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-008',
        'status' => 'sent',
        'subtotal' => 12000,
        'tax' => 1620,
        'total' => 13620,
    ], [
        [
            'description' => 'Product A',
            'quantity' => 2,
            'unit_price' => 3000,
            'tax_rate' => 12, // 12% as users would enter
        ],
        [
            'description' => 'Service B',
            'quantity' => 3,
            'unit_price' => 2000,
            'tax_rate' => 18, // 18% as users would enter
        ],
        [
            'description' => 'Tax-free item',
            'quantity' => 1,
            'unit_price' => 0,
            'tax_rate' => 0,
        ],
    ]);

    $converter = new EstimateToInvoiceConverter(new InvoiceCalculator);
    $invoice = $converter->convert($estimate);

    expect($invoice->items()->count())->toBe(3);

    $items = $invoice->items->sortBy('description');
    expect($items->first()->description)->toBe('Product A');
    expect($items->first()->tax_rate)->toBe(12.0); // Should return percentage for display

    expect($items->last()->description)->toBe('Tax-free item');
    expect($items->last()->tax_rate)->toBe(0.0); // Should return percentage for display
});
