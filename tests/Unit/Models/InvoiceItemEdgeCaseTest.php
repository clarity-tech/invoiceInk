<?php

use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('invoice item handles very large numbers', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-LARGE',
    ], [
        [
            'description' => 'Large Amount Service',
            'quantity' => 999999,
            'unit_price' => 999999999, // $9,999,999.99 in cents
            'tax_rate' => 99.99,
        ]
    ]);

    $item = $invoice->items->first();
    
    expect($item->quantity)->toBe(999999);
    expect($item->unit_price)->toBe(999999999);
    expect((float) $item->tax_rate)->toBe(99.99);
});

test('invoice item line total calculation with zero values', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-ZERO',
    ], [
        [
            'description' => 'Free Service',
            'quantity' => 1,
            'unit_price' => 0,
            'tax_rate' => 0,
        ]
    ]);

    $item = $invoice->items->first();
    
    expect($item->quantity)->toBe(1);
    expect($item->unit_price)->toBe(0);
    expect((float) $item->tax_rate)->toBe(0.0);
});

test('invoice item line total calculation with null tax rate', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-NULL-TAX',
    ], [
        [
            'description' => 'No Tax Service',
            'quantity' => 2,
            'unit_price' => 10000, // $100 in cents
            'tax_rate' => null,
        ]
    ]);

    $item = $invoice->items->first();
    
    expect($item->quantity)->toBe(2);
    expect($item->unit_price)->toBe(10000);
    expect($item->tax_rate)->toBeNull();
});

test('invoice item can be updated after creation', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-UPDATE-ITEM',
    ], [
        [
            'description' => 'Original Service',
            'quantity' => 1,
            'unit_price' => 5000,
            'tax_rate' => 10,
        ]
    ]);

    $item = $invoice->items->first();
    
    $item->update([
        'description' => 'Updated Service',
        'quantity' => 3,
        'unit_price' => 7500,
        'tax_rate' => 15,
    ]);

    $item->refresh();
    
    expect($item->description)->toBe('Updated Service');
    expect($item->quantity)->toBe(3);
    expect($item->unit_price)->toBe(7500);
    expect((float) $item->tax_rate)->toBe(15.0);
});

test('invoice item belongs to correct invoice after creation', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-RELATIONSHIP',
    ], [
        [
            'description' => 'Relationship Test Service',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 5,
        ]
    ]);

    $item = $invoice->items->first();
    
    expect($item->invoice_id)->toBe($invoice->id);
    expect($item->invoice)->toBeInstanceOf(\App\Models\Invoice::class);
    expect($item->invoice->id)->toBe($invoice->id);
});