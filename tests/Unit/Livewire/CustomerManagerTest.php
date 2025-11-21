<?php

use App\Livewire\CustomerManager;
use App\Models\Customer;
use App\Models\Location;
use App\ValueObjects\ContactCollection;
use Livewire\Livewire;

test('can render customer manager component', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->assertStatus(200)
        ->assertSee('Customers');
});

test('can load customers with pagination', function () {
    // Create a test organization with location for proper isolation
    $organization = createOrganizationWithLocation();

    // Create test customers in this specific organization
    $customers = collect();
    for ($i = 1; $i <= 12; $i++) {
        $customers->push(createCustomerWithLocation([
            'name' => "TestCustomer_{$i}_".uniqid(), // Unique names to avoid conflicts
            'emails' => new ContactCollection([["name" => "Contact {$i}", "email" => "testcustomer{$i}@example.test"]]),
        ], [], $organization));
    }

    // Authenticate as the organization owner to test scoping
    $this->actingAs($organization->owner);

    $component = Livewire::test(CustomerManager::class);

    // Verify we have the expected total customers in the organization
    expect(Customer::where('organization_id', $organization->id)->count())->toBe(12);

    // Test pagination behavior
    $component
        ->assertSee('TestCustomer_1_') // Should see first customer (latest first)
        ->assertSee('TestCustomer_10_') // Should see 10th customer on first page
        ->assertDontSee('TestCustomer_11_') // Should NOT see 11th customer (on page 2)
        ->assertDontSee('TestCustomer_12_') // Should NOT see 12th customer (on page 2)
        ->assertSee('Next'); // Should have next page button

    // Verify pagination structure
    $pagination = $component->get('customers');
    expect($pagination->total())->toBe(12);
    expect($pagination->perPage())->toBe(10);
    expect($pagination->currentPage())->toBe(1);
    expect($pagination->hasPages())->toBeTrue();
});

test('can show create form', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSee('Customer Name')
        ->assertSee('Location Name');
});

test('can add and remove contact fields', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->assertCount('contacts', 1)
        ->call('addContactField')
        ->assertCount('contacts', 2)
        ->call('addContactField')
        ->assertCount('contacts', 3)
        ->call('removeContactField', 1)
        ->assertCount('contacts', 2);
});

test('cannot remove last contact field', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->assertCount('contacts', 1)
        ->call('removeContactField', 0)
        ->assertCount('contacts', 1); // Should still have 1
});

test('can create new customer with location', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'Test Customer Corp')
        ->set('phone', '+1234567890')
        ->set('contacts.0.name', 'John Doe')
        ->set('contacts.0.email', 'customer@test.com')
        ->set('location_name', 'Main Office')
        ->set('gstin', '29BBBBB1111B2Z6')
        ->set('address_line_1', '456 Customer Ave')
        ->set('address_line_2', 'Floor 2')
        ->set('city', 'Bangalore')
        ->set('state', 'Karnataka')
        ->set('country', 'IN')
        ->set('postal_code', '560001')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertSee('Customer created successfully!');

    $this->assertDatabaseHas('customers', [
        'name' => 'Test Customer Corp',
        'phone' => '+1234567890',
    ]);

    $this->assertDatabaseHas('locations', [
        'name' => 'Main Office',
        'gstin' => '29BBBBB1111B2Z6',
        'address_line_1' => '456 Customer Ave',
        'city' => 'Bangalore',
        'state' => 'Karnataka',
        'country' => 'IN',
        'postal_code' => '560001',
        'locatable_type' => Customer::class,
    ]);
});

test('can create customer with multiple contacts', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'Multi Contact Customer')
        ->set('contacts.0.name', 'John Doe')
        ->set('contacts.0.email', 'primary@customer.com')
        ->call('addContactField')
        ->set('contacts.1.name', 'Jane Smith')
        ->set('contacts.1.email', 'billing@customer.com')
        ->call('addContactField')
        ->set('contacts.2.name', 'Bob Johnson')
        ->set('contacts.2.email', 'support@customer.com')
        ->set('location_name', 'Customer Office')
        ->set('address_line_1', '789 Customer St')
        ->set('city', 'Customer City')
        ->set('state', 'Customer State')
        ->set('country', 'IN')
        ->set('postal_code', '54321')
        ->call('save')
        ->assertSee('Customer created successfully!');

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
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'address_line_1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'postal_code' => 'required',
        ]);
});

test('validates email format', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('contacts.0.email', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['contacts.0.email' => 'email']);
});

