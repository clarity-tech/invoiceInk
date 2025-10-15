<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\PublicInvoice;
use Tests\Browser\Pages\PublicEstimate;

// Note: RefreshDatabase is already applied to all Browser tests via Pest.php configuration

test('user can view public invoice page', function () {
    // Create invoice data inline - self-contained and reliable
    $organization = Organization::factory()->withLocation()->create([
        'name' => 'Public Test Company',
        'company_name' => 'Public Test Company Ltd.',
        'currency' => 'INR',
    ]);
    
    $customer = Customer::factory()->withLocation()->for($organization)->create([
        'name' => 'Public Test Customer',
    ]);
    
    $invoice = Invoice::factory()
        ->invoice()
        ->sent()
        ->for($organization)
        ->for($customer)
        ->withLocations()
        ->create([
            'invoice_number' => 'PUB-INV-001',
            'subtotal' => 100000, // ₹1000
            'tax' => 18000,       // ₹180 (18%)
            'total' => 118000,    // ₹1180
        ]);
    
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Web Development Services',
        'quantity' => 10,
        'unit_price' => 10000, // ₹100
        'tax_rate' => 18.00,
    ]);

    $this->browse(function (Browser $browser) use ($invoice, $organization, $customer) {
        $publicInvoicePage = new PublicInvoice($invoice->ulid);
        
        $browser->visit($publicInvoicePage)
            ->pause(2000)
            ->screenshot('public_invoice_view')
            ->assertSee($invoice->invoice_number)
            ->assertSee('Web Development Services')
            ->assertSee($organization->name)
            ->assertSee($customer->name);
    });
});

test('user can view public estimate page', function () {
    // Create estimate data inline
    $organization = Organization::factory()->withLocation()->create([
        'name' => 'Estimate Test Company',
        'company_name' => 'Estimate Test Company Ltd.',
        'currency' => 'USD',
    ]);
    
    $customer = Customer::factory()->withLocation()->for($organization)->create([
        'name' => 'Estimate Test Customer',
    ]);
    
    $estimate = Invoice::factory()
        ->estimate()
        ->sent()
        ->for($organization)
        ->for($customer)
        ->withLocations()
        ->create([
            'invoice_number' => 'PUB-EST-001',
            'subtotal' => 50000, // $500
            'tax' => 4000,       // $40 (8%)
            'total' => 54000,    // $540
        ]);
    
    InvoiceItem::factory()->for($estimate)->create([
        'description' => 'Mobile App Development',
        'quantity' => 5,
        'unit_price' => 10000, // $100
        'tax_rate' => 8.00,
    ]);

    $this->browse(function (Browser $browser) use ($estimate, $organization, $customer) {
        $publicEstimatePage = new PublicEstimate($estimate->ulid);
        
        $browser->visit($publicEstimatePage)
            ->pause(2000)
            ->screenshot('public_estimate_view')
            ->assertSee($estimate->invoice_number)
            ->assertSee('Mobile App Development')
            ->assertSee($organization->name)
            ->assertSee($customer->name);
    });
});

