<?php

declare(strict_types=1);

use App\Models\User;

// --- Datasets ---

dataset('authenticated pages', [
    'dashboard' => ['/dashboard', 'InvoiceInk'],
    'invoices' => ['/invoices', 'Invoices'],
    'invoice create' => ['/invoices/create', 'Create Invoice'],
    'estimate create' => ['/estimates/create', 'Create Estimate'],
    'customers' => ['/customers', 'Customers'],
    'numbering series' => ['/numbering-series', 'InvoiceInk'],
    'email templates' => ['/email-templates', 'Email Templates'],
    'profile' => ['/user/profile', 'Profile'],
]);

dataset('org configurations', [
    'INR org with full setup' => fn () => [
        'currency' => 'INR',
        'country_code' => 'IN',
        'company_name' => 'Indian Corp Pvt Ltd',
        'financial_year_type' => 'april_march',
    ],
    'USD org minimal setup' => fn () => [
        'currency' => 'USD',
        'country_code' => 'US',
        'company_name' => 'US Company LLC',
        'financial_year_type' => null,
    ],
    'AED org (UAE)' => fn () => [
        'currency' => 'AED',
        'country_code' => 'AE',
        'company_name' => 'Dubai Trading LLC',
        'financial_year_type' => 'january_december',
    ],
    'org with no company name' => fn () => [
        'currency' => 'EUR',
        'country_code' => 'DE',
        'company_name' => null,
        'financial_year_type' => null,
    ],
]);

// --- Helpers ---

function createSmokeUser(array $orgConfig = []): User
{
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'smoke-'.uniqid().'@example.test',
        'password' => 'password',
    ]);

    $defaults = [
        'currency' => 'INR',
        'country_code' => 'IN',
        'company_name' => 'Smoke Test Corp',
        'setup_completed_at' => now(),
    ];

    $user->currentTeam->update(array_merge($defaults, array_filter($orgConfig, fn ($v) => $v !== null)));

    return $user;
}

// --- Tests ---

it('renders all authenticated pages without JS errors', function (string $path, string $expectedText) {
    $user = createSmokeUser();
    $this->actingAs($user);

    $this->visit($path)
        ->assertSee($expectedText)
        ->assertNoJavascriptErrors();
})->with('authenticated pages');

it('renders dashboard for different org configurations without JS errors', function (array $orgConfig) {
    $user = createSmokeUser($orgConfig);
    $this->actingAs($user);

    $page = $this->visit('/dashboard');
    $page->assertNoJavascriptErrors();

    // Nav bar should always be visible regardless of org config
    $page->assertSee('InvoiceInk')
        ->assertSee('Dashboard');
})->with('org configurations');

it('renders invoice create for different currencies without JS errors', function (array $orgConfig) {
    $user = createSmokeUser($orgConfig);
    $this->actingAs($user);

    $this->visit('/invoices/create')
        ->assertSee('Create Invoice')
        ->assertNoJavascriptErrors();
})->with('org configurations');

it('renders pages with data without JS errors', function () {
    $user = createSmokeUser();
    $this->actingAs($user);

    // Create org with location so invoices can reference it
    $org = createOrganizationWithLocation([], [], $user);
    $customer = createCustomerWithLocation([], [], $org);
    createInvoiceWithItems([], null, $org, $customer);
    createNumberingSeries([], $org);

    $this->visit('/dashboard')->assertNoJavascriptErrors();
    $this->visit('/invoices')->assertNoJavascriptErrors();
    $this->visit('/customers')->assertNoJavascriptErrors();
    $this->visit('/numbering-series')->assertNoJavascriptErrors();
});

it('renders pages for fresh user with no data without JS errors', function () {
    // User with org setup but zero customers, invoices, or numbering series
    $user = createSmokeUser();
    $this->actingAs($user);

    $this->visit('/dashboard')->assertNoJavascriptErrors();
    $this->visit('/invoices')->assertNoJavascriptErrors();
    $this->visit('/customers')->assertNoJavascriptErrors();
    $this->visit('/numbering-series')->assertNoJavascriptErrors();
});

it('navigation bar is visible on all authenticated pages', function (string $path, string $_expectedText) {
    $user = createSmokeUser();
    $this->actingAs($user);

    $this->visit($path)
        ->assertSee('InvoiceInk')
        ->assertSee('Dashboard')
        ->assertSee('Customers')
        ->assertNoJavascriptErrors();
})->with('authenticated pages');
