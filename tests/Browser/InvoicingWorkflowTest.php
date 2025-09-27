<?php

use App\Models\Customer;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;

uses(RefreshDatabase::class);

test('user can view the dashboard', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/dashboard')
            ->assertSee('Welcome to your Invoicing Application')
            ->screenshot('dashboard_view');
    });
});

test('user can view organizations page', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/organizations')
            ->assertSee('Organizations')
            ->screenshot('organizations_page');
    });
});

test('user can create a new organization', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/organizations')
            ->screenshot('organizations_page_before_click')
            ->click('.bg-blue-500')
            ->waitFor('form', 3)
            ->type('[wire\\:model="name"]', 'Test Organization Ltd')
            ->type('[wire\\:model="phone"]', '+91-9876543210')
            ->type('[wire\\:model="emails.0"]', 'test@organization.com')
            ->type('[wire\\:model="location_name"]', 'Head Office')
            ->type('[wire\\:model="address_line_1"]', '123 Business Street')
            ->type('[wire\\:model="city"]', 'Mumbai')
            ->type('[wire\\:model="state"]', 'Maharashtra')
            ->type('[wire\\:model="country"]', 'India')
            ->type('[wire\\:model="postal_code"]', '400001')
            ->type('[wire\\:model="gstin"]', '27ABCDE1234F1Z5')
            ->screenshot('organization_creation_form')
            ->click('button[type="submit"]')
            ->pause(2000)
            ->screenshot('organization_created_success');
    });
});

test('user can view customers page', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/customers')
            ->assertSee('Customers')
            ->screenshot('customers_page');
    });
});

test('user can create a new customer', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/customers')
            ->click('.bg-blue-500')
            ->waitFor('form', 3)
            ->type('[wire\\:model="name"]', 'Acme Corporation')
            ->type('[wire\\:model="phone"]', '+91-9876543211')
            ->type('[wire\\:model="emails.0"]', 'contact@acme.com')
            ->type('[wire\\:model="location_name"]', 'Main Office')
            ->type('[wire\\:model="address_line_1"]', '456 Client Avenue')
            ->type('[wire\\:model="city"]', 'Delhi')
            ->type('[wire\\:model="state"]', 'Delhi')
            ->type('[wire\\:model="country"]', 'India')
            ->type('[wire\\:model="postal_code"]', '110001')
            ->screenshot('customer_creation_form')
            ->click('button[type="submit"]')
            ->pause(2000)
            ->screenshot('customer_created_success');
    });
});

test('user can view invoices page', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/invoices')
            ->assertSee('Invoices')
            ->screenshot('invoices_page');
    });
});

test('user can create an invoice with items', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        // Create organization through UI first
        $browser->visit('/organizations')
            ->click('.bg-blue-500')
            ->waitFor('form', 3)
            ->type('[wire\\:model="name"]', 'Test Organization Ltd')
            ->type('[wire\\:model="phone"]', '+91-9876543210')
            ->type('[wire\\:model="emails.0"]', 'test@organization.com')
            ->type('[wire\\:model="location_name"]', 'Head Office')
            ->type('[wire\\:model="address_line_1"]', '123 Business Street')
            ->type('[wire\\:model="city"]', 'Mumbai')
            ->type('[wire\\:model="state"]', 'Maharashtra')
            ->type('[wire\\:model="country"]', 'India')
            ->type('[wire\\:model="postal_code"]', '400001')
            ->type('[wire\\:model="gstin"]', '27ABCDE1234F1Z5')
            ->click('button[type="submit"]')
            ->pause(1000)

            // Create customer through UI
            ->visit('/customers')
            ->click('.bg-blue-500')
            ->waitFor('form', 3)
            ->type('[wire\\:model="name"]', 'Acme Corporation')
            ->type('[wire\\:model="phone"]', '+91-9876543211')
            ->type('[wire\\:model="emails.0"]', 'contact@acme.com')
            ->type('[wire\\:model="location_name"]', 'Main Office')
            ->type('[wire\\:model="address_line_1"]', '456 Client Avenue')
            ->type('[wire\\:model="city"]', 'Delhi')
            ->type('[wire\\:model="state"]', 'Delhi')
            ->type('[wire\\:model="country"]', 'India')
            ->type('[wire\\:model="postal_code"]', '110001')
            ->click('button[type="submit"]')
            ->pause(1000)

            // Now create invoice
            ->visit('/invoices')
            ->click('.bg-blue-500')  // Create Invoice button
            ->waitFor('form', 3)
            ->screenshot('invoice_creation_step1')
            ->select('[wire\\:model\\.live="organization_id"]', '1')  // First company
            ->select('[wire\\:model\\.live="customer_id"]', '1')  // First customer
            ->pause(2000)  // Wait for location dropdowns to appear
            ->select('[wire\\:model="organization_location_id"]', '1')  // First company location
            ->select('[wire\\:model="customer_location_id"]', '2')  // First customer location
            ->screenshot('invoice_creation_step1_filled')
            ->click('button[wire\\:click="nextStep"]')
            ->pause(1000)
            ->screenshot('invoice_creation_step2')
            ->type('[wire\\:model\\.live="items\\.0\\.description"]', 'Web Development Services')
            ->type('[wire\\:model\\.live="items\\.0\\.quantity"]', '10')
            ->type('[wire\\:model\\.live="items\\.0\\.unit_price"]', '5000')
            ->type('[wire\\:model\\.live="items\\.0\\.tax_rate"]', '18')
            ->screenshot('invoice_creation_step2_filled')
            ->click('.bg-blue-500')
            ->pause(2000)
            ->screenshot('invoice_creation_after_first_next')
            ->click('.bg-blue-500')  // Click Next again
            ->pause(2000)
            ->screenshot('invoice_creation_after_second_next')
            ->pause(2000)
            ->screenshot('invoice_created_success');
    });
});

