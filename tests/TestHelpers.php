<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use App\ValueObjects\EmailCollection;

function createUserWithTeam(array $userAttributes = [], array $teamAttributes = []): User
{
    $defaultUserAttributes = [
        'name' => 'Test User',
        'email' => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password' => 'password', // Laravel will hash this automatically via User model cast
    ];

    $user = User::create(array_merge($defaultUserAttributes, $userAttributes));

    $defaultTeamAttributes = [
        'name' => 'Test Organization',
        'user_id' => $user->id, // Required for proper team ownership
        'personal_team' => true,
        'company_name' => 'Test Organization Inc.',
        'currency' => 'INR',
    ];

    $team = $user->ownedTeams()->create(array_merge($defaultTeamAttributes, $teamAttributes));

    // Set the team as the user's current team
    $user->switchTeam($team);

    return $user->fresh(['teams', 'currentTeam']);
}

function createOrganizationWithLocation(array $orgAttributes = [], array $locationAttributes = [], ?User $user = null): Organization
{
    // Create a user with team if not provided
    if (! $user && auth()->check()) {
        $user = auth()->user();
    } elseif (! $user) {
        $user = createUserWithTeam();
    }

    $defaultLocationAttributes = [
        'name' => 'Head Office',
        'address_line_1' => '123 Business Street',
        'city' => 'Business City',
        'state' => 'Business State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Organization::class,
        'locatable_id' => 1, // Temporary
    ];

    $location = Location::create(array_merge($defaultLocationAttributes, $locationAttributes));

    $defaultOrgAttributes = [
        'name' => 'Test Organization',
        'personal_team' => false,
        'company_name' => 'Test Organization Inc.',
        'tax_number' => 'TX-123456789',
        'registration_number' => 'REG-TEST-2024',
        'emails' => new EmailCollection(['contact@testorg.com']),
        'phone' => '+1-555-0123',
        'website' => 'https://testorg.com',
        'currency' => 'INR',
        'primary_location_id' => $location->id,
    ];

    $organization = $user->currentTeam;
    $organization->update(array_merge($defaultOrgAttributes, $orgAttributes));

    // Update location with correct organization ID
    $location->update(['locatable_id' => $organization->id]);

    return $organization->fresh(['primaryLocation']);
}

function createCustomerWithLocation(array $customerAttributes = [], array $locationAttributes = [], ?Organization $organization = null): Customer
{
    // Create an organization if not provided
    if (! $organization) {
        $organization = createOrganizationWithLocation();
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
        'organization_id' => $organization->id,
    ];

    $customer = Customer::create(array_merge($defaultCustomerAttributes, $customerAttributes));

    // Update location with correct customer ID
    $location->update(['locatable_id' => $customer->id]);

    return $customer->fresh(['primaryLocation']);
}

function createInvoiceWithItems(
    array $invoiceAttributes = [],
    ?array $items = null,
    ?Organization $organization = null,
    ?Customer $customer = null
): Invoice {
    if (! $organization) {
        $organization = createOrganizationWithLocation();
    }

    if (! $customer) {
        $customer = createCustomerWithLocation([], [], $organization);
    }

    $defaultInvoiceAttributes = [
        'type' => 'invoice',
        'organization_id' => $organization->id,
        'organization_location_id' => $organization->primary_location_id,
        'customer_id' => $customer->id,
        'customer_location_id' => $customer->primary_location_id,
        'invoice_number' => 'INV-'.rand(1000, 9999),
        'status' => 'draft',
        'currency' => $organization->currency ?? 'INR',
        'exchange_rate' => 1.000000,
        'subtotal' => 10000, // 100.00 in cents
        'tax' => 1800,       // 18.00 in cents
        'total' => 11800,    // 118.00 in cents
        'email_recipients' => ['customer@example.com'],
    ];

    $invoice = Invoice::create(array_merge($defaultInvoiceAttributes, $invoiceAttributes));

    // Create default items if none provided
    if ($items === null) {
        $items = [
            [
                'description' => 'Test Product 1',
                'quantity' => 2,
                'unit_price' => 5000, // 50.00 in cents
                'tax_rate' => 18.00,  // 18.00%
            ],
        ];
    }

    // Create invoice items
    foreach ($items as $itemData) {
        $defaultItemAttributes = [
            'invoice_id' => $invoice->id,
            'description' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 10000, // 100.00 in cents
            'tax_rate' => 18.00,   // 18.00%
        ];

        InvoiceItem::create(array_merge($defaultItemAttributes, $itemData));
    }

    return $invoice->fresh(['items', 'organizationLocation', 'customerLocation', 'customer']);
}

function createLocation(string $locatableType, int $locatableId, array $attributes = []): Location
{
    $defaultAttributes = [
        'name' => 'Default Location',
        'address_line_1' => '123 Default Street',
        'city' => 'Default City',
        'state' => 'Default State',
        'country' => 'Default Country',
        'postal_code' => '12345',
        'locatable_type' => $locatableType,
        'locatable_id' => $locatableId,
    ];

    return Location::create(array_merge($defaultAttributes, $attributes));
}

function loginUserInBrowser($browser, ?User $user = null): User
{
    if (! $user) {
        // Create user with factory method that ensures proper password hashing
        $user = User::factory()->create([
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password', // Factory will handle hashing properly
        ]);

        // Create organization for the user
        $organization = $user->ownedTeams()->create([
            'name' => 'Test Organization',
            'user_id' => $user->id,
            'personal_team' => true,
            'company_name' => 'Test Organization Inc.',
            'currency' => 'INR',
        ]);

        // Set current team
        $user->current_team_id = $organization->id;
        $user->save();
    }

    // Use standard loginAs with web guard
    $browser->loginAs($user, 'web');

    return $user;
}
