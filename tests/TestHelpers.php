<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use App\ValueObjects\ContactCollection;

// Global test counter to ensure unique values
$GLOBALS['test_counter'] = $GLOBALS['test_counter'] ?? 0;

if (! function_exists('getUniqueTestId')) {
    function getUniqueTestId(): int
    {
        return ++$GLOBALS['test_counter'].rand(1, 9999999);
    }
}

if (! function_exists('makeUniqueInvoiceNumber')) {
    function makeUniqueInvoiceNumber(string $baseNumber): string
    {
        // With database cleanup, just add a simple counter for uniqueness within test run
        $counter = getUniqueTestId();

        return $baseNumber.'-T'.$counter;
    }
}

if (! function_exists('createUserWithTeam')) {
    function createUserWithTeam(array $userAttributes = [], array $teamAttributes = []): User
    {
        $defaultUserAttributes = [
            'name' => 'Test User',
            'email' => 'user'.uniqid().rand(1, 99999999).'@example.test',
            'email_verified_at' => now(),
            'password' => 'password', // Laravel will hash this automatically via User model cast
        ];

        $user = User::create(array_merge($defaultUserAttributes, $userAttributes));

        $defaultTeamAttributes = [
            'name' => 'Test Organization',
            'user_id' => $user->id,
            'company_name' => 'Test Organization Inc.',
            'currency' => 'INR',
            'setup_completed_at' => now(),
        ];

        $team = $user->ownedTeams()->create(array_merge($defaultTeamAttributes, $teamAttributes));

        // Set the team as the user's current team
        $user->switchTeam($team);

        return $user->fresh(['teams', 'currentTeam']);
    }
}

if (! function_exists('createOrganizationWithLocation')) {
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
            'country' => 'IN',
            'postal_code' => '12345',
            'locatable_type' => Organization::class,
            'locatable_id' => 1, // Temporary
        ];

        $location = Location::create(array_merge($defaultLocationAttributes, $locationAttributes));

        $defaultOrgAttributes = [
            'name' => 'Test Organization',
            'company_name' => 'Test Organization Inc.',
            'tax_number' => 'TX-'.getUniqueTestId(),
            'registration_number' => 'REG-'.getUniqueTestId(),
            'emails' => new ContactCollection([['name' => 'Test Org Contact', 'email' => 'org'.getUniqueTestId().'@example.test']]),
            'phone' => '+14155550123',
            'phone_country' => 'US',
            'website' => 'https://testorg.com',
            'currency' => 'INR',
            'country_code' => 'IN',
            'financial_year_type' => 'april_march',
            'financial_year_start_month' => 4,
            'financial_year_start_day' => 1,
            'primary_location_id' => $location->id,
            'setup_completed_at' => now(), // Mark setup as complete for tests
        ];

        $organization = $user->currentTeam;
        $organization->update(array_merge($defaultOrgAttributes, $orgAttributes));

        // Update location with correct organization ID
        $location->update(['locatable_id' => $organization->id]);

        return $organization->fresh(['primaryLocation']);
    }
}

if (! function_exists('createCustomerWithLocation')) {
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
            'country' => 'IN',
            'postal_code' => '54321',
            'locatable_type' => Customer::class,
            'locatable_id' => 1, // Temporary
        ];

        $location = Location::create(array_merge($defaultLocationAttributes, $locationAttributes));

        $defaultCustomerAttributes = [
            'name' => 'Test Customer',
            'emails' => new ContactCollection([['name' => 'Test Customer Contact', 'email' => 'customer'.getUniqueTestId().'@example.test']]),
            'primary_location_id' => $location->id,
            'organization_id' => $organization->id,
        ];

        $customer = Customer::create(array_merge($defaultCustomerAttributes, $customerAttributes));

        // Update location with correct customer ID
        $location->update(['locatable_id' => $customer->id]);

        return $customer->fresh(['primaryLocation']);
    }
}

