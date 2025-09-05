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
    $casts = (new Customer)->getCasts();

    expect($casts['emails'])->toBe(\App\Casts\EmailCollectionCast::class);
});

test('customer has company relationship', function () {
    $customer = createCustomerWithLocation();

    expect($customer->company)->toBeInstanceOf(\App\Models\Company::class);
    expect($customer->company_id)->toBe($customer->company->id);
});

test('customer uses HasFactory trait', function () {
    $traits = class_uses(\App\Models\Customer::class);

    expect($traits)->toHaveKey('Illuminate\Database\Eloquent\Factories\HasFactory');
});

test('customer has correct fillable attributes', function () {
    $expectedFillable = [
        'name',
        'phone',
        'emails',
        'primary_location_id',
        'company_id',
    ];

    $customer = new Customer;

    expect($customer->getFillable())->toBe($expectedFillable);
});

test('customer morphMany locations relationship works', function () {
    $customer = createCustomerWithLocation();

    // Create additional location
    $location = createLocation(Customer::class, $customer->id, [
        'name' => 'Branch Office',
        'address_line_1' => '456 Branch Ave',
    ]);

    expect($customer->locations())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class);
    expect($customer->locations)->toHaveCount(2); // primary + branch
    expect($customer->locations->pluck('name'))->toContain('Branch Office');
});

test('customer primary location belongs to relationship works', function () {
    $customer = createCustomerWithLocation();

    expect($customer->primaryLocation())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($customer->primaryLocation)->toBeInstanceOf(\App\Models\Location::class);
});

test('customer company belongs to relationship works', function () {
    $customer = createCustomerWithLocation();

    expect($customer->company())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($customer->company)->toBeInstanceOf(\App\Models\Company::class);
});

test('customer has company scope applied', function () {
    $customer = new Customer;
    $globalScopes = $customer->getGlobalScopes();

    expect($globalScopes)->toHaveKey(\App\Models\Scopes\CompanyScope::class);
});

test('customer can be created with all fillable attributes', function () {
    $data = [
        'name' => 'Full Customer',
        'phone' => '+1234567890',
        'emails' => new EmailCollection(['full@customer.com']),
        'primary_location_id' => 1,
        'company_id' => 1,
    ];

    $customer = new Customer($data);

    expect($customer->name)->toBe('Full Customer');
    expect($customer->phone)->toBe('+1234567890');
    expect($customer->emails)->toBeInstanceOf(EmailCollection::class);
    expect($customer->primary_location_id)->toBe(1);
    expect($customer->company_id)->toBe(1);
});

test('customer handles empty emails collection', function () {
    $customer = createCustomerWithLocation([
        'name' => 'No Email Customer',
        'emails' => new EmailCollection([]),
    ]);

    expect($customer->emails)->toBeInstanceOf(EmailCollection::class);
    expect($customer->emails->toArray())->toBeEmpty();
});

test('customer emails cast handles array input', function () {
    $customer = new Customer([
        'name' => 'Array Email Customer',
        'emails' => ['array@customer.com', 'test@customer.com'],
    ]);

    expect($customer->emails)->toBeInstanceOf(EmailCollection::class);
    expect($customer->emails->toArray())->toBe(['array@customer.com', 'test@customer.com']);
});

test('customer emails cast handles string input', function () {
    $customer = new Customer([
        'name' => 'String Email Customer',
        'emails' => 'single@customer.com',
    ]);

    expect($customer->emails)->toBeInstanceOf(EmailCollection::class);
    expect($customer->emails->toArray())->toBe(['single@customer.com']);
});

test('customer emails cast handles null input', function () {
    $customer = new Customer([
        'name' => 'Null Email Customer',
        'emails' => null,
    ]);

    expect($customer->emails)->toBeInstanceOf(EmailCollection::class);
    expect($customer->emails->toArray())->toBeEmpty();
});

test('customer casts method returns correct array', function () {
    $casts = (new Customer)->getCasts();

    expect($casts)->toBeArray();
    expect($casts)->toHaveKey('emails');
    expect($casts['emails'])->toBe(\App\Casts\EmailCollectionCast::class);
});

test('customer locations polymorphic relationship is configured correctly', function () {
    $customer = createCustomerWithLocation();
    $location = $customer->locations()->first();

    expect($location->locatable_type)->toBe(Customer::class);
    expect($location->locatable_id)->toBe($customer->id);
    expect($location->locatable)->toBeInstanceOf(Customer::class);
    expect($location->locatable->id)->toBe($customer->id);
});

test('customer can have invoices through company', function () {
    $customer = createCustomerWithLocation();
    $invoice = createInvoiceWithItems([
        'company_id' => $customer->company_id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    expect($customer->company->invoices)->toHaveCount(1);
    expect($customer->company->invoices->first()->customer_location_id)->toBe($customer->primaryLocation->id);
});

test('customer belongs to correct company after creation', function () {
    $company = createCompanyWithLocation(['name' => 'Specific Company']);
    $customer = createCustomerWithLocation(['company_id' => $company->id]);

    expect($customer->company->id)->toBe($company->id);
    expect($customer->company->name)->toBe('Specific Company');
});
