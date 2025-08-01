<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Location;

test('can create location with all fields', function () {
    $location = Location::create([
        'name' => 'Test Office',
        'gstin' => '27AAAAA0000A1Z5',
        'address_line_1' => '123 Test Street',
        'address_line_2' => 'Suite 100',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Company::class,
        'locatable_id' => 1,
    ]);

    expect($location->name)->toBe('Test Office');
    expect($location->gstin)->toBe('27AAAAA0000A1Z5');
    expect($location->address_line_1)->toBe('123 Test Street');
    expect($location->address_line_2)->toBe('Suite 100');
    expect($location->city)->toBe('Test City');
    expect($location->state)->toBe('Test State');
    expect($location->country)->toBe('Test Country');
    expect($location->postal_code)->toBe('12345');
    expect($location->locatable_type)->toBe(Company::class);
    expect($location->locatable_id)->toBe(1);
});

test('can create location with minimal required fields', function () {
    $location = Location::create([
        'name' => 'Minimal Office',
        'address_line_1' => '456 Minimal St',
        'city' => 'Minimal City',
        'state' => 'Minimal State',
        'country' => 'Minimal Country',
        'postal_code' => '67890',
        'locatable_type' => Customer::class,
        'locatable_id' => 1,
    ]);

    expect($location->name)->toBe('Minimal Office');
    expect($location->gstin)->toBeNull();
    expect($location->address_line_2)->toBeNull();
});

test('location belongs to company through polymorphic relationship', function () {
    $company = createCompanyWithLocation([
        'name' => 'Test Company',
        'emails' => new \App\ValueObjects\EmailCollection(['test@company.com']),
        'primary_location_id' => 1,
    ]);

    $location = Location::create([
        'name' => 'Company Office',
        'address_line_1' => '123 Company St',
        'city' => 'Company City',
        'state' => 'Company State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Company::class,
        'locatable_id' => $company->id,
    ]);

    expect($location->locatable)->toBeInstanceOf(Company::class);
    expect($location->locatable->name)->toBe('Test Company');
});

test('location belongs to customer through polymorphic relationship', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Test Customer',
        'emails' => new \App\ValueObjects\EmailCollection(['test@customer.com']),
        'primary_location_id' => 1,
    ]);

    $location = Location::create([
        'name' => 'Customer Office',
        'address_line_1' => '789 Customer Ave',
        'city' => 'Customer City',
        'state' => 'Customer State',
        'country' => 'Test Country',
        'postal_code' => '54321',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    expect($location->locatable)->toBeInstanceOf(Customer::class);
    expect($location->locatable->name)->toBe('Test Customer');
});

test('location fillable attributes work correctly', function () {
    $data = [
        'name' => 'Test Location',
        'gstin' => '27AAAAA0000A1Z5',
        'address_line_1' => '123 Test St',
        'address_line_2' => 'Floor 2',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Company::class,
        'locatable_id' => 1,
    ];

    $location = new Location($data);

    expect($location->name)->toBe('Test Location');
    expect($location->gstin)->toBe('27AAAAA0000A1Z5');
    expect($location->address_line_1)->toBe('123 Test St');
    expect($location->address_line_2)->toBe('Floor 2');
    expect($location->locatable_type)->toBe(Company::class);
    expect($location->locatable_id)->toBe(1);
});

test('location polymorphic relationship works with different models', function () {
    // Test with Company
    $company = createCompanyWithLocation([
        'name' => 'Test Company',
        'emails' => new \App\ValueObjects\EmailCollection(['company@test.com']),
        'primary_location_id' => 1,
    ]);

    $companyLocation = Location::create([
        'name' => 'Company HQ',
        'address_line_1' => '123 Business St',
        'city' => 'Business City',
        'state' => 'Business State',
        'country' => 'Test Country',
        'postal_code' => '11111',
        'locatable_type' => Company::class,
        'locatable_id' => $company->id,
    ]);

    // Test with Customer
    $customer = createCustomerWithLocation([
        'name' => 'Test Customer',
        'emails' => new \App\ValueObjects\EmailCollection(['customer@test.com']),
        'primary_location_id' => 1,
    ]);

    $customerLocation = Location::create([
        'name' => 'Customer Office',
        'address_line_1' => '456 Client Ave',
        'city' => 'Client City',
        'state' => 'Client State',
        'country' => 'Test Country',
        'postal_code' => '22222',
        'locatable_type' => Customer::class,
        'locatable_id' => $customer->id,
    ]);

    expect($companyLocation->locatable_type)->toBe(Company::class);
    expect($customerLocation->locatable_type)->toBe(Customer::class);
    expect($companyLocation->locatable->name)->toBe('Test Company');
    expect($customerLocation->locatable->name)->toBe('Test Customer');
});