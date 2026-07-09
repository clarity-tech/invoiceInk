<?php

use App\Enums\GstTreatment;
use App\Models\Customer;
use App\Models\Invoice;
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
        'phone' => '(415) 555-2671',
        'phone_country' => 'US',
        'currency' => 'INR',
        'contacts' => [
            ['name' => 'John Doe', 'email' => 'customer@test.com'],
        ],
    ])->assertRedirect();

    // Phone is normalized to E.164 and the picked country is remembered.
    $this->assertDatabaseHas('customers', [
        'name' => 'Test Customer Corp',
        'phone' => '+14155552671',
        'phone_country' => 'US',
    ]);
});

test('rejects an invalid phone number when creating customer', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => 'Bad Phone Corp',
        'phone' => '123',
        'phone_country' => 'US',
        'currency' => 'INR',
        'contacts' => [
            ['name' => 'John Doe', 'email' => 'customer@test.com'],
        ],
    ])->assertSessionHasErrors('phone');

    $this->assertDatabaseMissing('customers', ['name' => 'Bad Phone Corp']);
});

test('stores email when contact name is empty', function () {
    // Regression: ConvertEmptyStringsToNull middleware turned name '' into
    // null, and ContactCollection silently dropped the entire contact.
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => 'Anonymous Contact Customer',
        'currency' => 'INR',
        'contacts' => [
            ['name' => '', 'email' => 'nameless@customer.test'],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $customer = Customer::where('name', 'Anonymous Contact Customer')->firstOrFail();
    expect($customer->emails->getEmails())->toBe(['nameless@customer.test']);
    expect($customer->emails->getNames())->toBe(['']);
});

test('persists the place of supply', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => 'GST Customer',
        'currency' => 'INR',
        'place_of_supply' => '27',
        'contacts' => [['name' => 'A', 'email' => 'a@customer.test']],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Customer::where('name', 'GST Customer')->first()->place_of_supply)->toBe('27');
});

test('rejects an invalid place of supply code', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => 'Bad POS Customer',
        'currency' => 'INR',
        'place_of_supply' => '99',
        'contacts' => [['name' => 'A', 'email' => 'a@customer.test']],
    ])->assertSessionHasErrors('place_of_supply');

    expect(Customer::where('name', 'Bad POS Customer')->exists())->toBeFalse();
});

test('index exposes Indian state options for an Indian organization', function () {
    $user = createUserWithTeam([], ['country_code' => 'IN']);
    $this->actingAs($user);

    $this->get('/customers')
        ->assertInertia(fn ($page) => $page
            ->where('indianStates.27', 'Maharashtra (27)')
        );
});

test('index sends no Indian state options for a non-Indian organization', function () {
    $user = createUserWithTeam([], ['country_code' => 'US']);
    $this->actingAs($user);

    $this->get('/customers')
        ->assertInertia(fn ($page) => $page->where('indianStates', []));
});

test('persists the gst treatment and defaults to domestic', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => 'Default Treatment Customer',
        'currency' => 'INR',
        'contacts' => [['name' => 'A', 'email' => 'a@customer.test']],
    ])->assertRedirect();

    expect(Customer::where('name', 'Default Treatment Customer')->first()->gst_treatment)
        ->toBe(GstTreatment::Domestic);
});

test('clears place of supply when the customer is overseas', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $this->post('/customers', [
        'name' => 'Overseas Customer',
        'currency' => 'USD',
        'gst_treatment' => 'overseas',
        'place_of_supply' => '27',
        'contacts' => [['name' => 'A', 'email' => 'a@customer.test']],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $customer = Customer::where('name', 'Overseas Customer')->first();
    expect($customer->isOverseas())->toBeTrue();
    expect($customer->place_of_supply)->toBeNull();
});

test('index exposes GST treatment options for an Indian organization', function () {
    $user = createUserWithTeam([], ['country_code' => 'IN']);
    $this->actingAs($user);

    $this->get('/customers')
        ->assertInertia(fn ($page) => $page
            ->where('gstTreatments.overseas', 'Overseas')
            ->where('gstTreatments.domestic', 'Domestic')
        );
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
        'phone' => '020 7183 8750',
        'phone_country' => 'GB',
        'currency' => 'INR',
        'contacts' => [
            ['name' => 'Updated Contact', 'email' => 'updated@customer.com'],
        ],
    ])->assertRedirect();

    $customer->refresh();
    expect($customer->name)->toBe('Updated Customer');
    expect($customer->phone)->toBe('+442071838750');
    expect($customer->phone_country)->toBe('GB');
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

test('can soft delete customer and locations remain', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([
        'name' => 'Customer to Delete',
        'emails' => new ContactCollection([['name' => 'Delete Contact', 'email' => 'delete@customer.com']]),
    ], [], $organization);

    $this->actingAs($organization->owner);

    $locationId = $customer->primaryLocation->id;

    $this->delete("/customers/{$customer->id}")->assertRedirect();

    expect(Customer::find($customer->id))->toBeNull();
    expect(Customer::withTrashed()->find($customer->id)?->trashed())->toBeTrue();
    $this->assertDatabaseHas('locations', ['id' => $locationId]);
});

