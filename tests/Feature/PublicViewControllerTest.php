<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\Organization;
use App\ValueObjects\EmailCollection;

beforeEach(function () {
    // Create test organization with location
    $this->organization = createOrganizationWithLocation([
        'name' => 'Test Organization Ltd',
        'company_name' => 'Test Company Ltd',
        'phone' => '+1234567890',
        'emails' => new EmailCollection(['company@test.com']),
    ], [
        'name' => 'Company HQ',
        'gstin' => '27AAAAA0000A1Z5',
        'address_line_1' => '123 Business Street',
        'address_line_2' => 'Suite 100',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'country' => 'India',
        'postal_code' => '400001',
    ]);

    $this->organizationLocation = $this->organization->primaryLocation;

    // Create test customer with location
    $this->customer = createCustomerWithLocation([
        'name' => 'Test Customer Corp',
        'phone' => '+9876543210',
        'emails' => new EmailCollection(['customer@test.com']),
    ], [
        'name' => 'Customer Office',
        'gstin' => '29BBBBB1111B2Z6',
        'address_line_1' => '456 Client Avenue',
        'city' => 'Bangalore',
        'state' => 'Karnataka',
        'country' => 'India',
        'postal_code' => '560001',
    ], $this->organization);

    $this->customerLocation = $this->customer->primaryLocation;
});

test('can view public invoice page', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'organization_location_id' => $this->organizationLocation->id,
        'customer_id' => $this->customer->id,
        'customer_location_id' => $this->customerLocation->id,
        'currency' => 'INR',
        'exchange_rate' => 1.000000,
        'email_recipients' => ['customer@test.com'],
        'invoice_number' => 'INV-2025-001',
        'status' => 'sent',
        'issued_at' => now(),
        'due_at' => now()->addDays(30),
        'subtotal' => 10000,
        'tax' => 1800,
        'total' => 11800,
    ]);

    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Website Development',
        'quantity' => 1,
        'unit_price' => 10000,
        'tax_rate' => 18,
    ]);

    $response = $this->get("/invoices/{$invoice->ulid}");

    $response->assertStatus(200);
    $response->assertViewIs('public.invoice');
    $response->assertViewHas('invoice');
    $response->assertSee('INVOICE');
    $response->assertSee('INV-2025-001');
    $response->assertSee('Test Company Ltd');
    $response->assertSee('Test Customer Corp');
    $response->assertSee('Website Development');
    $response->assertSee('₹118.00'); // Total formatted
});

test('can view public estimate page', function () {
    $estimate = Invoice::create([
        'type' => 'estimate',
        'organization_id' => $this->organization->id,
        'organization_location_id' => $this->organizationLocation->id,
        'customer_id' => $this->customer->id,
        'customer_location_id' => $this->customerLocation->id,
        'currency' => 'INR',
        'exchange_rate' => 1.000000,
        'email_recipients' => ['customer@test.com'],
        'invoice_number' => 'EST-2025-001',
        'status' => 'sent',
        'issued_at' => now(),
        'due_at' => now()->addDays(30),
        'subtotal' => 5000,
        'tax' => 900,
        'total' => 5900,
    ]);

    InvoiceItem::create([
        'invoice_id' => $estimate->id,
        'description' => 'Consulting Services',
        'quantity' => 2,
        'unit_price' => 2500,
        'tax_rate' => 18,
    ]);

    $response = $this->get("/estimates/{$estimate->ulid}");

    $response->assertStatus(200);
    $response->assertViewIs('public.estimate');
    $response->assertViewHas('estimate');
    $response->assertSee('ESTIMATE');
    $response->assertSee('EST-2025-001');
    $response->assertSee('Test Company Ltd');
    $response->assertSee('Test Customer Corp');
    $response->assertSee('Consulting Services');
    $response->assertSee('₹59.00'); // Total formatted
});

test('returns 404 for non-existent invoice', function () {
    $response = $this->get('/invoices/non-existent-ulid');

    $response->assertStatus(404);
});

test('returns 404 for non-existent estimate', function () {
    $response = $this->get('/estimates/non-existent-ulid');

    $response->assertStatus(404);
});

test('returns 404 when accessing invoice with estimate ULID', function () {
    $estimate = Invoice::create([
        'type' => 'estimate',
        'organization_id' => $this->organization->id,
        'organization_location_id' => $this->organizationLocation->id,
        'customer_id' => $this->customer->id,
        'customer_location_id' => $this->customerLocation->id,
        'currency' => 'INR',
        'exchange_rate' => 1.000000,
        'email_recipients' => ['customer@test.com'],
        'invoice_number' => 'EST-2025-002',
        'status' => 'sent',
        'subtotal' => 3000,
        'tax' => 540,
        'total' => 3540,
    ]);

    $response = $this->get("/invoices/{$estimate->ulid}");

    $response->assertStatus(404);
});

