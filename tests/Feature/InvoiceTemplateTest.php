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
        'tax_id' => '18AAACC1234C1Z5',
    ]);

    $customer = createCustomerWithLocation([], [
        'tax_id' => '36AAACR5678R1ZQ',
        'state' => 'Telangana',
    ], $organization);

    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'notes' => 'Test invoice notes',
        'terms' => 'Payment due within 30 days',
        'tax_type' => 'IGST',
        'place_of_supply' => '36', // Telangana
        'order_number' => 'PO-2026-4471',
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
    $response->assertSee('Order Number');
    $response->assertSee('PO-2026-4471');
});

test('public invoice template renders INVOICE header when no GSTIN', function () {
    $organization = createOrganizationWithLocation([], [
        'tax_id' => null,
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

test('public invoice renders TRN label for UAE organization location', function () {
    $organization = createOrganizationWithLocation([
        'currency' => 'AED',
        'country_code' => 'AE',
    ], [
        'country' => 'AE',
        'tax_id' => '100123456700003',
    ]);
    $customer = createCustomerWithLocation([
        'currency' => 'AED',
    ], [
        'country' => 'AE',
        'tax_id' => '100987654300003',
    ], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get(route('invoices.public', $invoice->ulid));

    $response->assertStatus(200);
    $response->assertSee('TRN 100123456700003', false);
    $response->assertSee('TRN 100987654300003', false);
    $response->assertDontSee('GSTIN');
});

test('public invoice does not show TAX INVOICE header for non-Indian organization even with a tax id', function () {
    $organization = createOrganizationWithLocation([
        'currency' => 'AED',
        'country_code' => 'AE',
    ], [
        'country' => 'AE',
        'tax_id' => '100123456700003',
    ]);
    $customer = createCustomerWithLocation([
        'currency' => 'AED',
    ], [], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get(route('invoices.public', $invoice->ulid));

    $response->assertStatus(200);
    $response->assertSee('INVOICE');
    $response->assertDontSee('TAX INVOICE');
});

test('public invoice hides Place of Supply for non-Indian customer', function () {
    $organization = createOrganizationWithLocation([
        'currency' => 'AED',
        'country_code' => 'AE',
    ], [
        'country' => 'AE',
    ]);
    $customer = createCustomerWithLocation([
        'currency' => 'AED',
    ], [
        'country' => 'AE',
        'state' => 'Dubai',
    ], $organization);
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
    ], null, $organization, $customer);

    $response = $this->get(route('invoices.public', $invoice->ulid));

    $response->assertStatus(200);
    $response->assertDontSee('Place of Supply');
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
