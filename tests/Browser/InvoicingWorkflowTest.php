<?php

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;

uses(RefreshDatabase::class);

test('user can view the dashboard', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee('Invoicing')
            ->screenshot('dashboard_view');
    });
});

test('user can view companies page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/companies')
            ->assertSee('Companies')
            ->screenshot('companies_page');
    });
});

test('user can create a new company', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/companies')
            ->click('text:Create Company')
            ->waitFor('form')
            ->type('name', 'Test Company Ltd')
            ->type('phone', '+91-9876543210')
            ->type('emails.0', 'test@company.com')
            ->type('location_name', 'Head Office')
            ->type('address_line_1', '123 Business Street')
            ->type('city', 'Mumbai')
            ->type('state', 'Maharashtra')
            ->type('country', 'India')
            ->type('postal_code', '400001')
            ->type('gstin', '27ABCDE1234F1Z5')
            ->screenshot('company_creation_form')
            ->click('button[type="submit"]')
            ->waitFor('.alert, .success, [class*="success"]', 5)
            ->screenshot('company_created_success');
    });
});

test('user can view customers page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/customers')
            ->assertSee('Customers')
            ->screenshot('customers_page');
    });
});

test('user can create a new customer', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/customers')
            ->click('text:Create Customer')
            ->waitFor('form')
            ->type('name', 'Acme Corporation')
            ->type('phone', '+91-9876543211')
            ->type('emails.0', 'contact@acme.com')
            ->type('location_name', 'Main Office')
            ->type('address_line_1', '456 Client Avenue')
            ->type('city', 'Delhi')
            ->type('state', 'Delhi')
            ->type('country', 'India')
            ->type('postal_code', '110001')
            ->screenshot('customer_creation_form')
            ->click('button[type="submit"]')
            ->waitFor('.alert, .success, [class*="success"]', 5)
            ->screenshot('customer_created_success');
    });
});

test('user can view invoices page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/invoices')
            ->assertSee('Invoices')
            ->screenshot('invoices_page');
    });
});

test('user can create an invoice with items', function () {
    // Create test data
    $company = Company::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $this->browse(function (Browser $browser) use ($company, $customer) {
        $browser->visit('/invoices')
            ->click('text:Create Invoice')
            ->waitFor('form')
            ->screenshot('invoice_creation_step1')
            ->select('company_id', $company->id)
            ->select('customer_id', $customer->id)
            ->select('company_location_id', $company->primaryLocation->id)
            ->select('customer_location_id', $customer->primaryLocation->id)
            ->screenshot('invoice_creation_step1_filled')
            ->click('text:Next')
            ->waitFor('[id*="item"], [class*="item"]', 2)
            ->screenshot('invoice_creation_step2')
            ->type('items.0.description', 'Web Development Services')
            ->type('items.0.quantity', '10')
            ->type('items.0.unit_price', '5000')
            ->type('items.0.tax_rate', '18')
            ->screenshot('invoice_creation_step2_filled')
            ->click('text:Next')
            ->waitFor('[class*="review"], [class*="summary"]', 2)
            ->screenshot('invoice_creation_step3_review')
            ->click('button[type="submit"]')
            ->waitFor('.alert, .success, [class*="success"]', 5)
            ->screenshot('invoice_created_success');
    });
});

test('user can create an estimate', function () {
    // Create test data
    $company = Company::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $this->browse(function (Browser $browser) use ($company, $customer) {
        $browser->visit('/invoices')
            ->click('text:Create Estimate')
            ->waitFor('form')
            ->screenshot('estimate_creation_step1')
            ->select('company_id', $company->id)
            ->select('customer_id', $customer->id)
            ->select('company_location_id', $company->primaryLocation->id)
            ->select('customer_location_id', $customer->primaryLocation->id)
            ->screenshot('estimate_creation_step1_filled')
            ->click('text:Next')
            ->waitFor('[id*="item"], [class*="item"]', 2)
            ->screenshot('estimate_creation_step2')
            ->type('items.0.description', 'Mobile App Development')
            ->type('items.0.quantity', '5')
            ->type('items.0.unit_price', '10000')
            ->type('items.0.tax_rate', '18')
            ->screenshot('estimate_creation_step2_filled')
            ->click('text:Next')
            ->waitFor('[class*="review"], [class*="summary"]', 2)
            ->screenshot('estimate_creation_step3_review')
            ->click('button[type="submit"]')
            ->waitFor('.alert, .success, [class*="success"]', 5)
            ->screenshot('estimate_created_success');
    });
});

test('user can add multiple items to invoice', function () {
    $company = Company::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    $this->browse(function (Browser $browser) use ($company, $customer) {
        $browser->visit('/invoices')
            ->click('text:Create Invoice')
            ->waitFor('form')
            ->select('company_id', $company->id)
            ->select('customer_id', $customer->id)
            ->select('company_location_id', $company->primaryLocation->id)
            ->select('customer_location_id', $customer->primaryLocation->id)
            ->click('text:Next')
            ->waitFor('[id*="item"], [class*="item"]', 2)
            ->type('items.0.description', 'Design Services')
            ->type('items.0.quantity', '3')
            ->type('items.0.unit_price', '2500')
            ->type('items.0.tax_rate', '18')
            ->screenshot('invoice_first_item_added')
            ->click('text:Add Item')
            ->waitFor('[id*="items.1"], [class*="item"]:nth-child(2)', 2)
            ->type('items.1.description', 'Development Services')
            ->type('items.1.quantity', '5')
            ->type('items.1.unit_price', '4000')
            ->type('items.1.tax_rate', '18')
            ->screenshot('invoice_multiple_items_added')
            ->click('text:Next')
            ->waitFor('[class*="review"], [class*="summary"]', 2)
            ->screenshot('invoice_multiple_items_review')
            ->click('button[type="submit"]')
            ->waitFor('.alert, .success, [class*="success"]', 5)
            ->screenshot('invoice_multiple_items_success');
    });
});

test('user can view invoice list with created invoices', function () {
    // Create test data
    $company = Company::factory()->withLocation()->create();
    $customer = Customer::factory()->withLocation()->create();

    // Create some test invoices using factories
    \App\Models\Invoice::factory()->count(3)->create([
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/invoices')
            ->assertSee('Invoices')
            ->screenshot('invoices_list_with_data')
            ->assertSee('INV-') // Should see invoice numbers
            ->screenshot('invoices_list_populated');
    });
});