test('can soft delete customer with invoices', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([
        'name' => 'Customer With Invoice',
        'emails' => new ContactCollection([['name' => 'Test', 'email' => 'test@test.com']]),
    ], [], $organization);

    createInvoiceWithItems([], null, $organization, $customer);

    $this->actingAs($organization->owner);

    $this->delete("/customers/{$customer->id}")->assertRedirect();

    expect(Customer::find($customer->id))->toBeNull();
    expect(Customer::withTrashed()->find($customer->id)?->trashed())->toBeTrue();
});

test('can render customers trash page listing soft-deleted customers', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $live = createCustomerWithLocation(['name' => 'Live'], [], $organization);
    $trashed = createCustomerWithLocation(['name' => 'Trashed'], [], $organization);
    $trashed->delete();

    $response = $this->get('/customers/trash');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Customers/Trash')
        ->has('customers.data', 1)
        ->where('customers.data.0.id', $trashed->id)
    );
});

test('can restore a soft-deleted customer', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $customer = createCustomerWithLocation([], [], $organization);
    $customer->delete();

    $this->post("/customers/{$customer->id}/restore")->assertRedirect();

    expect(Customer::find($customer->id))->not->toBeNull();
    expect(Customer::find($customer->id)->trashed())->toBeFalse();
});

test('can permanently delete a customer without invoices', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $customer = createCustomerWithLocation([], [], $organization);
    $customerId = $customer->id;
    $locationId = $customer->primaryLocation->id;
    $customer->delete();

    $this->delete("/customers/{$customerId}/force")->assertRedirect();

    expect(Customer::withTrashed()->find($customerId))->toBeNull();
    $this->assertDatabaseMissing('locations', ['id' => $locationId]);
});

test('cannot permanently delete a customer with invoices', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $customer = createCustomerWithLocation([], [], $organization);
    createInvoiceWithItems([], null, $organization, $customer);
    $customer->delete();

    $this->delete("/customers/{$customer->id}/force")->assertRedirect();

    expect(Customer::withTrashed()->find($customer->id))->not->toBeNull();
});