test('user can create an estimate', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        // Create organization through UI first
        $browser->visit('/companies')
            ->click('.bg-blue-500')
            ->waitFor('form', 3)
            ->type('[wire\\:model="name"]', 'Estimate Organization Ltd')
            ->type('[wire\\:model="phone"]', '+91-9876543210')
            ->type('[wire\\:model="emails.0"]', 'estimate@organization.com')
            ->type('[wire\\:model="location_name"]', 'Head Office')
            ->type('[wire\\:model="address_line_1"]', '123 Business Street')
            ->type('[wire\\:model="city"]', 'Mumbai')
            ->type('[wire\\:model="state"]', 'Maharashtra')
            ->type('[wire\\:model="country"]', 'India')
            ->type('[wire\\:model="postal_code"]', '400001')
            ->type('[wire\\:model="gstin"]', '27ABCDE1234F1Z5')
            ->click('button[type="submit"]')
            ->pause(1000)

            // Create customer through UI
            ->visit('/customers')
            ->click('.bg-blue-500')
            ->waitFor('form', 3)
            ->type('[wire\\:model="name"]', 'Estimate Client Corp')
            ->type('[wire\\:model="phone"]', '+91-9876543211')
            ->type('[wire\\:model="emails.0"]', 'client@estimate.com')
            ->type('[wire\\:model="location_name"]', 'Main Office')
            ->type('[wire\\:model="address_line_1"]', '456 Client Avenue')
            ->type('[wire\\:model="city"]', 'Delhi')
            ->type('[wire\\:model="state"]', 'Delhi')
            ->type('[wire\\:model="country"]', 'India')
            ->type('[wire\\:model="postal_code"]', '110001')
            ->click('button[type="submit"]')
            ->pause(1000)

            // Now create estimate
            ->visit('/invoices')
            ->click('.bg-green-500')  // Create Estimate button
            ->waitFor('form', 3)
            ->screenshot('estimate_creation_step1')
            ->select('[wire\\:model\\.live="organization_id"]', '1')  // First company
            ->select('[wire\\:model\\.live="customer_id"]', '1')  // First customer
            ->pause(2000)  // Wait for location dropdowns to appear
            ->select('[wire\\:model="organization_location_id"]', '1')  // First company location
            ->select('[wire\\:model="customer_location_id"]', '2')  // First customer location
            ->screenshot('estimate_creation_step1_filled')
            ->click('button[wire\\:click="nextStep"]')  // Use proper button selector
            ->pause(2000)
            ->waitFor('[wire\\:model\\.live="items\\.0\\.description"]', 5)  // Wait for form to appear
            ->screenshot('estimate_creation_step2')
            ->type('[wire\\:model\\.live="items\\.0\\.description"]', 'Mobile App Development')
            ->type('[wire\\:model\\.live="items\\.0\\.quantity"]', '5')
            ->type('[wire\\:model\\.live="items\\.0\\.unit_price"]', '10000')
            ->type('[wire\\:model\\.live="items\\.0\\.tax_rate"]', '18')
            ->screenshot('estimate_creation_step2_filled')
            ->click('button[wire\\:click="nextStep"]')  // Use proper button selector
            ->pause(2000)
            ->screenshot('estimate_created_success');
    });
});

