<?php

use App\Livewire\CustomerManager;
use App\Models\Customer;
use App\ValueObjects\EmailCollection;
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

test('can manage email fields', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->assertCount('emails', 1)
        ->call('addEmailField')
        ->assertCount('emails', 2)
        ->call('removeEmailField', 1)
        ->assertCount('emails', 1);
});

test('loads customers through computed property', function () {
    createCustomerWithLocation([
        'name' => 'Test Customer',
        'emails' => new EmailCollection(['test@customer.com']),
    ]);

    $component = Livewire::test(CustomerManager::class);
    $customers = $component->instance()->customers;
    expect($customers->total())->toBeGreaterThan(0);
});

test('can populate form for editing', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Edit Customer',
        'phone' => '+9876543210',
        'emails' => new EmailCollection(['edit@customer.com']),
    ]);

    Livewire::test(CustomerManager::class)
        ->call('edit', $customer)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $customer->id)
        ->assertSet('name', 'Edit Customer')
        ->assertSet('phone', '+9876543210')
        ->assertSet('emails.0', 'edit@customer.com');
});

test('resets form correctly', function () {
    $component = Livewire::test(CustomerManager::class)
        ->set('name', 'Test Name')
        ->set('phone', '+1234567890')
        ->set('emails.0', 'test@test.com')
        ->call('cancel');

    expect($component->get('name'))->toBe('');
    expect($component->get('phone'))->toBe('');
    expect($component->get('emails'))->toBe(['']);
});

test('validates required fields', function () {
    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', '') // Empty required field
        ->set('emails.0', 'invalid-email') // Invalid email
        ->call('save')
        ->assertHasErrors(['name', 'emails.0']);
});

test('can create customer with valid data', function () {
    $initialCount = Customer::count();

    Livewire::test(CustomerManager::class)
        ->call('create')
        ->set('name', 'New Customer')
        ->set('emails.0', 'new@customer.com')
        ->set('location_name', 'Main Office')
        ->set('address_line_1', '456 Customer Ave')
        ->set('city', 'Customer City')
        ->set('state', 'Customer State')
        ->set('country', 'Test Country')
        ->set('postal_code', '54321')
        ->call('save');

    expect(Customer::count())->toBe($initialCount + 1);
    expect(Customer::latest()->first()->name)->toBe('New Customer');
});

test('can delete customer', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Delete Me',
        'emails' => new EmailCollection(['delete@customer.com']),
    ]);

    $initialCount = Customer::count();

    Livewire::test(CustomerManager::class)
        ->call('delete', $customer);

    expect(Customer::count())->toBe($initialCount - 1);
    expect(Customer::find($customer->id))->toBeNull();
});