test('returns 404 when accessing estimate with invoice ULID', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'organization_location_id' => $this->organizationLocation->id,
        'customer_id' => $this->customer->id,
        'customer_location_id' => $this->customerLocation->id,
        'currency' => 'INR',
        'exchange_rate' => 1.000000,
        'email_recipients' => ['customer@test.com'],
        'invoice_number' => 'INV-2025-002',
        'status' => 'sent',
        'subtotal' => 4000,
        'tax' => 720,
        'total' => 4720,
    ]);

    $response = $this->get("/estimates/{$invoice->ulid}");

    $response->assertStatus(404);
});

test('public invoice page displays all address details', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'organization_location_id' => $this->organizationLocation->id,
        'customer_id' => $this->customer->id,
        'customer_location_id' => $this->customerLocation->id,
        'currency' => 'INR',
        'exchange_rate' => 1.000000,
        'email_recipients' => ['customer@test.com'],
        'invoice_number' => 'INV-2025-003',
        'status' => 'sent',
        'subtotal' => 2000,
        'tax' => 360,
        'total' => 2360,
    ]);

    $response = $this->get("/invoices/{$invoice->ulid}");

    $response->assertStatus(200);
    $response->assertSee('123 Business Street');
    $response->assertSee('Suite 100');
    $response->assertSee('Mumbai');
    $response->assertSee('Maharashtra');
    $response->assertSee('400001');
    $response->assertSee('27AAAAA0000A1Z5'); // Company GSTIN

    $response->assertSee('456 Client Avenue');
    $response->assertSee('Bangalore');
    $response->assertSee('Karnataka');
    $response->assertSee('560001');
    $response->assertSee('29BBBBB1111B2Z6'); // Customer GSTIN
});

test('public invoice page displays multiple items correctly', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'organization_location_id' => $this->organizationLocation->id,
        'customer_id' => $this->customer->id,
        'customer_location_id' => $this->customerLocation->id,
        'currency' => 'INR',
        'exchange_rate' => 1.000000,
        'email_recipients' => ['customer@test.com'],
        'invoice_number' => 'INV-2025-004',
        'status' => 'paid',
        'subtotal' => 15000,
        'tax' => 2340,
        'total' => 17340,
    ]);

    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'Web Development',
        'quantity' => 1,
        'unit_price' => 8000,
        'tax_rate' => 18,
    ]);

    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => 'SEO Optimization',
        'quantity' => 2,
        'unit_price' => 3500,
        'tax_rate' => 12,
    ]);

    $response = $this->get("/invoices/{$invoice->ulid}");

    $response->assertStatus(200);
    $response->assertSee('Web Development');
    $response->assertSee('SEO Optimization');
    $response->assertSee('18%'); // First item tax rate
    $response->assertSee('12%'); // Second item tax rate
    $response->assertSee('₹80.00'); // First item unit price
    $response->assertSee('₹35.00'); // Second item unit price
});

test('can download invoice PDF', function () {
    $invoice = Invoice::create([
        'type' => 'invoice',
        'organization_id' => $this->organization->id,
        'organization_location_id' => $this->organizationLocation->id,
        'customer_id' => $this->customer->id,
        'customer_location_id' => $this->customerLocation->id,
        'currency' => 'INR',
        'exchange_rate' => 1.000000,
        'email_recipients' => ['customer@test.com'],
        'invoice_number' => 'INV-2025-005',
        'status' => 'sent',
        'subtotal' => 5000,
        'tax' => 900,
        'total' => 5900,
    ]);

    // Mock the PdfService to avoid Puppeteer dependency
    $this->mock(\App\Services\PdfService::class, function ($mock) {
        $mock->shouldReceive('downloadInvoicePdf')
            ->once()
            ->andReturn(response('fake-pdf-content', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice-INV-2025-005.pdf"',
            ]));
    });

    $response = $this->get("/invoices/{$invoice->ulid}/pdf");

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('can download estimate PDF', function () {
    $estimate = Invoice::create([
        'type' => 'estimate',
        'organization_id' => $this->organization->id,
        'organization_location_id' => $this->organizationLocation->id,
        'customer_id' => $this->customer->id,
        'customer_location_id' => $this->customerLocation->id,
        'currency' => 'INR',
        'exchange_rate' => 1.000000,
        'email_recipients' => ['customer@test.com'],
        'invoice_number' => 'EST-2025-003',
        'status' => 'sent',
        'subtotal' => 7500,
        'tax' => 1350,
        'total' => 8850,
    ]);

    // Mock the PdfService
    $this->mock(\App\Services\PdfService::class, function ($mock) {
        $mock->shouldReceive('downloadEstimatePdf')
            ->once()
            ->andReturn(response('fake-pdf-content', 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="estimate-EST-2025-003.pdf"',
            ]));
    });

    $response = $this->get("/estimates/{$estimate->ulid}/pdf");

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('PDF download returns 404 for non-existent invoice', function () {
    $response = $this->get('/invoices/non-existent-ulid/pdf');

    $response->assertStatus(404);
});

test('PDF download returns 404 for non-existent estimate', function () {
    $response = $this->get('/estimates/non-existent-ulid/pdf');

    $response->assertStatus(404);
});
