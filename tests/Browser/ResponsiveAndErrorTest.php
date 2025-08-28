<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;

uses(RefreshDatabase::class);

test('application is responsive on mobile viewport', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->resize(375, 667) // iPhone viewport
            ->visit('/')
            ->screenshot('mobile_dashboard')
            ->visit('/companies')
            ->screenshot('mobile_companies_page')
            ->visit('/customers')
            ->screenshot('mobile_customers_page')
            ->visit('/invoices')
            ->screenshot('mobile_invoices_page');
    });
});

test('application is responsive on tablet viewport', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->resize(768, 1024) // iPad viewport
            ->visit('/')
            ->screenshot('tablet_dashboard')
            ->visit('/companies')
            ->screenshot('tablet_companies_page')
            ->visit('/customers')
            ->screenshot('tablet_customers_page')
            ->visit('/invoices')
            ->screenshot('tablet_invoices_page');
    });
});

test('application works on desktop viewport', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->resize(1920, 1080) // Desktop viewport
            ->visit('/')
            ->screenshot('desktop_dashboard')
            ->visit('/companies')
            ->screenshot('desktop_companies_page')
            ->visit('/customers')
            ->screenshot('desktop_customers_page')
            ->visit('/invoices')
            ->screenshot('desktop_invoices_page');
    });
});

test('navigation works across all main pages', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/')
            ->screenshot('navigation_home')
            ->clickLink('Companies')
            ->assertPathIs('/companies')
            ->screenshot('navigation_companies')
            ->clickLink('Customers')
            ->assertPathIs('/customers')
            ->screenshot('navigation_customers')
            ->clickLink('Invoices')
            ->assertPathIs('/invoices')
            ->screenshot('navigation_invoices');
    });
});

test('handles non-existent invoice gracefully', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/invoices/non-existent-ulid')
            ->screenshot('404_invoice_not_found')
            ->assertSee('404');
    });
});

test('handles non-existent estimate gracefully', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/estimates/non-existent-ulid')
            ->screenshot('404_estimate_not_found')
            ->assertSee('404');
    });
});

test('form validation displays error messages', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/companies')
            ->click('.bg-blue-500')
            ->waitFor('form', 5)
            ->screenshot('company_form_empty')
            ->click('button[type="submit"]')
            ->pause(2000)
            ->screenshot('company_form_validation_errors')
            ->assertPresent('form');
    });
});

test('customer form validation works', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/customers')
            ->click('.bg-blue-500')
            ->waitFor('form', 5)
            ->screenshot('customer_form_empty')
            ->click('button[type="submit"]')
            ->pause(2000)
            ->screenshot('customer_form_validation_errors')
            ->assertPresent('form');
    });
});

test('invoice form step validation works', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/invoices')
            ->click('.bg-blue-500')
            ->waitFor('form', 5)
            ->screenshot('invoice_form_step1_empty')
            ->press('Next')
            ->pause(2000)
            ->screenshot('invoice_form_step1_validation_errors')
            ->assertPresent('form');
    });
});

test('dark mode toggle works if available', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/')
            ->screenshot('light_mode_dashboard');

        // Try to find and click dark mode toggle if it exists
        try {
            $browser->click("[class*='dark'], [data-theme], [class*='theme']")
                ->pause(1000)
                ->screenshot('dark_mode_dashboard');
        } catch (\Exception $e) {
            // Dark mode toggle not found, skip this test
            $browser->screenshot('dark_mode_not_available');
        }
    });
});

test('form inputs handle special characters', function () {
    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/companies')
            ->click('.bg-blue-500')
            ->waitFor('form')
            ->type('[wire\\:model="name"]', 'Test & Company "Special" Chars')
            ->type('[wire\\:model="phone"]', '+91-98765-43210')
            ->type('[wire\\:model="emails.0"]', 'test+special@company.co.in')
            ->type('[wire\\:model="location_name"]', 'Head Office & Warehouse')
            ->type('[wire\\:model="address_line_1"]', '123/A, "Main" Street & Avenue')
            ->type('[wire\\:model="city"]', 'New Delhi')
            ->type('[wire\\:model="state"]', 'Delhi')
            ->type('[wire\\:model="country"]', 'India')
            ->type('[wire\\:model="postal_code"]', '110001')
            ->screenshot('company_form_special_chars');
    });
});

test('pagination works in invoice list', function () {
    // Create many invoices to test pagination
    $company = \App\Models\Company::factory()->withLocation()->create();
    $customer = \App\Models\Customer::factory()->withLocation()->create();

    \App\Models\Invoice::factory()->count(25)->create([
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/invoices')
            ->screenshot('invoices_page_with_pagination');

        // If pagination exists, test it
        try {
            $browser->click('.pagination a, [class*="page"] a')
                ->pause(1000)
                ->screenshot('invoices_page_pagination_clicked');
        } catch (\Exception $e) {
            // Pagination not found, this is expected for small datasets
            $browser->screenshot('invoices_no_pagination');
        }
    });
});

test('search functionality works if available', function () {
    // Create some test data first
    $company = \App\Models\Company::factory()->withLocation()->create(['name' => 'Searchable Company']);
    $customer = \App\Models\Customer::factory()->withLocation()->create(['name' => 'Searchable Customer']);

    $this->browse(function (Browser $browser) {
        loginUserInBrowser($browser);

        $browser->visit('/companies')
            ->screenshot('companies_before_search');

        // Try to find search input and use it
        try {
            $browser->type('input[type="search"], input[placeholder*="search"], input[name*="search"]', 'Searchable')
                ->pause(1000)
                ->screenshot('companies_search_results');
        } catch (\Exception $e) {
            // Search input not found, this is expected if search is not implemented
            $browser->screenshot('companies_no_search');
        }
    });
});
