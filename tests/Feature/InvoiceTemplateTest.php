<?php

test('public invoice template renders with all new elements', function () {
    $organization = createOrganizationWithLocation([
        'bank_details' => [
            'account_name' => 'Test Company',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'ifsc' => 'TEST0001',
            'branch' => 'Main',
            'swift' => 'TESTSWIFT',
            'pan' => 'ABCDE1234F',
        ],
    ], [
        'gstin' => '18AAACC1234C1Z5',
    ]);

    $customer = createCustomerWithLocation([], [
        'gstin' => '36AAACR5678R1ZQ',
        'state' => 'Telangana',
    ], $organization);

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'notes' => 'Test invoice notes',
        'terms' => 'Payment due within 30 days',
        'tax_type' => 'IGST',
    ], null, $organization, $customer);

    $response = $this->get(route('invoices.public', $invoice->ulid));

    $response->assertStatus(200);
    $response->assertSee('TAX INVOICE');
    $response->assertSee('Balance Due');
    $response->assertSee('Total In Words');
    $response->assertSee('Test invoice notes');
    $response->assertSee('Test Bank');
    $response->assertSee('Account No:');
    $response->assertSee('IFSC TEST0001');
    $response->assertSee('SWIFT Code TESTSWIFT');
    $response->assertSee('PAN ABCDE1234F');
    $response->assertSee('Terms & Conditions');
    $response->assertSee('Payment due within 30 days');
    $response->assertSee('Place of Supply');
    $response->assertSee('Telangana');
    $response->assertSee('IGST');
});

test('public invoice template renders INVOICE header when no GSTIN', function () {
    $organization = createOrganizationWithLocation([], [
        'gstin' => null,
    ]);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get(route('invoices.public', $invoice->ulid));

    $response->assertStatus(200);
    $response->assertSee('INVOICE');
    $response->assertDontSee('TAX INVOICE');
});

test('public invoice template hides bank details when not configured', function () {
    $organization = createOrganizationWithLocation([
        'bank_details' => null,
    ]);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get(route('invoices.public', $invoice->ulid));

    $response->assertStatus(200);
    $response->assertDontSee('Account No:');
});

test('public invoice template renders tax breakdown when available', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'tax_type' => 'GST',
        'tax_breakdown' => ['CGST (9%)' => 900, 'SGST (9%)' => 900],
    ], null, $organization, $customer);

    $response = $this->get(route('invoices.public', $invoice->ulid));

    $response->assertStatus(200);
    $response->assertSee('CGST (9%)');
    $response->assertSee('SGST (9%)');
});

test('public estimate template renders with Zoho style', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $estimate = createInvoiceWithItems([
        'type' => 'estimate',
        'notes' => 'Estimate notes',
        'terms' => 'Valid for 30 days',
    ], null, $organization, $customer);

    $response = $this->get(route('estimates.public', $estimate->ulid));

    $response->assertStatus(200);
    $response->assertSee('ESTIMATE');
    $response->assertSee('Estimated Total');
    $response->assertSee('Total In Words');
    $response->assertSee('Estimate notes');
    $response->assertSee('Terms & Conditions');
    $response->assertSee('Valid for 30 days');
});

test('total in words displays correctly for INR invoice', function () {
    $organization = createOrganizationWithLocation([
        'currency' => 'INR',
    ]);
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'total' => 4720000,
        'currency' => 'INR',
    ], null, $organization, $customer);

    $response = $this->get(route('invoices.public', $invoice->ulid));

    $response->assertStatus(200);
    $response->assertSee('Indian Rupee Forty-Seven Thousand Two Hundred Only');
});
