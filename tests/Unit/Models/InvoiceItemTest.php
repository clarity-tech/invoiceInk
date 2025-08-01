<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;

test('can create invoice item with all fields', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Service',
        'quantity' => 2,
        'unit_price' => 1000,
        'tax_rate' => 18,
    ]);

    expect($item->invoice_id)->toBe($invoice->id);
    expect($item->description)->toBe('Test Service');
    expect($item->quantity)->toBe(2);
    expect($item->unit_price)->toBe(1000);
    expect($item->tax_rate)->toBe(18);
});

test('invoice item belongs to invoice', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Service',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 18,
    ]);

    expect($item->invoice)->not->toBeNull();
    expect($item->invoice->id)->toBe($invoice->id);
    expect($item->invoice->invoice_number)->toBe('INV-001');
});

test('invoice item can have zero tax rate', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 0,
        'total' => 1000,
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Tax-free Service',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => 0,
    ]);

    expect($item->tax_rate)->toBe(0);
});

test('invoice item can have null tax rate', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'company_location_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 0,
        'total' => 1000,
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Service without tax',
        'quantity' => 1,
        'unit_price' => 1000,
        'tax_rate' => null,
    ]);

    expect($item->tax_rate)->toBeNull();
});

test('invoice item fillable attributes work correctly', function () {
    $data = [
        'invoice_id' => 1,
        'description' => 'Test Product',
        'quantity' => 3,
        'unit_price' => 2500,
        'tax_rate' => 12,
    ];

    $item = new InvoiceItem($data);

    expect($item->invoice_id)->toBe(1);
    expect($item->description)->toBe('Test Product');
    expect($item->quantity)->toBe(3);
    expect($item->unit_price)->toBe(2500);
    expect($item->tax_rate)->toBe(12);
});

test('invoice item calculates line total correctly', function () {
    $item = new InvoiceItem([
        'quantity' => 2,
        'unit_price' => 1000,
        'tax_rate' => 18,
    ]);

    $lineSubtotal = $item->quantity * $item->unit_price;
    $lineTax = ($lineSubtotal * $item->tax_rate) / 100;
    $lineTotal = $lineSubtotal + $lineTax;

    expect($lineSubtotal)->toBe(2000);
    expect($lineTax)->toBe(360);
    expect($lineTotal)->toBe(2360);
});

test('invoice item handles large quantities and prices', function () {
    $item = InvoiceItem::create([
        'invoice_id' => 1,
        'description' => 'Bulk Service',
        'quantity' => 1000,
        'unit_price' => 500000, // $5000.00 in cents
        'tax_rate' => 18,
    ]);

    expect($item->quantity)->toBe(1000);
    expect($item->unit_price)->toBe(500000);
    
    $lineSubtotal = $item->quantity * $item->unit_price;
    expect($lineSubtotal)->toBe(500000000); // $5,000,000.00 in cents
});

test('invoice item can have fractional tax rates', function () {
    $item = InvoiceItem::create([
        'invoice_id' => 1,
        'description' => 'Service with custom tax',
        'quantity' => 1,
        'unit_price' => 10000,
        'tax_rate' => 12.5,
    ]);

    expect($item->tax_rate)->toBe(12.5);
});