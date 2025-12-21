<?php

use App\Enums\ResetFrequency;
use App\Models\InvoiceNumberingSeries;
use App\Models\TaxTemplate;
use App\Models\User;

/**
 * Seed rich demo data for screenshot capture.
 *
 * @return array{user: User, org: \App\Models\Organization, customers: array, invoices: array, estimates: array}
 */
function seedDemoData(): array
{
    // 1. User + Organization (Indian company, completed setup, with location)
    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Maya Sharma',
        'email' => 'demo-screenshots@example.test',
        'password' => 'password',
    ]);

    $org = createOrganizationWithLocation([
        'name' => 'Clarity Technologies',
        'company_name' => 'Clarity Technologies Pvt Ltd',
        'tax_number' => '29AAFCC1234A1ZV',
        'registration_number' => 'U72200KA2020PTC123456',
        'currency' => 'INR',
        'country_code' => 'IN',
        'financial_year_type' => 'april_march',
        'financial_year_start_month' => 4,
        'financial_year_start_day' => 1,
        'setup_completed_at' => now(),
    ], [
        'name' => 'Head Office',
        'address_line_1' => '42 MG Road, Indiranagar',
        'address_line_2' => '2nd Floor, Prestige Tower',
        'city' => 'Bangalore',
        'state' => 'Karnataka',
        'country' => 'IN',
        'postal_code' => '560038',
    ], $user);

    // 2. Customers
    $customers = [];

    // Indian customer with GSTIN
    $customers['indian'] = createCustomerWithLocation([
        'name' => 'BrightCare Health India Pvt Ltd',
    ], [
        'name' => 'Bangalore Office',
        'address_line_1' => '14th Floor, Prestige Shantiniketan',
        'address_line_2' => 'Whitefield Main Road',
        'city' => 'Bangalore',
        'state' => 'Karnataka',
        'country' => 'IN',
        'postal_code' => '560048',
        'gstin' => '29ABCDE1234F1Z5',
    ], $org);

    // UAE customer
    $customers['uae'] = createCustomerWithLocation([
        'name' => 'Sandline Pharmacy LLC',
    ], [
        'name' => 'Dubai Office',
        'address_line_1' => 'Dubai Healthcare City',
        'address_line_2' => 'Building 64, Block A',
        'city' => 'Dubai',
        'state' => 'Dubai',
        'country' => 'AE',
        'postal_code' => '505055',
    ], $org);

    // Generic customer
    $customers['generic'] = createCustomerWithLocation([
        'name' => 'ACME Manufacturing Corp',
    ], [
        'name' => 'Detroit HQ',
        'address_line_1' => '1200 Industrial Parkway',
        'city' => 'Detroit',
        'state' => 'Michigan',
        'country' => 'US',
        'postal_code' => '48201',
    ], $org);

    // 3. Tax templates
    TaxTemplate::create([
        'organization_id' => $org->id,
        'name' => 'CGST 9%',
        'type' => 'CGST',
        'rate' => 90000,
        'category' => 'standard',
        'country_code' => 'IN',
        'is_active' => true,
    ]);
    TaxTemplate::create([
        'organization_id' => $org->id,
        'name' => 'SGST 9%',
        'type' => 'SGST',
        'rate' => 90000,
        'category' => 'standard',
        'country_code' => 'IN',
        'is_active' => true,
    ]);
    TaxTemplate::create([
        'organization_id' => $org->id,
        'name' => 'IGST 18%',
        'type' => 'IGST',
        'rate' => 180000,
        'category' => 'standard',
        'country_code' => 'IN',
        'is_active' => true,
    ]);
    TaxTemplate::create([
        'organization_id' => $org->id,
        'name' => 'VAT 5%',
        'type' => 'VAT',
        'rate' => 50000,
        'category' => 'standard',
        'country_code' => 'AE',
        'is_active' => true,
    ]);

    // 4. Numbering series
    InvoiceNumberingSeries::create([
        'organization_id' => $org->id,
        'name' => 'Default Invoice Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{FY}-{SEQUENCE:4}',
        'current_number' => 6,
        'reset_frequency' => ResetFrequency::FINANCIAL_YEAR,
        'is_active' => true,
        'is_default' => true,
    ]);

    // 5. Invoices (various statuses)
    $invoices = [];

    // Paid invoice — Indian customer, GST
    $invoices['paid'] = createInvoiceWithItems([
        'invoice_number' => 'INV-2025-26-0001',
        'type' => 'invoice',
        'status' => 'paid',
        'currency' => 'INR',
        'issued_at' => now()->subMonths(2),
        'due_at' => now()->subMonth(),
        'subtotal' => 15000000,
        'tax' => 2700000,
        'total' => 17700000,
    ], [
        ['description' => 'Full Stack Web Application Development', 'quantity' => 120, 'unit_price' => 125000, 'tax_rate' => 18.00],
    ], $org, $customers['indian']);

    // Sent invoice — UAE customer
    $invoices['sent'] = createInvoiceWithItems([
        'invoice_number' => 'INV-2025-26-0002',
        'type' => 'invoice',
        'status' => 'sent',
        'currency' => 'INR',
        'issued_at' => now()->subWeeks(2),
        'due_at' => now()->addWeeks(2),
        'subtotal' => 8500000,
        'tax' => 1530000,
        'total' => 10030000,
    ], [
        ['description' => 'Mobile App Development - Phase 1', 'quantity' => 80, 'unit_price' => 100000, 'tax_rate' => 18.00],
        ['description' => 'UI/UX Design Services', 'quantity' => 10, 'unit_price' => 50000, 'tax_rate' => 18.00],
    ], $org, $customers['uae']);

    // Draft invoice — ACME customer
    $invoices['draft'] = createInvoiceWithItems([
        'invoice_number' => 'INV-2025-26-0003',
        'type' => 'invoice',
        'status' => 'draft',
        'currency' => 'INR',
        'subtotal' => 5000000,
        'tax' => 900000,
        'total' => 5900000,
    ], [
        ['description' => 'Cloud Infrastructure Consulting', 'quantity' => 40, 'unit_price' => 125000, 'tax_rate' => 18.00],
    ], $org, $customers['generic']);

    // Overdue invoice
    $invoices['overdue'] = createInvoiceWithItems([
        'invoice_number' => 'INV-2025-26-0004',
        'type' => 'invoice',
        'status' => 'sent',
        'currency' => 'INR',
        'issued_at' => now()->subMonths(3),
        'due_at' => now()->subMonth(),
        'subtotal' => 3200000,
        'tax' => 576000,
        'total' => 3776000,
    ], [
        ['description' => 'API Integration & Testing', 'quantity' => 32, 'unit_price' => 100000, 'tax_rate' => 18.00],
    ], $org, $customers['indian']);

    // Another paid invoice for volume
    $invoices['paid2'] = createInvoiceWithItems([
        'invoice_number' => 'INV-2025-26-0005',
        'type' => 'invoice',
        'status' => 'paid',
        'currency' => 'INR',
        'issued_at' => now()->subMonths(4),
        'due_at' => now()->subMonths(3),
        'subtotal' => 2400000,
        'tax' => 432000,
        'total' => 2832000,
    ], [
        ['description' => 'DevOps Setup & CI/CD Pipeline', 'quantity' => 24, 'unit_price' => 100000, 'tax_rate' => 18.00],
    ], $org, $customers['generic']);

    // 6. Estimates
    $estimates = [];

    $estimates['sent'] = createInvoiceWithItems([
        'invoice_number' => 'EST-2025-26-0001',
        'type' => 'estimate',
        'status' => 'sent',
        'currency' => 'INR',
        'issued_at' => now()->subWeek(),
        'due_at' => now()->addMonth(),
        'subtotal' => 12000000,
        'tax' => 2160000,
        'total' => 14160000,
    ], [
        ['description' => 'E-Commerce Platform Development', 'quantity' => 200, 'unit_price' => 50000, 'tax_rate' => 18.00],
        ['description' => 'Payment Gateway Integration', 'quantity' => 20, 'unit_price' => 100000, 'tax_rate' => 18.00],
    ], $org, $customers['indian']);

    $estimates['draft'] = createInvoiceWithItems([
        'invoice_number' => 'EST-2025-26-0002',
        'type' => 'estimate',
        'status' => 'draft',
        'currency' => 'INR',
        'subtotal' => 4500000,
        'tax' => 810000,
        'total' => 5310000,
    ], [
        ['description' => 'Mobile App Redesign', 'quantity' => 60, 'unit_price' => 75000, 'tax_rate' => 18.00],
    ], $org, $customers['uae']);

    return compact('user', 'org', 'customers', 'invoices', 'estimates');
}

