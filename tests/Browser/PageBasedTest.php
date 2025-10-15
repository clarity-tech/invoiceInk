<?php

use App\Models\User;
use App\Models\Organization;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\Login;
use Tests\Browser\Pages\Dashboard;
use Tests\Browser\Pages\PublicInvoice;

// Note: RefreshDatabase is already applied to all Browser tests via Pest.php configuration

test('user can access dashboard using page approach', function () {
    // Create test user inline - clean and self-contained
    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Page Test User',
        'email' => 'pagetest@example.test', // Using .test TLD
        'password' => 'password',
        'email_verified_at' => now(),
    ]);
    
    $this->browse(function (Browser $browser) use ($user) {
        // Use proven authentication helper
        loginUserInBrowser($browser, $user);
        
        // Use Dashboard page object for clean assertions
        $dashboardPage = new Dashboard();
        
        $browser->visit($dashboardPage)
            ->screenshot('page_dashboard_success');
    });
});

test('public invoice view using page approach', function () {
    // Create complete invoice data inline - no external dependencies
    $organization = Organization::factory()->withLocation()->create([
        'name' => 'Page Test Company',
        'company_name' => 'Page Test Company Ltd.',
        'currency' => 'INR',
    ]);
    
    $customer = Customer::factory()->withLocation()->for($organization)->create([
        'name' => 'Page Test Customer',
    ]);
    
    $invoice = Invoice::factory()
        ->invoice()
        ->sent()
        ->for($organization)
        ->for($customer)
        ->withLocations()
        ->create([
            'invoice_number' => 'PAGE-001',
            'subtotal' => 100000, // ₹1000
            'tax' => 18000,       // ₹180 (18%)
            'total' => 118000,    // ₹1180
        ]);
    
    // Add invoice item
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Page-Based Development Services',
        'quantity' => 5,
        'unit_price' => 20000, // ₹200
        'tax_rate' => 18.00,
    ]);

    $this->browse(function (Browser $browser) use ($invoice) {
        // Use PublicInvoice page object for clean invoice viewing
        $publicInvoicePage = new PublicInvoice($invoice->ulid);
        
        $browser->visit($publicInvoicePage)
            ->pause(2000) // Wait for page to load
            ->screenshot('page_public_invoice_debug');
            
        // For now, just verify we can visit the page - the Page object handles URL structure
        // We can add specific assertions once we know what's on the page
    });
});