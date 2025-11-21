<?php

use App\Models\Customer;
use App\Models\Location;
use App\ValueObjects\ContactCollection;

test('can create customer with contacts', function () {
    $contacts = new ContactCollection([
        ['name' => 'John Doe', 'email' => 'customer@test.com'],
        ['name' => 'Jane Smith', 'email' => 'billing@test.com']
    ]);

    $customer = createCustomerWithLocation([
        'name' => 'Test Customer',
        'phone' => '+1234567890',
        'emails' => $contacts,
    ]);

    expect($customer->name)->toBe('Test Customer');
    expect($customer->phone)->toBe('+1234567890');
    expect($customer->emails)->toBeInstanceOf(ContactCollection::class);
    expect($customer->emails->getEmails())->toBe(['customer@test.com', 'billing@test.com']);
    expect($customer->emails->getNames())->toBe(['John Doe', 'Jane Smith']);
});

test('customer emails are cast to ContactCollection', function () {
    $customer = new Customer([
        'name' => 'Test Customer',
        'emails' => [['name' => 'John Doe', 'email' => 'customer@test.com']],
    ]);

    expect($customer->emails)->toBeInstanceOf(ContactCollection::class);
});

test('customer can have primary location relationship', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Test Customer',
        'emails' => new ContactCollection([['name' => 'John Doe', 'email' => 'customer@test.com']]),
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
        'emails' => new ContactCollection([['name' => 'John Doe', 'email' => 'customer@test.com']]),
        'primary_location_id' => 1,
    ];

    $customer = new Customer($data);

    expect($customer->name)->toBe('Test Customer');
    expect($customer->phone)->toBe('+1234567890');
    expect($customer->emails)->toBeInstanceOf(ContactCollection::class);
    expect($customer->primary_location_id)->toBe(1);
});

test('customer can be created without phone', function () {
    $customer = createCustomerWithLocation([
        'name' => 'Test Customer',
        'emails' => new ContactCollection([['name' => 'John Doe', 'email' => 'customer@test.com']]),
    ]);

    expect($customer->phone)->toBeNull();
});

test('customer emails field uses ContactCollectionCast', function () {
    $casts = (new Customer)->getCasts();

    expect($casts['emails'])->toBe(\App\Casts\ContactCollectionCast::class);
});

test('customer has organization relationship', function () {
    $customer = createCustomerWithLocation();

    expect($customer->organization)->toBeInstanceOf(\App\Models\Organization::class);
    expect($customer->organization_id)->toBe($customer->organization->id);
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
        'organization_id',
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

test('customer organization belongs to relationship works', function () {
    $customer = createCustomerWithLocation();

    expect($customer->organization())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($customer->organization)->toBeInstanceOf(\App\Models\Organization::class);
});

test('customer has organization scope applied', function () {
    $customer = new Customer;
    $globalScopes = $customer->getGlobalScopes();

    expect($globalScopes)->toHaveKey(\App\Models\Scopes\OrganizationScope::class);
});

test('customer can be created with all fillable attributes', function () {
    $data = [
        'name' => 'Full Customer',
        'phone' => '+1234567890',
        'emails' => new ContactCollection([['name' => 'John Doe', 'email' => 'full@customer.com']]),
        'primary_location_id' => 1,
        'organization_id' => 1,
    ];

    $customer = new Customer($data);

    expect($customer->name)->toBe('Full Customer');
    expect($customer->phone)->toBe('+1234567890');
    expect($customer->emails)->toBeInstanceOf(ContactCollection::class);
    expect($customer->primary_location_id)->toBe(1);
    expect($customer->organization_id)->toBe(1);
});

test('customer handles empty contacts collection', function () {
    $customer = createCustomerWithLocation([
        'name' => 'No Email Customer',
        'emails' => new ContactCollection([]),
    ]);

    expect($customer->emails)->toBeInstanceOf(ContactCollection::class);
    expect($customer->emails->toArray())->toBeEmpty();
});

test('customer contacts cast handles array input', function () {
    $customer = new Customer([
        'name' => 'Array Contact Customer',
        'emails' => [
            ['name' => 'John Doe', 'email' => 'array@customer.com'],
            ['name' => 'Jane Smith', 'email' => 'test@customer.com']
        ],
    ]);

    expect($customer->emails)->toBeInstanceOf(ContactCollection::class);
    expect($customer->emails->getEmails())->toBe(['array@customer.com', 'test@customer.com']);
    expect($customer->emails->getNames())->toBe(['John Doe', 'Jane Smith']);
});

test('customer contacts cast handles backward compatibility with email arrays', function () {
    $customer = new Customer([
        'name' => 'Backward Compatible Customer',
        'emails' => ['single@customer.com', 'test@customer.com'],
    ]);

    expect($customer->emails)->toBeInstanceOf(ContactCollection::class);
    expect($customer->emails->getEmails())->toBe(['single@customer.com', 'test@customer.com']);
    expect($customer->emails->getNames())->toBe(['', '']); // Empty names for backward compatibility
});

test('customer contacts cast handles null input', function () {
    $customer = new Customer([
        'name' => 'Null Contact Customer',
        'emails' => null,
    ]);

    expect($customer->emails)->toBeInstanceOf(ContactCollection::class);
    expect($customer->emails->toArray())->toBeEmpty();
});

test('customer casts method returns correct array', function () {
    $casts = (new Customer)->getCasts();

    expect($casts)->toBeArray();
    expect($casts)->toHaveKey('emails');
    expect($casts['emails'])->toBe(\App\Casts\ContactCollectionCast::class);
});

test('customer locations polymorphic relationship is configured correctly', function () {
    $customer = createCustomerWithLocation();
    $location = $customer->locations()->first();

    expect($location->locatable_type)->toBe(Customer::class);
    expect($location->locatable_id)->toBe($customer->id);
    expect($location->locatable)->toBeInstanceOf(Customer::class);
    expect($location->locatable->id)->toBe($customer->id);
});

test('customer can have invoices through organization', function () {
    $customer = createCustomerWithLocation();
    $invoice = createInvoiceWithItems([
        'organization_id' => $customer->organization_id,
        'customer_location_id' => $customer->primaryLocation->id,
    ]);

    expect($customer->organization->invoices)->toHaveCount(1);
    expect($customer->organization->invoices->first()->customer_location_id)->toBe($customer->primaryLocation->id);
});

test('customer belongs to correct organization after creation', function () {
    $organization = createOrganizationWithLocation(['name' => 'Specific Organization']);
    $customer = createCustomerWithLocation(['organization_id' => $organization->id], [], $organization);

    expect($customer->organization->id)->toBe($organization->id);
    expect($customer->organization->name)->toBe('Specific Organization');
});