test('public invoice page displays all required details', function () {
    // Create complex invoice with multiple items inline
    $organization = Organization::factory()->withLocation()->create([
        'name' => 'Complex Invoice Company',
        'currency' => 'EUR',
    ]);
    
    $customer = Customer::factory()->withLocation()->for($organization)->create([
        'name' => 'Complex Invoice Customer',
    ]);
    
    $invoice = Invoice::factory()
        ->invoice()
        ->sent()
        ->for($organization)
        ->for($customer)
        ->withLocations()
        ->create([
            'invoice_number' => 'PUB-COMPLEX-001',
            'subtotal' => 250000, // €2500
            'tax' => 47500,       // €475 (19%)
            'total' => 297500,    // €2975
        ]);
    
    // Create multiple invoice items with different tax rates
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Frontend Development',
        'quantity' => 40,
        'unit_price' => 5000, // €50
        'tax_rate' => 19.00,
    ]);
    
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Backend API Development',
        'quantity' => 20,
        'unit_price' => 7500, // €75
        'tax_rate' => 19.00,
    ]);
    
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Consulting Services',
        'quantity' => 10,
        'unit_price' => 10000, // €100
        'tax_rate' => 7.00,
    ]);

    $this->browse(function (Browser $browser) use ($invoice, $organization, $customer) {
        $publicInvoicePage = new PublicInvoice($invoice->ulid);
        
        $browser->visit($publicInvoicePage)
            ->pause(2000)
            ->screenshot('public_invoice_detailed_header')
            ->assertSee($organization->name)
            ->assertSee($customer->name)
            ->assertSee('PUB-COMPLEX-001')
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
    // Create invoice with specific location data for address testing
    $organization = Organization::factory()->withLocation([
        'name' => 'Address Test HQ',
        'address_line_1' => '123 Business Boulevard',
        'city' => 'Commerce City',
        'state' => 'Business State',
        'country' => 'Test Country',
        'postal_code' => '12345',
    ])->create([
        'name' => 'Address Test Company',
    ]);
    
    $customer = Customer::factory()->withLocation([
        'name' => 'Customer Office',
        'address_line_1' => '456 Client Avenue',
        'city' => 'Customer City',
        'state' => 'Customer State',
        'country' => 'Test Country',
        'postal_code' => '54321',
    ])->for($organization)->create([
        'name' => 'Address Test Customer',
    ]);
    
    $invoice = Invoice::factory()
        ->invoice()
        ->sent()
        ->for($organization)
        ->for($customer)
        ->withLocations()
        ->create([
            'invoice_number' => 'PUB-ADDR-001',
        ]);

    $this->browse(function (Browser $browser) use ($invoice) {
        $publicInvoicePage = new PublicInvoice($invoice->ulid);
        
        $browser->visit($publicInvoicePage)
            ->pause(2000)
            ->screenshot('public_invoice_addresses_full')
            ->assertSee('Address Test HQ')
            ->assertSee('123 Business Boulevard')
            ->assertSee('Commerce City')
            ->assertSee('Business State')
            ->screenshot('public_invoice_company_address')
            ->assertSee('Customer Office')
            ->assertSee('456 Client Avenue')
            ->assertSee('Customer City')
            ->screenshot('public_invoice_customer_address');
    });
});

test('public invoice page handles different tax scenarios', function () {
    // Create invoice with mixed tax rates inline
    $organization = Organization::factory()->withLocation()->create([
        'name' => 'Tax Test Company',
        'currency' => 'INR',
    ]);
    
    $customer = Customer::factory()->withLocation()->for($organization)->create([
        'name' => 'Tax Test Customer',
    ]);
    
    $invoice = Invoice::factory()
        ->invoice()
        ->sent()
        ->for($organization)
        ->for($customer)
        ->withLocations()
        ->create([
            'invoice_number' => 'PUB-TAX-001',
        ]);
    
    // Different tax rates for different services
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Frontend Development (18% GST)',
        'quantity' => 10,
        'unit_price' => 5000,
        'tax_rate' => 18.00,
    ]);
    
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Backend API Development (18% GST)',
        'quantity' => 8,
        'unit_price' => 6000,
        'tax_rate' => 18.00,
    ]);
    
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Consulting Services (5% GST)',
        'quantity' => 5,
        'unit_price' => 8000,
        'tax_rate' => 5.00,
    ]);

    $this->browse(function (Browser $browser) use ($invoice) {
        $publicInvoicePage = new PublicInvoice($invoice->ulid);
        
        $browser->visit($publicInvoicePage)
            ->pause(2000)
            ->screenshot('public_invoice_mixed_tax_rates')
            ->assertSee('Frontend Development (18% GST)')
            ->assertSee('Backend API Development (18% GST)')
            ->assertSee('Consulting Services (5% GST)')
            ->screenshot('public_invoice_different_tax_items');
    });
});