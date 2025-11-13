<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\PublicInvoice;

// Note: RefreshDatabase is already applied to all Browser tests via Pest.php configuration

test('test basic route access', function () {
    $this->browse(function (Browser $browser) {
        // Test if basic route works
        $browser->visit('/')
            ->screenshot('basic_route_test');
    });
});

test('simple public route test', function () {
    // Create test data inline - self-contained and clean
    $organization = Organization::factory()->withLocation()->create([
        'name' => 'Simple Test Company',
        'company_name' => 'Simple Test Company Ltd.',
        'currency' => 'INR',
    ]);

    $customer = Customer::factory()->withLocation()->for($organization)->create([
        'name' => 'Simple Test Customer',
    ]);

    $invoice = Invoice::factory()
        ->invoice()
        ->sent()
        ->for($organization)
        ->for($customer)
        ->withLocations()
        ->create([
            'invoice_number' => 'SIMPLE-001',
            'subtotal' => 100000, // ₹1000
            'tax' => 18000,       // ₹180 (18%)
            'total' => 118000,    // ₹1180
        ]);

    // Add invoice item
    InvoiceItem::factory()->for($invoice)->create([
        'description' => 'Web Development Services',
        'quantity' => 10,
        'unit_price' => 10000, // ₹100
        'tax_rate' => 18.00,
    ]);

    // Verify the invoice is publicly accessible
    $response = $this->get("/invoices/{$invoice->ulid}");
    expect($response->status())->toBe(200);

    $this->browse(function (Browser $browser) use ($invoice) {
        // Use PublicInvoice page object for clean navigation
        $publicInvoicePage = new PublicInvoice($invoice->ulid);

        $browser->visit($publicInvoicePage)
            ->assertSee($invoice->invoice_number)
            ->assertSee('Web Development Services');
    });
});