test('invoice list still shows customer name after customer is soft-deleted', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation(['name' => 'Will Be Trashed'], [], $organization);
    $invoice = createInvoiceWithItems([], null, $organization, $customer);
    $customer->delete();

    $this->actingAs($organization->owner);

    $loadedInvoice = Invoice::with('customer')->find($invoice->id);
    expect($loadedInvoice->customer)->not->toBeNull();
    expect($loadedInvoice->customer->name)->toBe('Will Be Trashed');
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

test('can soft delete location', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

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

    expect(Location::find($secondLocation->id))->toBeNull();
    expect(Location::withTrashed()->find($secondLocation->id)?->trashed())->toBeTrue();
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

test('soft deleting primary location reassigns primary to another active location', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $primaryLocation = $customer->primaryLocation;

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

    createInvoiceWithItems([], null, $organization, $customer);

    $this->actingAs($organization->owner);

    $this->delete("/customers/{$customer->id}/locations/{$primaryLocation->id}")
        ->assertRedirect();

    expect(Location::find($primaryLocation->id))->toBeNull();
    expect(Location::withTrashed()->find($primaryLocation->id)?->trashed())->toBeTrue();
    expect($customer->fresh()->primary_location_id)->toBe($secondLocation->id);
});

test('can soft delete shipping location referenced by invoices', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    $shippingLocation = Location::create([
        'name' => 'Warehouse',
        'address_line_1' => '500 Warehouse Ln',
        'city' => 'Pune',
        'state' => 'MH',
        'country' => 'IN',
        'postal_code' => '411001',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    createInvoiceWithItems([
        'customer_shipping_location_id' => $shippingLocation->id,
    ], null, $organization, $customer);

    $this->actingAs($organization->owner);

    $this->delete("/customers/{$customer->id}/locations/{$shippingLocation->id}")
        ->assertRedirect();

    expect(Location::find($shippingLocation->id))->toBeNull();
    expect(Location::withTrashed()->find($shippingLocation->id)?->trashed())->toBeTrue();
});

test('can restore an archived location', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    $secondLocation = Location::create([
        'name' => 'Branch',
        'address_line_1' => '1 Branch Rd',
        'city' => 'Branch City',
        'state' => 'Branch State',
        'country' => 'IN',
        'postal_code' => '11111',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);
    $secondLocation->delete();

    $this->actingAs($organization->owner);

    $this->post("/customers/{$customer->id}/locations/{$secondLocation->id}/restore")
        ->assertRedirect();

    expect(Location::find($secondLocation->id))->not->toBeNull();
    expect(Location::find($secondLocation->id)->trashed())->toBeFalse();
});

test('can force delete archived location not referenced by invoices', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    $secondLocation = Location::create([
        'name' => 'Branch',
        'address_line_1' => '1 Branch Rd',
        'city' => 'Branch City',
        'state' => 'Branch State',
        'country' => 'IN',
        'postal_code' => '11111',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);
    $secondLocation->delete();

    $this->actingAs($organization->owner);

    $this->delete("/customers/{$customer->id}/locations/{$secondLocation->id}/force")
        ->assertRedirect();

    expect(Location::withTrashed()->find($secondLocation->id))->toBeNull();
});

test('cannot force delete archived location still referenced by invoices', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    $secondLocation = Location::create([
        'name' => 'Branch',
        'address_line_1' => '1 Branch Rd',
        'city' => 'Branch City',
        'state' => 'Branch State',
        'country' => 'IN',
        'postal_code' => '11111',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    createInvoiceWithItems([
        'customer_shipping_location_id' => $secondLocation->id,
    ], null, $organization, $customer);

    $secondLocation->delete();

    $this->actingAs($organization->owner);

    $this->delete("/customers/{$customer->id}/locations/{$secondLocation->id}/force")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Location::withTrashed()->find($secondLocation->id))->not->toBeNull();
});

test('invoice resolves customer location even after location is archived', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $location = $customer->primaryLocation;
    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    Location::create([
        'name' => 'Spare',
        'address_line_1' => '1 Spare Rd',
        'city' => 'Spare City',
        'state' => 'Spare State',
        'country' => 'IN',
        'postal_code' => '11111',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    $this->actingAs($organization->owner);
    $this->delete("/customers/{$customer->id}/locations/{$location->id}")->assertRedirect();

    $loaded = Invoice::with('customerLocation')->find($invoice->id);
    expect($loaded->customerLocation)->not->toBeNull();
    expect($loaded->customerLocation->id)->toBe($location->id);
});

test('invoice edit page includes archived referenced location in customer locations', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $location = $customer->primaryLocation;

    Location::create([
        'name' => 'Active',
        'address_line_1' => '1 Active Rd',
        'city' => 'Active City',
        'state' => 'Active State',
        'country' => 'IN',
        'postal_code' => '11111',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    $invoice = createInvoiceWithItems([], null, $organization, $customer);

    $this->actingAs($organization->owner);
    $this->delete("/customers/{$customer->id}/locations/{$location->id}")->assertRedirect();

    $response = $this->get("/invoices/{$invoice->id}/edit");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('customers', function ($customers) use ($customer, $location) {
            $target = collect($customers)->firstWhere('id', $customer->id);

            return collect($target['locations'])->contains(fn ($loc) => $loc['id'] === $location->id && ! empty($loc['deleted_at']));
        })
    );
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
            ->has('taxLabels')
            ->where('taxLabels.IN', 'GSTIN')
            ->where('taxLabels.US', 'EIN')
            ->where('taxLabels.AE', 'TRN')
        );
});
