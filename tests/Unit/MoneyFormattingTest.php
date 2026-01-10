<?php

use App\Currency;
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
        'tax_rate' => 0,
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

test('Currency::formatAmount uses Indian grouping for INR', function () {
    $inr = Currency::INR;

    expect($inr->formatAmount(0))->toBe('₹0.00');
    expect($inr->formatAmount(100))->toBe('₹1.00');
    expect($inr->formatAmount(10000))->toBe('₹100.00');
    expect($inr->formatAmount(100000))->toBe('₹1,000.00');
    expect($inr->formatAmount(10000000))->toBe('₹1,00,000.00');
    expect($inr->formatAmount(100000000))->toBe('₹10,00,000.00');
    expect($inr->formatAmount(1000000000))->toBe('₹1,00,00,000.00');
    expect($inr->formatAmount(10000000000))->toBe('₹10,00,00,000.00');
});

test('Currency::formatAmount handles negative INR amounts', function () {
    $inr = Currency::INR;

    expect($inr->formatAmount(-10000000))->toBe('-₹1,00,000.00');
    expect($inr->formatAmount(-100))->toBe('-₹1.00');
});

test('Currency::formatAmount delegates to Money::format for non-INR currencies', function () {
    expect(Currency::USD->formatAmount(10000))->toContain('$100.00');
    expect(Currency::EUR->formatAmount(10000))->toContain('€100,00');
    expect(Currency::AED->formatAmount(10000))->toContain('100.00');
});

test('Invoice formats money with Indian grouping for INR currency', function () {
    $organization = createOrganizationWithLocation(['currency' => 'INR']);
    $customer = createCustomerWithLocation([], [], $organization);

    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $organization->id,
        'organization_location_id' => $organization->primary_location_id,
        'customer_id' => $customer->id,
        'customer_location_id' => $customer->primary_location_id,
        'invoice_number' => 'INV-INR-001',
        'status' => 'draft',
        'currency' => 'INR',
        'subtotal' => 10000000,  // 1,00,000.00
        'tax' => 1800000,        // 18,000.00
        'total' => 11800000,     // 1,18,000.00
    ]);

    expect($invoice->formatted_subtotal)->toBe('₹1,00,000.00');
    expect($invoice->formatted_tax)->toBe('₹18,000.00');
    expect($invoice->formatted_total)->toBe('₹1,18,000.00');
});

test('InvoiceTotals formats money with Indian grouping for INR currency', function () {
    $totals = new InvoiceTotals(10000000, 1800000, 11800000);

    expect($totals->formatSubtotal('INR'))->toBe('₹1,00,000.00');
    expect($totals->formatTax('INR'))->toBe('₹18,000.00');
    expect($totals->formatTotal('INR'))->toBe('₹1,18,000.00');
});
