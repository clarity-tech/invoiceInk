<?php

use App\Models\Customer;
use App\Models\Location;
use App\ValueObjects\EmailCollection;

test('can create customer with emails', function () {
    $emails = new EmailCollection(['customer@test.com', 'billing@test.com']);
    
    $customer = createCustomerWithLocation([
        'name' => 'Test Customer',
        'phone' => '+1234567890',
        'emails' => $emails,
    ]);

    expect($customer->name)->toBe('Test Customer');
    expect($customer->phone)->toBe('+1234567890');
    expect($customer->emails)->toBeInstanceOf(EmailCollection::class);
    expect($customer->emails->toArray())->toBe(['customer@test.com', 'billing@test.com']);
});

test('customer emails are cast to EmailCollection', function () {
    $customer = new Customer([
        'name' => 'Test Customer',
        'emails' => ['customer@test.com'],
    ]);

    expect($customer->emails)->toBeInstanceOf(EmailCollection::class);
});

test('customer can have primary location relationship', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Test Customer',
        'emails' => new EmailCollection(['customer@test.com']),
    ], [
        'name' => 'Customer Office',
        'address_line_1' => '789 Customer St',
        'city' => 'Customer City',
        'state' => 'Customer State',
    ]);

    expect($customer->primaryLocation)->not->toBeNull();
    expect($customer->primaryLocation->name)->toBe('Customer Office');
});

test('customer can have multiple locations', function () {
    $customer = createCustomerWithLocation();

    // Create an additional location
    createLocation(Customer::class, $customer->id, [
        'name' => 'Branch Office',
        'address_line_1' => '456 Branch Ave',
        'city' => 'Branch City',
    ]);

    expect($customer->locations()->count())->toBe(2);
});

test('customer fillable attributes work correctly', function () {
    $data = [
        'name' => 'Test Customer',
        'phone' => '+1234567890',
        'emails' => new EmailCollection(['customer@test.com']),
        'primary_location_id' => 1,
    ];

    $customer = new Customer($data);

    expect($customer->name)->toBe('Test Customer');
    expect($customer->phone)->toBe('+1234567890');
    expect($customer->emails)->toBeInstanceOf(EmailCollection::class);
    expect($customer->primary_location_id)->toBe(1);
});

test('customer can be created without phone', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Test Customer',
        'emails' => new EmailCollection(['customer@test.com']),
    ]);

    expect($customer->phone)->toBeNull();
});

test('customer emails field uses EmailCollectionCast', function () {
    $casts = (new Customer())->getCasts();
    
    expect($casts['emails'])->toBe(\App\Casts\EmailCollectionCast::class);
});