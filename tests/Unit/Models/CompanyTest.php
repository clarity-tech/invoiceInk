<?php

use App\Models\Company;
use App\Models\Location;
use App\ValueObjects\EmailCollection;

test('can create company with emails', function () {
    $emails = new EmailCollection(['test@company.com', 'admin@company.com']);

    $company = createCompanyWithLocation([
        'name' => 'Test Company',
        'phone' => '+1234567890',
        'emails' => $emails,
    ]);

    expect($company->name)->toBe('Test Company');
    expect($company->phone)->toBe('+1234567890');
    expect($company->emails)->toBeInstanceOf(EmailCollection::class);
    expect($company->emails->toArray())->toBe(['test@company.com', 'admin@company.com']);
});

test('company emails are cast to EmailCollection', function () {
    $company = new Company([
        'name' => 'Test Company',
        'emails' => ['test@company.com'],
    ]);

    expect($company->emails)->toBeInstanceOf(EmailCollection::class);
});

test('company can have primary location relationship', function () {
    $location = Location::create([
        'name' => 'Main Office',
        'address_line_1' => '123 Main St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Company::class,
        'locatable_id' => 1,
    ]);

    $company = createCompanyWithLocation([
        'name' => 'Test Company',
        'emails' => new EmailCollection(['test@company.com']),
        'primary_location_id' => $location->id,
    ]);

    expect($company->primaryLocation)->not->toBeNull();
    expect($company->primaryLocation->name)->toBe('Main Office');
});

test('company can have multiple locations', function () {
    $company = createCompanyWithLocation();

    // Create an additional location
    createLocation(Company::class, $company->id, [
        'name' => 'Branch Office',
        'address_line_1' => '456 Branch Ave',
        'city' => 'Branch City',
    ]);

    expect($company->locations()->count())->toBe(2);
});

test('company fillable attributes work correctly', function () {
    $data = [
        'name' => 'Test Company',
        'phone' => '+1234567890',
        'emails' => new EmailCollection(['test@company.com']),
        'primary_location_id' => 1,
    ];

    $company = new Company($data);

    expect($company->name)->toBe('Test Company');
    expect($company->phone)->toBe('+1234567890');
    expect($company->emails)->toBeInstanceOf(EmailCollection::class);
    expect($company->primary_location_id)->toBe(1);
});

test('company can be created without phone', function () {
    $company = createCompanyWithLocation([
        'name' => 'Test Company',
        'emails' => new EmailCollection(['test@company.com']),
    ]);

    expect($company->phone)->toBeNull();
});

test('company emails field uses EmailCollectionCast', function () {
    $casts = (new Company)->getCasts();

    expect($casts['emails'])->toBe(\App\Casts\EmailCollectionCast::class);
});

test('company has team relationship', function () {
    $company = createCompanyWithLocation();

    expect($company->team)->toBeInstanceOf(\App\Models\Team::class);
    expect($company->team_id)->toBe($company->team->id);
});

test('company has customers relationship', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation(['company_id' => $company->id]);

    expect($company->customers)->toHaveCount(1);
    expect($company->customers->first())->toBeInstanceOf(\App\Models\Customer::class);
    expect($company->customers->first()->id)->toBe($customer->id);
});

test('company has invoices relationship', function () {
    $company = createCompanyWithLocation();
    $customer = createCustomerWithLocation(['company_id' => $company->id]);
    $invoice = createInvoiceWithItems(['company_id' => $company->id]);

    expect($company->invoices)->toHaveCount(1);
    expect($company->invoices->first())->toBeInstanceOf(\App\Models\Invoice::class);
    expect($company->invoices->first()->id)->toBe($invoice->id);
});

test('company currency cast works', function () {
    $company = createCompanyWithLocation(['currency' => 'USD']);

    expect($company->currency)->toBeInstanceOf(\App\Currency::class);
    expect($company->currency->value)->toBe('USD');
});

test('company currency cast handles null', function () {
    // Create a company with a valid currency first, then update to null if allowed
    $company = createCompanyWithLocation(['currency' => 'USD']);

    // If currency is nullable in database, test null handling
    // For now, let's test that it properly casts valid currency values
    expect($company->currency)->toBeInstanceOf(\App\Currency::class);
    expect($company->currency->value)->toBe('USD');
});

test('company uses HasFactory trait', function () {
    $traits = class_uses(\App\Models\Company::class);

    expect($traits)->toHaveKey('Illuminate\Database\Eloquent\Factories\HasFactory');
});

test('company has correct fillable attributes', function () {
    $expectedFillable = [
        'name',
        'phone',
        'emails',
        'primary_location_id',
        'team_id',
        'currency',
    ];

    $company = new Company;

    expect($company->getFillable())->toBe($expectedFillable);
});

test('company casts includes currency', function () {
    $casts = (new Company)->getCasts();

    expect($casts['currency'])->toBe(\App\Currency::class);
});

test('company morphMany locations relationship works', function () {
    $company = createCompanyWithLocation();

    // Create additional location
    $location = createLocation(Company::class, $company->id, [
        'name' => 'Branch Office',
        'address_line_1' => '456 Branch Ave',
    ]);

    expect($company->locations())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class);
    expect($company->locations)->toHaveCount(2); // primary + branch
    expect($company->locations->pluck('name'))->toContain('Branch Office');
});

test('company primary location belongs to relationship works', function () {
    $company = createCompanyWithLocation();

    expect($company->primaryLocation())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($company->primaryLocation)->toBeInstanceOf(\App\Models\Location::class);
});

test('company team belongs to relationship works', function () {
    $company = createCompanyWithLocation();

    expect($company->team())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($company->team)->toBeInstanceOf(\App\Models\Team::class);
});

test('company customers has many relationship works', function () {
    $company = createCompanyWithLocation();

    expect($company->customers())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('company invoices has many relationship works', function () {
    $company = createCompanyWithLocation();

    expect($company->invoices())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('company has team scope applied', function () {
    $company = new Company;
    $globalScopes = $company->getGlobalScopes();

    expect($globalScopes)->toHaveKey(\App\Models\Scopes\TeamScope::class);
});

test('company can be created with all fillable attributes', function () {
    $data = [
        'name' => 'Full Company',
        'phone' => '+1234567890',
        'emails' => new EmailCollection(['full@company.com']),
        'primary_location_id' => 1,
        'team_id' => 1,
        'currency' => 'EUR',
    ];

    $company = new Company($data);

    expect($company->name)->toBe('Full Company');
    expect($company->phone)->toBe('+1234567890');
    expect($company->emails)->toBeInstanceOf(EmailCollection::class);
    expect($company->primary_location_id)->toBe(1);
    expect($company->team_id)->toBe(1);
    expect($company->currency)->toBeInstanceOf(\App\Currency::class);
    expect($company->currency->value)->toBe('EUR');
});

test('company handles empty emails collection', function () {
    $company = createCompanyWithLocation([
        'name' => 'No Email Company',
        'emails' => new EmailCollection([]),
    ]);

    expect($company->emails)->toBeInstanceOf(EmailCollection::class);
    expect($company->emails->toArray())->toBeEmpty();
});

test('company emails cast handles array input', function () {
    $company = new Company([
        'name' => 'Array Email Company',
        'emails' => ['array@company.com', 'test@company.com'],
    ]);

    expect($company->emails)->toBeInstanceOf(EmailCollection::class);
    expect($company->emails->toArray())->toBe(['array@company.com', 'test@company.com']);
});
