<?php

use App\Livewire\CustomerManager;
use App\Models\Customer;
use App\Models\Location;
use App\ValueObjects\EmailCollection;
use Livewire\Livewire;

test('can render customer manager component', function () {
    Livewire::test(CustomerManager::class)
        ->assertStatus(200)
        ->assertSee('Customers');
});

test('can load customers with pagination', function () {
    // Create test customers
    $customers = collect();
    for ($i = 1; $i <= 12; $i++) {
        $customers->push(createCustomerWithLocation([
            'name' => "Customer {$i}",
            'emails' => new EmailCollection(["customer{$i}@test.com"]),
        ]));
    }

    Livewire::test(CustomerManager::class)
        ->assertSee('Customer 1')
        ->assertSee('Customer 10')
        ->assertDontSee('Customer 11') // Should be on page 2
        ->assertSee('Next');
});

test('can show create form', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSee('Customer Name')
        ->assertSee('Location Name');
});

test('can add and remove email fields', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->assertCount('emails', 1)
        ->call('addEmailField')
        ->assertCount('emails', 2)
        ->call('addEmailField')
        ->assertCount('emails', 3)
        ->call('removeEmailField', 1)
        ->assertCount('emails', 2);
});

test('cannot remove last email field', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->assertCount('emails', 1)
        ->call('removeEmailField', 0)
        ->assertCount('emails', 1); // Should still have 1
});

test('can create new customer with location', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'Test Customer Corp')
        ->set('phone', '+1234567890')
        ->set('emails.0', 'customer@test.com')
        ->set('location_name', 'Main Office')
        ->set('gstin', '29BBBBB1111B2Z6')
        ->set('address_line_1', '456 Customer Ave')
        ->set('address_line_2', 'Floor 2')
        ->set('city', 'Bangalore')
        ->set('state', 'Karnataka')
        ->set('country', 'India')
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
        'country' => 'India',
        'postal_code' => '560001',
        'locatable_type' => Customer::class,
    ]);
});

test('can create customer with multiple emails', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'Multi Email Customer')
        ->set('emails.0', 'primary@customer.com')
        ->call('addEmailField')
        ->set('emails.1', 'billing@customer.com')
        ->call('addEmailField')
        ->set('emails.2', 'support@customer.com')
        ->set('location_name', 'Customer Office')
        ->set('address_line_1', '789 Customer St')
        ->set('city', 'Customer City')
        ->set('state', 'Customer State')
        ->set('country', 'Test Country')
        ->set('postal_code', '54321')
        ->call('save')
        ->assertSee('Customer created successfully!');

    $customer = Customer::where('name', 'Multi Email Customer')->first();
    expect($customer->emails->toArray())->toBe([
        'primary@customer.com',
        'billing@customer.com', 
        'support@customer.com'
    ]);
});

test('validates required fields when creating customer', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'location_name' => 'required',
            'address_line_1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'postal_code' => 'required',
        ]);
});

test('validates email format', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('emails.0', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['emails.0' => 'email']);
});

test('requires at least one non-empty email', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'Test Customer')
        ->set('emails.0', '')
        ->set('location_name', 'Office')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save')
        ->assertHasErrors(['emails.0' => 'At least one email is required.']);
});

test('can edit existing customer', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Original Customer',
        'phone' => '+2222222222',
        'emails' => new EmailCollection(['original@customer.com']),
    ], [
        'name' => 'Original Customer Office',
        'gstin' => '29BBBBB1111B2Z6',
        'address_line_1' => '789 Original Ave',
        'city' => 'Original City',
        'state' => 'Original State',
        'country' => 'Original Country',
        'postal_code' => '54321',
    ]);

    Livewire::test(CustomerManager::class)
        ->call('edit', $customer)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $customer->id)
        ->assertSet('name', 'Original Customer')
        ->assertSet('phone', '+2222222222')
        ->assertSet('emails.0', 'original@customer.com')
        ->assertSet('location_name', 'Original Customer Office')
        ->assertSet('gstin', '29BBBBB1111B2Z6')
        ->assertSet('address_line_1', '789 Original Ave')
        ->assertSet('city', 'Original City');
});

test('can update existing customer', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Original Customer',
        'emails' => new EmailCollection(['original@customer.com']),
    ]);

    Livewire::test(CustomerManager::class)
        ->call('edit', $customer)
        ->set('name', 'Updated Customer')
        ->set('phone', '+8888888888')
        ->set('emails.0', 'updated@customer.com')
        ->set('location_name', 'Updated Customer Office')
        ->call('save')
        ->assertSet('showForm', false);

    $customer->refresh();
    expect($customer->name)->toBe('Updated Customer');
    expect($customer->phone)->toBe('+8888888888');
    expect($customer->emails->toArray())->toBe(['updated@customer.com']);
    expect($customer->primaryLocation->name)->toBe('Updated Customer Office');
});

test('can delete customer', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Customer to Delete',
        'emails' => new EmailCollection(['delete@customer.com']),
    ]);

    Livewire::test(CustomerManager::class)
        ->call('delete', $customer)
        ->assertSee('Customer deleted successfully!');

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    $this->assertDatabaseMissing('locations', ['id' => $customer->primaryLocation->id]);
});

test('can cancel form', function () {
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
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'Test Customer')
        ->set('emails.0', 'test@customer.com')
        ->set('location_name', 'Test Office')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('emails', [''])
        ->assertSet('location_name', '')
        ->assertSet('editingId', null);
});

test('handles customer without primary location when editing', function () {
    $location = Location::create([
        'name' => 'Test Location',
        'address_line_1' => '123 Test St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Customer::class,
        'locatable_id' => 0,
    ]);

    $customer = Customer::create([
        'name' => 'No Location Customer',
        'emails' => new EmailCollection(['test@customer.com']),
        'primary_location_id' => null,
    ]);

    Livewire::test(CustomerManager::class)
        ->call('edit', $customer)
        ->assertSet('showForm', true)
        ->assertSet('name', 'No Location Customer')
        ->assertSet('location_name', '')
        ->assertSet('address_line_1', '');
});