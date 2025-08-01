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
    $casts = (new Company())->getCasts();
    
    expect($casts['emails'])->toBe(\App\Casts\EmailCollectionCast::class);
});