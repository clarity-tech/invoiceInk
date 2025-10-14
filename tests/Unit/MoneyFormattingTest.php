<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\ValueObjects\InvoiceTotals;

test('Invoice formats money correctly for different currencies', function () {
    // Create organization with USD currency
    $organization = createOrganizationWithLocation(['currency' => 'USD']);
    $customer = createCustomerWithLocation([], [], $organization);

    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $organization->id,
        'organization_location_id' => $organization->primary_location_id,
        'customer_id' => $customer->id,
        'customer_location_id' => $customer->primary_location_id,
        'invoice_number' => 'INV-USD-001',
        'status' => 'draft',
        'currency' => 'USD',
        'subtotal' => 10000, // $100.00
        'tax' => 1000,       // $10.00
        'total' => 11000,    // $110.00
    ]);

    expect($invoice->formatted_subtotal)->toContain('$100.00');
    expect($invoice->formatted_tax)->toContain('$10.00');
    expect($invoice->formatted_total)->toContain('$110.00');
});

test('Invoice formats money correctly for EUR currency', function () {
    // Create organization with EUR currency
    $organization = createOrganizationWithLocation(['currency' => 'EUR']);
    $customer = createCustomerWithLocation([], [], $organization);

    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $organization->id,
        'organization_location_id' => $organization->primary_location_id,
        'customer_id' => $customer->id,
        'customer_location_id' => $customer->primary_location_id,
        'invoice_number' => 'INV-EUR-001',
        'status' => 'draft',
        'currency' => 'EUR',
        'subtotal' => 10000, // €100.00
        'tax' => 1000,       // €10.00
        'total' => 11000,    // €110.00
    ]);

    expect($invoice->formatted_subtotal)->toContain('€100,00');
    expect($invoice->formatted_tax)->toContain('€10,00');
    expect($invoice->formatted_total)->toContain('€110,00');
});

test('Invoice formats money correctly for AED currency', function () {
    // Create organization with AED currency
    $organization = createOrganizationWithLocation(['currency' => 'AED']);
    $customer = createCustomerWithLocation([], [], $organization);

    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $organization->id,
        'organization_location_id' => $organization->primary_location_id,
        'customer_id' => $customer->id,
        'customer_location_id' => $customer->primary_location_id,
        'invoice_number' => 'INV-AED-001',
        'status' => 'draft',
        'currency' => 'AED',
        'subtotal' => 10000, // 100.00 AED
        'tax' => 1000,       // 10.00 AED
        'total' => 11000,    // 110.00 AED
    ]);

    expect($invoice->formatted_subtotal)->toContain('100.00');
    expect($invoice->formatted_tax)->toContain('10.00');
    expect($invoice->formatted_total)->toContain('110.00');
});

test('InvoiceItem formats money correctly for different currencies', function () {
    // Create organization with USD currency
    $organization = createOrganizationWithLocation(['currency' => 'USD']);
    $customer = createCustomerWithLocation([], [], $organization);

    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $organization->id,
        'organization_location_id' => $organization->primary_location_id,
        'customer_id' => $customer->id,
        'customer_location_id' => $customer->primary_location_id,
        'invoice_number' => 'INV-USD-ITEM-001',
        'status' => 'draft',
        'currency' => 'USD',
        'subtotal' => 10000,
        'tax' => 1000,
        'total' => 11000,
    ]);

    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Service',
        'quantity' => 2,
        'unit_price' => 5000, // $50.00
        'tax_rate' => 10,
    ]);

    expect($item->formatted_unit_price)->toContain('$50.00');
    expect($item->formatted_line_total)->toContain('$100.00');
});

test('InvoiceTotals formats money correctly for different currencies', function () {
    $totals = new InvoiceTotals(10000, 1000, 11000);

    expect($totals->formatSubtotal('USD'))->toContain('$100.00');
    expect($totals->formatTax('USD'))->toContain('$10.00');
    expect($totals->formatTotal('USD'))->toContain('$110.00');

    expect($totals->formatSubtotal('EUR'))->toContain('€100,00');
    expect($totals->formatTax('EUR'))->toContain('€10,00');
    expect($totals->formatTotal('EUR'))->toContain('€110,00');
});