test('requires at least one non-empty contact email', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'Test Customer')
        ->set('contacts.0.email', '')
        ->set('location_name', 'Office')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'IN')
        ->set('postal_code', '12345')
        ->call('save')
        ->assertHasErrors(['contacts.0.email']);
});

test('can edit existing customer', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([
        'name' => 'Original Customer',
        'phone' => '+2222222222',
        'emails' => new ContactCollection([['name' => 'Original Contact', 'email' => 'original@customer.com']]),
    ], [
        'name' => 'Original Customer Office',
        'gstin' => '29BBBBB1111B2Z6',
        'address_line_1' => '789 Original Ave',
        'city' => 'Original City',
        'state' => 'Original State',
        'country' => 'IN',
        'postal_code' => '54321',
    ], $organization);

    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('edit', $customer)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $customer->id)
        ->assertSet('name', 'Original Customer')
        ->assertSet('phone', '+2222222222')
        ->assertSet('contacts.0.name', 'Original Contact')
        ->assertSet('contacts.0.email', 'original@customer.com')
        ->assertSet('location_name', 'Original Customer Office')
        ->assertSet('gstin', '29BBBBB1111B2Z6')
        ->assertSet('address_line_1', '789 Original Ave')
        ->assertSet('city', 'Original City');
});

test('can update existing customer', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([
        'name' => 'Original Customer',
        'emails' => new ContactCollection([['name' => 'Original Contact', 'email' => 'original@customer.com']]),
    ], [], $organization);

    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('edit', $customer)
        ->set('name', 'Updated Customer')
        ->set('phone', '+8888888888')
        ->set('contacts.0.name', 'Updated Contact')
        ->set('contacts.0.email', 'updated@customer.com')
        ->set('location_name', 'Updated Customer Office')
        ->call('save')
        ->assertSet('showForm', false);

    $customer->refresh();
    expect($customer->name)->toBe('Updated Customer');
    expect($customer->phone)->toBe('+8888888888');
    expect($customer->emails->getEmails())->toBe(['updated@customer.com']);
    expect($customer->emails->getNames())->toBe(['Updated Contact']);
    expect($customer->primaryLocation->name)->toBe('Updated Customer Office');
});

test('can delete customer', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([
        'name' => 'Customer to Delete',
        'emails' => new ContactCollection([['name' => 'Delete Contact', 'email' => 'delete@customer.com']]),
    ], [], $organization);

    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('delete', $customer)
        ->assertSee('Customer deleted successfully!');

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    $this->assertDatabaseMissing('locations', ['id' => $customer->primaryLocation->id]);
});

test('can cancel form', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'Test Customer')
        ->assertSet('showForm', true)
        ->call('cancel')
        ->assertSet('showForm', false)
        ->assertSet('name', '')
        ->assertSet('editingId', null);
});

test('resets form after successful save', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'Test Customer')
        ->set('contacts.0.name', 'Test Contact')
        ->set('contacts.0.email', 'test@customer.com')
        ->set('location_name', 'Test Office')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'IN')
        ->set('postal_code', '12345')
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('contacts', [['name' => '', 'email' => '']])
        ->assertSet('location_name', '')
        ->assertSet('editingId', null);
});

test('handles customer without primary location when editing', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $location = Location::create([
        'name' => 'Test Location',
        'address_line_1' => '123 Test St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'IN',
        'postal_code' => '12345',
        'locatable_type' => Customer::class,
        'locatable_id' => 0,
    ]);

    $customer = Customer::create([
        'name' => 'No Location Customer',
        'emails' => new ContactCollection([['name' => 'Test Contact', 'email' => 'test@customer.com']]),
        'primary_location_id' => null,
        'organization_id' => $user->currentTeam->id,
    ]);

    Livewire::test(CustomerManager::class)
        ->call('edit', $customer)
        ->assertSet('showForm', true)
        ->assertSet('name', 'No Location Customer')
        ->assertSet('location_name', '')
        ->assertSet('address_line_1', '');
});

test('uses customer name plus office as default when location name is empty', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'ABC Industries')
        ->set('contacts.0.name', 'ABC Contact')
        ->set('contacts.0.email', 'contact@abc.com')
        // Note: location_name is intentionally left empty
        ->set('address_line_1', '789 Industrial Ave')
        ->set('city', 'Factory Town')
        ->set('state', 'Manufacturing State')
        ->set('postal_code', '54321')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $customer = Customer::where('name', 'ABC Industries')->first();
    expect($customer->primaryLocation->name)->toBe('ABC Industries Office');
});