if (! function_exists('createInvoiceWithItems')) {
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
            'customer_shipping_location_id' => $customer->primary_location_id,
            'invoice_number' => 'INV-'.fake()->unique()->numerify('########'),
            'status' => 'draft',
            'currency' => $organization->currency ?? 'INR',
            'exchange_rate' => 1.000000,
            'subtotal' => 10000, // 100.00 in cents
            'tax' => 1800,       // 18.00 in cents
            'total' => 11800,    // 118.00 in cents
            'email_recipients' => ['customer@example.com'],
        ];

        // Merge attributes, only make auto-generated invoice numbers unique
        $mergedAttributes = array_merge($defaultInvoiceAttributes, $invoiceAttributes);

        // Only apply uniqueness suffix if invoice_number wasn't explicitly provided in test
        if (! isset($invoiceAttributes['invoice_number']) && isset($mergedAttributes['invoice_number'])) {
            $mergedAttributes['invoice_number'] = makeUniqueInvoiceNumber($mergedAttributes['invoice_number']);
        }

        $invoice = Invoice::create($mergedAttributes);

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
}

if (! function_exists('createLocation')) {
    function createLocation(string $locatableType, int $locatableId, array $attributes = []): Location
    {
        $defaultAttributes = [
            'name' => 'Default Location',
            'address_line_1' => '123 Default Street',
            'city' => 'Default City',
            'state' => 'Default State',
            'country' => 'IN',
            'postal_code' => '12345',
            'locatable_type' => $locatableType,
            'locatable_id' => $locatableId,
        ];

        return Location::create(array_merge($defaultAttributes, $attributes));
    }
}

if (! function_exists('createNumberingSeries')) {
    function createNumberingSeries(array $attributes = [], ?Organization $organization = null): InvoiceNumberingSeries
    {
        if (! $organization) {
            $organization = createOrganizationWithLocation();
        }

        $defaultAttributes = [
            'organization_id' => $organization->id,
            'name' => 'Test Series',
            'prefix' => 'TST',
            'format_pattern' => '{PREFIX}-{SEQUENCE:4}',
            'current_number' => 0,
            'reset_frequency' => 'never',
            'is_active' => true,
            'is_default' => false,
        ];

        return InvoiceNumberingSeries::create(array_merge($defaultAttributes, $attributes));
    }
}

if (! function_exists('seedInvoiceListFixture')) {
    /**
     * Seeds a fully set-up organization with `$documents` documents for the
     * invoice list browser tests. The first is always an estimate, which
     * carries the full six-action row — the worst case for both layout and for
     * telling row actions apart by name.
     *
     * @return array{0: User, 1: Organization, 2: Customer}
     */
    function seedInvoiceListFixture(int $documents = 1): array
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Maya Sharma',
            'email' => 'invoice-list-a11y@example.test',
        ]);

        $organization = createOrganizationWithLocation([
            'name' => 'Clarity Technologies',
            'currency' => 'INR',
            'country_code' => 'IN',
            'setup_completed_at' => now(),
        ], [], $user);

        $customer = createCustomerWithLocation([
            'name' => 'Riverbend Institute of Medical Sciences Limited',
        ], [], $organization);

        createInvoiceWithItems([
            'invoice_number' => 'CT-EST-001',
            'type' => 'estimate',
            'status' => 'draft',
            'currency' => 'INR',
            'issued_at' => now(),
            'subtotal' => 20000000,
            'tax' => 3600000,
            'total' => 23600000,
        ], null, $organization, $customer);

        for ($i = 1; $i < $documents; $i++) {
            createInvoiceWithItems([
                'invoice_number' => sprintf('CT-INV-%03d', $i),
                'type' => 'invoice',
                'status' => 'draft',
                'currency' => 'INR',
                'issued_at' => now()->subDays($i),
                'subtotal' => 1000000,
                'tax' => 180000,
                'total' => 1180000,
            ], null, $organization, $customer);
        }

        return [$user, $organization, $customer];
    }
}

if (! function_exists('sharePath')) {
    /**
     * The path and query of a document's share link, without the host.
     *
     * The signature does not cover the host, and the browser plugin serves the
     * app on a random port, so visit() needs the link relative to wherever the
     * test is running.
     */
    function sharePath(Invoice $invoice): string
    {
        $url = $invoice->shareUrl();

        return parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);
    }
}
