<?php

use App\Models\Customer;
use App\Models\Location;
use App\ValueObjects\ContactCollection;

test('can render customer index page', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $this->get('/customers')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Customers/Index'));
});

test('index shows paginated customers', function () {
    $organization = createOrganizationWithLocation();

    for ($i = 1; $i <= 12; $i++) {
        createCustomerWithLocation([
            'name' => "Customer {$i}",
            'emails' => new ContactCollection([['name' => "Contact {$i}", 'email' => "c{$i}@example.test"]]),
        ], [], $organization);
    }

    $this->actingAs($organization->owner);

    $this->get('/customers')
        ->assertInertia(fn ($page) => $page
            ->component('Customers/Index')
            ->has('customers.data', 10)
            ->where('customers.total', 12)
        );
});

test('can create customer', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => 'Test Customer Corp',
        'phone' => '+1234567890',
        'currency' => 'INR',
        'contacts' => [
            ['name' => 'John Doe', 'email' => 'customer@test.com'],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('customers', [
        'name' => 'Test Customer Corp',
        'phone' => '+1234567890',
    ]);
});

test('can create customer with multiple contacts', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => 'Multi Contact Customer',
        'currency' => 'INR',
        'contacts' => [
            ['name' => 'John Doe', 'email' => 'primary@customer.com'],
            ['name' => 'Jane Smith', 'email' => 'billing@customer.com'],
            ['name' => 'Bob Johnson', 'email' => 'support@customer.com'],
        ],
    ])->assertRedirect();

    $customer = Customer::where('name', 'Multi Contact Customer')->first();
    expect($customer->emails->getEmails())->toBe([
        'primary@customer.com',
        'billing@customer.com',
        'support@customer.com',
    ]);
    expect($customer->emails->getNames())->toBe([
        'John Doe',
        'Jane Smith',
        'Bob Johnson',
    ]);
});

test('validates required fields when creating customer', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => '',
        'currency' => 'INR',
        'contacts' => [
            ['name' => '', 'email' => ''],
        ],
    ])->assertSessionHasErrors(['name', 'contacts.0.email']);
});

test('validates email format', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => 'Test',
        'currency' => 'INR',
        'contacts' => [
            ['name' => '', 'email' => 'invalid-email'],
        ],
    ])->assertSessionHasErrors(['contacts.0.email']);
});

test('can update existing customer', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([
        'name' => 'Original Customer',
        'emails' => new ContactCollection([['name' => 'Original Contact', 'email' => 'original@customer.com']]),
    ], [], $organization);

    $this->actingAs($organization->owner);

    $this->put("/customers/{$customer->id}", [
        'name' => 'Updated Customer',
        'phone' => '+8888888888',
        'currency' => 'INR',
        'contacts' => [
            ['name' => 'Updated Contact', 'email' => 'updated@customer.com'],
        ],
    ])->assertRedirect();

    $customer->refresh();
    expect($customer->name)->toBe('Updated Customer');
    expect($customer->phone)->toBe('+8888888888');
    expect($customer->emails->getEmails())->toBe(['updated@customer.com']);
});

test('cannot update customer from different organization', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([
        'name' => 'Other Org Customer',
        'emails' => new ContactCollection([['name' => 'Test', 'email' => 'test@test.com']]),
    ], [], $organization);

    $otherUser = createUserWithTeam();
    $this->actingAs($otherUser);

    $this->put("/customers/{$customer->id}", [
        'name' => 'Hacked',
        'currency' => 'INR',
        'contacts' => [['name' => '', 'email' => 'hacked@test.com']],
    ])->assertNotFound(); // OrganizationScope hides customer from other orgs
});

test('can delete customer', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([
        'name' => 'Customer to Delete',
        'emails' => new ContactCollection([['name' => 'Delete Contact', 'email' => 'delete@customer.com']]),
    ], [], $organization);

    $this->actingAs($organization->owner);

    $locationId = $customer->primaryLocation->id;

    $this->delete("/customers/{$customer->id}")->assertRedirect();

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    $this->assertDatabaseMissing('locations', ['id' => $locationId]);
});

test('cannot delete customer with invoices', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([
        'name' => 'Customer With Invoice',
        'emails' => new ContactCollection([['name' => 'Test', 'email' => 'test@test.com']]),
    ], [], $organization);

    createInvoiceWithItems([], null, $organization, $customer);

    $this->actingAs($organization->owner);

    $this->delete("/customers/{$customer->id}")->assertRedirect();

    $this->assertDatabaseHas('customers', ['id' => $customer->id]);
});

test('can add location to customer', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    $this->actingAs($organization->owner);

    $this->post("/customers/{$customer->id}/locations", [
        'name' => 'Branch Office',
        'address_line_1' => '789 Branch St',
        'city' => 'Branch City',
        'state' => 'Branch State',
        'country' => 'IN',
        'postal_code' => '99999',
    ])->assertRedirect();

    $this->assertDatabaseHas('locations', [
        'name' => 'Branch Office',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);
});

test('can update location', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $location = $customer->primaryLocation;

    $this->actingAs($organization->owner);

    $this->put("/customers/{$customer->id}/locations/{$location->id}", [
        'name' => 'Updated Office',
        'address_line_1' => '999 Updated St',
        'city' => 'Updated City',
        'state' => 'Updated State',
        'country' => 'IN',
    ])->assertRedirect();

    $location->refresh();
    expect($location->name)->toBe('Updated Office');
    expect($location->address_line_1)->toBe('999 Updated St');
});

test('can delete location', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    // Add a second location so we can delete one
    $secondLocation = Location::create([
        'name' => 'Second Office',
        'address_line_1' => '222 Second St',
        'city' => 'Second City',
        'state' => 'Second State',
        'country' => 'IN',
        'postal_code' => '11111',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    $this->actingAs($organization->owner);

    $this->delete("/customers/{$customer->id}/locations/{$secondLocation->id}")->assertRedirect();

    $this->assertDatabaseMissing('locations', ['id' => $secondLocation->id]);
});

test('cannot delete last location', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $location = $customer->primaryLocation;

    $this->actingAs($organization->owner);

    $this->delete("/customers/{$customer->id}/locations/{$location->id}")->assertRedirect();

    // Location should still exist
    $this->assertDatabaseHas('locations', ['id' => $location->id]);
});

test('can set primary location', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    $secondLocation = Location::create([
        'name' => 'Second Office',
        'address_line_1' => '222 Second St',
        'city' => 'Second City',
        'state' => 'Second State',
        'country' => 'IN',
        'postal_code' => '22222',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    $this->actingAs($organization->owner);

    $this->post("/customers/{$customer->id}/primary-location/{$secondLocation->id}")->assertRedirect();

    $customer->refresh();
    expect($customer->primary_location_id)->toBe($secondLocation->id);
});

test('first location is automatically set as primary', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    // Create a customer without location via controller
    $this->post('/customers', [
        'name' => 'No Location Customer',
        'currency' => 'INR',
        'contacts' => [['name' => 'Test', 'email' => 'test@example.test']],
    ]);

    $customer = Customer::where('name', 'No Location Customer')->first();
    expect($customer->primary_location_id)->toBeNull();

    // Add first location
    $this->post("/customers/{$customer->id}/locations", [
        'name' => 'First Office',
        'address_line_1' => '123 First St',
        'city' => 'First City',
        'state' => 'First State',
        'country' => 'IN',
    ]);

    $customer->refresh();
    expect($customer->primary_location_id)->not->toBeNull();
});

test('currencies are passed to index page', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->get('/customers')
        ->assertInertia(fn ($page) => $page
            ->has('currencies')
            ->has('countries')
        );
});