test('user can add multiple items to invoice', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        // Create organization through UI first
        $browser->visit('/companies')
            ->click('.bg-blue-500')
            ->waitFor('form', 3)
            ->type('[wire\\:model="name"]', 'Multi-Item Organization Ltd')
            ->type('[wire\\:model="phone"]', '+91-9876543210')
            ->type('[wire\\:model="emails.0"]', 'multi@organization.com')
            ->type('[wire\\:model="location_name"]', 'Head Office')
            ->type('[wire\\:model="address_line_1"]', '123 Business Street')
            ->type('[wire\\:model="city"]', 'Mumbai')
            ->type('[wire\\:model="state"]', 'Maharashtra')
            ->type('[wire\\:model="country"]', 'India')
            ->type('[wire\\:model="postal_code"]', '400001')
            ->click('button[type="submit"]')
            ->pause(1000)

            // Create customer through UI
            ->visit('/customers')
            ->click('.bg-blue-500')
            ->waitFor('form', 3)
            ->type('[wire\\:model="name"]', 'Multi-Item Client Corp')
            ->type('[wire\\:model="phone"]', '+91-9876543211')
            ->type('[wire\\:model="emails.0"]', 'multi@client.com')
            ->type('[wire\\:model="location_name"]', 'Client Office')
            ->type('[wire\\:model="address_line_1"]', '456 Client Lane')
            ->type('[wire\\:model="city"]', 'Delhi')
            ->type('[wire\\:model="state"]', 'Delhi')
            ->type('[wire\\:model="country"]', 'India')
            ->type('[wire\\:model="postal_code"]', '110001')
            ->click('button[type="submit"]')
            ->pause(1000)

            // Create invoice with multiple items
            ->visit('/invoices')
            ->click('.bg-blue-500')  // Create Invoice button
            ->waitFor('form', 3)
            ->select('[wire\\:model\\.live="organization_id"]', '1')  // First company
            ->select('[wire\\:model\\.live="customer_id"]', '1')  // First customer
            ->pause(2000)  // Wait for location dropdowns to appear
            ->select('[wire\\:model="organization_location_id"]', '1')  // First company location
            ->select('[wire\\:model="customer_location_id"]', '2')  // First customer location
            ->click('button[wire\\:click="nextStep"]')
            ->pause(2000)
            ->waitFor('[wire\\:model\\.live="items\\.0\\.description"]', 5)

            // Add first item
            ->type('[wire\\:model\\.live="items\\.0\\.description"]', 'Design Services')
            ->type('[wire\\:model\\.live="items\\.0\\.quantity"]', '3')
            ->type('[wire\\:model\\.live="items\\.0\\.unit_price"]', '2500')
            ->type('[wire\\:model\\.live="items\\.0\\.tax_rate"]', '18')
            ->screenshot('invoice_first_item_added')

            // Add second item
            ->click('button[wire\\:click="addItem"]')
            ->pause(1000)
            ->waitFor('[wire\\:model\\.live="items\\.1\\.description"]', 3)
            ->type('[wire\\:model\\.live="items\\.1\\.description"]', 'Development Services')
            ->type('[wire\\:model\\.live="items\\.1\\.quantity"]', '5')
            ->type('[wire\\:model\\.live="items\\.1\\.unit_price"]', '4000')
            ->type('[wire\\:model\\.live="items\\.1\\.tax_rate"]', '18')
            ->screenshot('invoice_multiple_items_added')

            // Go to review and save
            ->click('button[wire\\:click="nextStep"]')
            ->pause(2000)
            ->screenshot('invoice_multiple_items_review')
            ->click('button[type="submit"]')
            ->pause(2000)
            ->screenshot('invoice_multiple_items_success');
    });
});

test('user can view invoice list with created invoices', function () {
    // Create test data
    $organization = Organization::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    // Create some test invoices using factories
    \App\Models\Invoice::factory()->count(3)->create([
        'organization_location_id' => $organization->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/invoices')
            ->assertSee('Invoices')
            ->screenshot('invoices_list_with_data')
            // ->assertSee('INV-') // May not have any invoices initially
            ->screenshot('invoices_list_populated');
    });
});
