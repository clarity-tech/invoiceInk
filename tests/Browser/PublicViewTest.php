<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;

uses(RefreshDatabase::class);

test('user can view public invoice page', function () {
    // Create test data
    $company = Company::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $invoice = Invoice::factory()->create([
        'type' => 'invoice',
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-2025-01-0001',
        'status' => 'sent',
        'subtotal' => 100000, // $1000 in cents
        'tax' => 18000,       // $180 in cents
        'total' => 118000,    // $1180 in cents
    ]);

    // Create invoice items
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Web Development Services',
        'quantity' => 10,
        'unit_price' => 10000, // $100 in cents
        'tax_rate' => 18,
    ]);

    $this->browse(function (Browser $browser) use ($invoice) {
        $browser->visit("/invoices/{$invoice->ulid}")
            ->assertSee('Invoice')
            ->assertSee($invoice->invoice_number)
            ->assertSee('Web Development Services')
            ->assertSee($invoice->companyLocation->name)
            ->assertSee($invoice->customerLocation->name)
            ->screenshot('public_invoice_view');
    });
});

test('user can view public estimate page', function () {
    // Create test data
    $company = Company::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $estimate = Invoice::factory()->create([
        'type' => 'estimate',
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'EST-2025-01-0001',
        'status' => 'sent',
        'subtotal' => 150000, // $1500 in cents
        'tax' => 27000,       // $270 in cents
        'total' => 177000,    // $1770 in cents
    ]);

    // Create estimate items
    InvoiceItem::factory()->create([
        'invoice_id' => $estimate->id,
        'description' => 'Mobile App Development',
        'quantity' => 5,
        'unit_price' => 30000, // $300 in cents
        'tax_rate' => 18,
    ]);

    $this->browse(function (Browser $browser) use ($estimate) {
        $browser->visit("/estimates/{$estimate->ulid}")
            ->assertSee('Estimate')
            ->assertSee($estimate->invoice_number)
            ->assertSee('Mobile App Development')
            ->assertSee($estimate->companyLocation->name)
            ->assertSee($estimate->customerLocation->name)
            ->screenshot('public_estimate_view');
    });
});

test('public invoice page displays all required details', function () {
    // Create test data with detailed information
    $company = Company::factory()->withLocation()->create([
        'name' => 'Tech Solutions Ltd',
    ]);

    $customer = Customer::factory()->withLocation()->create([
        'name' => 'Client Corp Inc',
    ]);

    $invoice = Invoice::factory()->create([
        'type' => 'invoice',
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-2025-01-0002',
        'status' => 'sent',
        'issued_at' => now(),
        'due_at' => now()->addDays(30),
        'subtotal' => 200000,
        'tax' => 36000,
        'total' => 236000,
    ]);

    // Create multiple invoice items
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Frontend Development',
        'quantity' => 20,
        'unit_price' => 5000,
        'tax_rate' => 18,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Backend API Development',
        'quantity' => 15,
        'unit_price' => 6667,
        'tax_rate' => 18,
    ]);

    $this->browse(function (Browser $browser) use ($invoice) {
        $browser->visit("/invoices/{$invoice->ulid}")
            ->screenshot('public_invoice_detailed_header')
            ->assertSee('Tech Solutions Ltd')
            ->assertSee('Client Corp Inc')
            ->assertSee('INV-2025-01-0002')
            ->assertSee('Frontend Development')
            ->assertSee('Backend API Development')
            ->screenshot('public_invoice_detailed_items')
            ->assertSee('Subtotal')
            ->assertSee('Tax')
            ->assertSee('Total')
            ->screenshot('public_invoice_detailed_totals');
    });
});

test('public invoice page shows company and customer addresses', function () {
    // Create test data with specific address information
    $company = Company::factory()->withLocation()->create();
    $company->primaryLocation->update([
        'name' => 'Corporate Headquarters',
        'address_line_1' => '123 Business Park',
        'address_line_2' => 'Suite 100',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'country' => 'India',
        'postal_code' => '400001',
        'gstin' => '27ABCDE1234F1Z5',
    ]);

    $customer = Customer::factory()->withLocation()->create();
    $customer->primaryLocation->update([
        'name' => 'Client Main Office',
        'address_line_1' => '456 Commerce Street',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'country' => 'India',
        'postal_code' => '110001',
    ]);

    $invoice = Invoice::factory()->create([
        'type' => 'invoice',
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Consulting Services',
        'quantity' => 1,
        'unit_price' => 50000,
        'tax_rate' => 18,
    ]);

    $this->browse(function (Browser $browser) use ($invoice) {
        $browser->visit("/invoices/{$invoice->ulid}")
            ->screenshot('public_invoice_addresses_full')
            ->assertSee('Corporate Headquarters')
            ->assertSee('123 Business Park')
            ->assertSee('Suite 100')
            ->assertSee('Mumbai')
            ->assertSee('Maharashtra')
            ->assertSee('27ABCDE1234F1Z5')
            ->screenshot('public_invoice_company_address')
            ->assertSee('Client Main Office')
            ->assertSee('456 Commerce Street')
            ->assertSee('Delhi')
            ->screenshot('public_invoice_customer_address');
    });
});

test('public invoice page handles different tax scenarios', function () {
    // Create test data
    $company = Company::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $invoice = Invoice::factory()->create([
        'type' => 'invoice',
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    // Create items with different tax rates
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Standard Service (18% GST)',
        'quantity' => 1,
        'unit_price' => 10000,
        'tax_rate' => 18,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Reduced Rate Service (5% GST)',
        'quantity' => 2,
        'unit_price' => 5000,
        'tax_rate' => 5,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Tax-Free Service',
        'quantity' => 1,
        'unit_price' => 8000,
        'tax_rate' => 0,
    ]);

    $this->browse(function (Browser $browser) use ($invoice) {
        $browser->visit("/invoices/{$invoice->ulid}")
            ->screenshot('public_invoice_mixed_tax_rates')
            ->assertSee('Standard Service (18% GST)')
            ->assertSee('Reduced Rate Service (5% GST)')
            ->assertSee('Tax-Free Service')
            ->screenshot('public_invoice_different_tax_items');
    });
});
