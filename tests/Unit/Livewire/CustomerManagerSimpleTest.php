<?php

use App\Livewire\CustomerManager;
use App\Models\Customer;
use App\ValueObjects\ContactCollection;
use Livewire\Livewire;

test('customer manager component loads', function () {
    $component = Livewire::test(CustomerManager::class);
    expect($component)->not->toBeNull();
});

test('can show and hide create form', function () {
    Livewire::test(CustomerManager::class)
        ->assertSet('showForm', false)
        ->call('create')
        ->assertSet('showForm', true)
        ->call('cancel')
        ->assertSet('showForm', false);
});

test('can manage contact fields', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->assertCount('contacts', 1)
        ->call('addContactField')
        ->assertCount('contacts', 2)
        ->call('removeContactField', 1)
        ->assertCount('contacts', 1);
});

test('loads customers through computed property', function () {
    createCustomerWithLocation([
        'name' => 'Test Customer',
        'emails' => new ContactCollection([['name' => 'Test Contact', 'email' => 'test@customer.com']]),
    ]);

    $component = Livewire::test(CustomerManager::class);
    $customers = $component->instance()->customers;
    expect($customers->total())->toBeGreaterThan(0);
});

test('can populate form for editing', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $customer = createCustomerWithLocation([
        'name' => 'Edit Customer',
        'phone' => '+9876543210',
        'emails' => new ContactCollection([['name' => 'Edit Contact', 'email' => 'edit@customer.com']]),
    ], [], $organization);

    Livewire::test(CustomerManager::class)
        ->call('edit', $customer)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $customer->id)
        ->assertSet('name', 'Edit Customer')
        ->assertSet('phone', '+9876543210')
        ->assertSet('contacts.0.name', 'Edit Contact')
        ->assertSet('contacts.0.email', 'edit@customer.com');
});

test('resets form correctly', function () {
    $component = Livewire::test(CustomerManager::class)
        ->set('name', 'Test Name')
        ->set('phone', '+1234567890')
        ->set('contacts.0.name', 'Test Contact')
        ->set('contacts.0.email', 'test@test.com')
        ->call('cancel');

    expect($component->get('name'))->toBe('');
    expect($component->get('phone'))->toBe('');
    expect($component->get('contacts'))->toBe([['name' => '', 'email' => '']]);
});

test('validates required fields', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', '') // Empty required field
        ->set('contacts.0.email', 'notanemail') // Invalid email
        ->call('save')
        ->assertHasErrors(['name', 'contacts.0.email']);
});

test('can create customer with valid data', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $initialCount = Customer::count();

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'New Customer')
        ->set('contacts.0.name', 'New Contact')
        ->set('contacts.0.email', 'new@customer.com')
        ->set('location_name', 'Main Office')
        ->set('address_line_1', '456 Customer Ave')
        ->set('city', 'Customer City')
        ->set('state', 'Customer State')
        ->set('country', 'IN')
        ->set('postal_code', '54321')
        ->call('save');

    expect(Customer::count())->toBe($initialCount + 1);
    expect(Customer::latest()->first()->name)->toBe('New Customer');
});

test('can delete customer', function () {
    $organization = createOrganizationWithLocation();
    $this->actingAs($organization->owner);

    $customer = createCustomerWithLocation([
        'name' => 'Delete Me',
        'emails' => new ContactCollection([['name' => 'Delete Contact', 'email' => 'delete@customer.com']]),
    ], [], $organization);

    $initialCount = Customer::count();

    Livewire::test(CustomerManager::class)
        ->call('delete', $customer);

    expect(Customer::count())->toBe($initialCount - 1);
    expect(Customer::find($customer->id))->toBeNull();
});
