<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\User;
use App\ValueObjects\EmailCollection;

function createCompanyWithLocation(array $companyAttributes = [], array $locationAttributes = [], ?User $user = null): Company
{
    // Create a user with team if not provided
    if (! $user && auth()->check()) {
        $user = auth()->user();
    } elseif (! $user) {
        $user = createUserWithTeam();
    }

    $defaultLocationAttributes = [
        'name' => 'Test Office',
        'address_line_1' => '123 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Company::class,
        'locatable_id' => 1, // Temporary
    ];

    $location = Location::create(array_merge($defaultLocationAttributes, $locationAttributes));

    $defaultCompanyAttributes = [
        'name' => 'Test Company',
        'emails' => new EmailCollection(['test@company.com']),
        'primary_location_id' => $location->id,
        'team_id' => $user->currentTeam->id,
        'currency' => 'INR',
    ];

    $company = Company::create(array_merge($defaultCompanyAttributes, $companyAttributes));

    // Update location with correct company ID
    $location->update(['locatable_id' => $company->id]);

    return $company->fresh(['primaryLocation']);
}

function createCustomerWithLocation(array $customerAttributes = [], array $locationAttributes = [], ?Company $company = null): Customer
{
    // Create a company if not provided
    if (! $company) {
        $company = createCompanyWithLocation();
    }

    $defaultLocationAttributes = [
        'name' => 'Customer Office',
        'address_line_1' => '456 Customer Avenue',
        'city' => 'Customer City',
        'state' => 'Customer State',
        'country' => 'Test Country',
        'postal_code' => '54321',
        'locatable_type' => Customer::class,
        'locatable_id' => 1, // Temporary
    ];

    $location = Location::create(array_merge($defaultLocationAttributes, $locationAttributes));

    $defaultCustomerAttributes = [
        'name' => 'Test Customer',
        'emails' => new EmailCollection(['test@customer.com']),
        'primary_location_id' => $location->id,
        'company_id' => $company->id,
    ];

    $customer = Customer::create(array_merge($defaultCustomerAttributes, $customerAttributes));

    // Update location with correct customer ID
    $location->update(['locatable_id' => $customer->id]);

    return $customer->fresh(['primaryLocation']);
}

function createInvoiceWithItems(
    array $invoiceAttributes = [],
    ?array $items = null,
    ?Company $company = null,
    ?Customer $customer = null
): Invoice {
    if (! $company) {
        $company = createCompanyWithLocation();
    }

    if (! $customer) {
        $customer = createCustomerWithLocation([], [], $company);
    }

    $defaultInvoiceAttributes = [
        'type' => 'invoice',
        'company_location_id' => $company->primaryLocation->id,
        'customer_location_id' => $customer->primaryLocation->id,
        'invoice_number' => 'INV-'.time(),
        'status' => 'draft',
        'subtotal' => 10000,
        'tax' => 1800,
        'total' => 11800,
        'company_id' => $company->id,
    ];

    $invoice = Invoice::create(array_merge($defaultInvoiceAttributes, $invoiceAttributes));

    // Create default items if none provided
    if ($items === null) {
        $items = [
            [
                'description' => 'Test Service',
                'quantity' => 1,
                'unit_price' => 10000,
                'tax_rate' => 18, // 18% as users would enter
            ],
        ];
    }

    // Create invoice items
    foreach ($items as $itemData) {
        InvoiceItem::create(array_merge([
            'invoice_id' => $invoice->id,
        ], $itemData));
    }

    return $invoice->fresh(['items', 'companyLocation', 'customerLocation']);
}

function createLocation(string $type, int $locatableId, array $attributes = []): Location
{
    $defaultAttributes = [
        'name' => 'Test Location',
        'address_line_1' => '123 Default Street',
        'city' => 'Default City',
        'state' => 'Default State',
        'country' => 'Default Country',
        'postal_code' => '12345',
        'locatable_type' => $type,
        'locatable_id' => $locatableId,
    ];

    return Location::create(array_merge($defaultAttributes, $attributes));
}

function createUserWithTeam(array $userAttributes = [], array $teamAttributes = []): User
{
    $defaultUserAttributes = [
        'name' => 'Test User',
        'email' => 'test-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ];

    $user = User::create(array_merge($defaultUserAttributes, $userAttributes));

    $defaultTeamAttributes = [
        'name' => 'Test Team',
        'personal_team' => true,
    ];

    $team = $user->ownedTeams()->create(array_merge($defaultTeamAttributes, $teamAttributes));

    // Set the team as the user's current team
    $user->switchTeam($team);

    return $user->fresh(['teams', 'currentTeam']);
}

function loginUserInBrowser($browser, ?User $user = null): User
{
    if (! $user) {
        $user = createUserWithTeam();
    }

    $browser->visit('/login')
        ->waitFor('form', 5)
        ->clear('input[name="email"]')
        ->clear('input[name="password"]')
        ->type('input[name="email"]', $user->email)
        ->type('input[name="password"]', 'password')
        ->click('button[type="submit"]')
        ->waitFor('body', 5); // Wait for page to load

    // Check if we're redirected away from login
    $currentUrl = $browser->driver->getCurrentURL();
    if (str_contains($currentUrl, '/login')) {
        // If still on login page, take a screenshot and check for errors
        $browser->screenshot('login_failed_debug');

        // Check if there are validation errors visible
        $pageSource = $browser->driver->getPageSource();
        if (str_contains($pageSource, 'These credentials do not match our records')) {
            throw new \Exception('Login failed - invalid credentials');
        }
        if (str_contains($pageSource, 'The email field is required')) {
            throw new \Exception('Login failed - email field validation error');
        }
        if (str_contains($pageSource, 'The password field is required')) {
            throw new \Exception('Login failed - password field validation error');
        }

        throw new \Exception('Login failed - still on login page. User email: '.$user->email);
    }

    return $user;
}
