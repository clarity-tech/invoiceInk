<?php

use App\Models\Invoice;

test('can create invoice with required fields', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 10000,
        'tax' => 1800,
        'total' => 11800,
    ]);

    expect($invoice->type)->toBe('invoice');
    expect($invoice->invoice_number)->toBe('INV-001');
    expect($invoice->status)->toBe('draft');
    expect($invoice->subtotal)->toBe(10000);
    expect($invoice->tax)->toBe(1800);
    expect($invoice->total)->toBe(11800);
});

test('invoice automatically generates ULID on creation', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ]);

    expect($invoice->ulid)->not->toBeNull();
    expect(strlen($invoice->ulid))->toBe(26); // ULID length
});

test('invoice can be created as estimate', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-001',
        'status' => 'draft',
        'subtotal' => 5000,
        'tax' => 900,
        'total' => 5900,
    ]);

    expect($invoice->type)->toBe('estimate');
    expect($invoice->isEstimate())->toBeTrue();
    expect($invoice->isInvoice())->toBeFalse();
});

test('invoice has organization location relationship', function () {
    $organization = createOrganizationWithLocation([], [
        'name' => 'Organization HQ',
        'address_line_1' => '123 Business St',
        'city' => 'Business City',
        'state' => 'Business State',
        'country' => 'Test Country',
        'postal_code' => '12345',
    ]);

    $customer = createCustomerWithLocation([], [], $organization);

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ], null, $organization, $customer);

    expect($invoice->organizationLocation)->not->toBeNull();
    expect($invoice->organizationLocation->name)->toBe('Organization HQ');
});

test('invoice has customer location relationship', function () {
    $organization = createOrganizationWithLocation();

    $customer = createCustomerWithLocation([], [
        'name' => 'Customer Office',
        'address_line_1' => '456 Client Ave',
        'city' => 'Client City',
        'state' => 'Client State',
        'country' => 'Test Country',
        'postal_code' => '54321',
    ], $organization);

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ], null, $organization, $customer);

    expect($invoice->customerLocation)->not->toBeNull();
    expect($invoice->customerLocation->name)->toBe('Customer Office');
});

test('invoice has many items relationship', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 2000,
        'tax' => 360,
        'total' => 2360,
    ], [
        [
            'description' => 'Service 1',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 18,
        ],
        [
            'description' => 'Service 2',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 18,
        ],
    ]);

    expect($invoice->items()->count())->toBe(2);
    expect($invoice->items->first()->description)->toBe('Service 1');
});

test('invoice type checking methods work correctly', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ]);

    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'invoice_number' => 'EST-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ]);

    expect($invoice->isInvoice())->toBeTrue();
    expect($invoice->isEstimate())->toBeFalse();
    expect($estimate->isInvoice())->toBeFalse();
    expect($estimate->isEstimate())->toBeTrue();
});

test('invoice dates are cast to Carbon instances', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'issued_at' => '2025-01-01',
        'due_at' => '2025-01-31',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ]);

    expect($invoice->issued_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($invoice->due_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($invoice->issued_at->format('Y-m-d'))->toBe('2025-01-01');
    expect($invoice->due_at->format('Y-m-d'))->toBe('2025-01-31');
});

test('invoice can be created without optional dates', function () {
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
    ]);

    expect($invoice->issued_at)->toBeNull();
    expect($invoice->due_at)->toBeNull();
});

test('invoice fillable attributes work correctly', function () {
    $data = [
        'type' => 'invoice',
        'ulid' => 'test-ulid',
        'organization_id' => 1,
        'organization_location_id' => 1,
        'customer_id' => 1,
        'customer_location_id' => 2,
        'invoice_number' => 'INV-001',
        'status' => 'sent',
        'currency' => 'INR',
        'exchange_rate' => 1.000000,
        'issued_at' => now(),
        'due_at' => now()->addDays(30),
        'subtotal' => 1000,
        'tax' => 180,
        'total' => 1180,
        'email_recipients' => ['test@example.com'],
    ];

    $invoice = new Invoice($data);

    expect($invoice->type)->toBe('invoice');
    expect($invoice->ulid)->toBe('test-ulid');
    expect($invoice->organization_id)->toBe(1);
    expect($invoice->organization_location_id)->toBe(1);
    expect($invoice->customer_id)->toBe(1);
    expect($invoice->customer_location_id)->toBe(2);
    expect($invoice->invoice_number)->toBe('INV-001');
    expect($invoice->status)->toBe('sent');
    expect($invoice->currency)->toBe('INR');
    expect($invoice->exchange_rate)->toBe('1.000000');
    expect($invoice->subtotal)->toBe(1000);
    expect($invoice->tax)->toBe(180);
    expect($invoice->total)->toBe(1180);
    expect($invoice->email_recipients)->toBe(['test@example.com']);
});

test('invoice uses HasUlids trait', function () {
    $invoice = new Invoice;
    $traits = class_uses($invoice);

    expect($traits)->toHaveKey(\Illuminate\Database\Eloquent\Concerns\HasUlids::class);
});

test('invoice unique ids configuration', function () {
    $invoice = new Invoice;

    expect($invoice->uniqueIds())->toBe(['ulid']);
});