// ─── Test 1: Public pages (no auth, no data) ───

it('captures public page screenshots', function () {
    $demoDir = base_path('tests/Browser/Screenshots/demo');
    if (! is_dir($demoDir)) {
        mkdir($demoDir, 0755, true);
    }

    $this->visit('/login')
        ->assertSee('Log in')
        ->screenshot(fullPage: true, filename: 'demo/01-login');

    $this->visit('/register')
        ->assertSee('Register')
        ->screenshot(fullPage: true, filename: 'demo/02-register');
});

// ─── Test 2: Authenticated pages (seed data, actingAs user) ───

it('captures authenticated page screenshots', function () {
    $demoDir = base_path('tests/Browser/Screenshots/demo');
    if (! is_dir($demoDir)) {
        mkdir($demoDir, 0755, true);
    }

    $data = seedDemoData();
    $this->actingAs($data['user']);

    $this->visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->screenshot(fullPage: true, filename: 'demo/03-dashboard');

    $this->visit('/organizations')
        ->assertPathIs('/organizations')
        ->screenshot(fullPage: true, filename: 'demo/04-organizations');

    $this->visit('/customers')
        ->assertPathIs('/customers')
        ->screenshot(fullPage: true, filename: 'demo/05-customers');

    $this->visit('/invoices')
        ->assertPathIs('/invoices')
        ->screenshot(fullPage: true, filename: 'demo/06-invoice-list');

    $this->visit('/invoices/create')
        ->assertPathIs('/invoices/create')
        ->screenshot(fullPage: true, filename: 'demo/07-invoice-create');

    $this->visit('/estimates/create')
        ->assertPathIs('/estimates/create')
        ->screenshot(fullPage: true, filename: 'demo/08-estimate-create');

    $this->visit('/numbering-series')
        ->assertPathIs('/numbering-series')
        ->screenshot(fullPage: true, filename: 'demo/09-numbering-series');
});

// ─── Test 3: Public document views (seed data, no auth) ───

it('captures public document view screenshots', function () {
    $demoDir = base_path('tests/Browser/Screenshots/demo');
    if (! is_dir($demoDir)) {
        mkdir($demoDir, 0755, true);
    }

    $data = seedDemoData();

    // Public invoice view (the sent invoice has realistic data)
    $invoice = $data['invoices']['sent'];
    $this->visit("/invoices/view/{$invoice->ulid}")
        ->assertSee($invoice->invoice_number)
        ->screenshot(fullPage: true, filename: 'demo/10-public-invoice');

    // Public estimate view
    $estimate = $data['estimates']['sent'];
    $this->visit("/estimates/view/{$estimate->ulid}")
        ->assertSee($estimate->invoice_number)
        ->screenshot(fullPage: true, filename: 'demo/11-public-estimate');
});
