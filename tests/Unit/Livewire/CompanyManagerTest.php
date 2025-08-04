<?php

use App\Livewire\CompanyManager;
use App\Models\Company;
use App\Models\Location;
use App\ValueObjects\EmailCollection;
use Livewire\Livewire;

test('can render company manager component', function () {
    Livewire::test(CompanyManager::class)
        ->assertStatus(200)
        ->assertSee('Companies');
});

test('can load companies with pagination', function () {
    // Create test companies
    $companies = collect();
    for ($i = 1; $i <= 12; $i++) {
        $companies->push(createCompanyWithLocation([
            'name' => "Company {$i}",
            'emails' => new EmailCollection(["company{$i}@test.com"]),
        ]));
    }

    Livewire::test(CompanyManager::class)
        ->assertSee('Company 1')
        ->assertSee('Company 10')
        ->assertDontSee('Company 11') // Should be on page 2
        ->assertSee('Next');
});

test('can show create form', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSee('Company Name')
        ->assertSee('Location Name');
});

test('can add and remove email fields', function () {
    Livewire::test(CompanyManager::class)
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
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->assertCount('emails', 1)
        ->call('removeEmailField', 0)
        ->assertCount('emails', 1); // Should still have 1
});

test('can create new company with location', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->set('name', 'Test Company Ltd')
        ->set('phone', '+1234567890')
        ->set('emails.0', 'company@test.com')
        ->set('location_name', 'Head Office')
        ->set('gstin', '27AAAAA0000A1Z5')
        ->set('address_line_1', '123 Business St')
        ->set('address_line_2', 'Suite 100')
        ->set('city', 'Mumbai')
        ->set('state', 'Maharashtra')
        ->set('country', 'India')
        ->set('postal_code', '400001')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertSessionHas('message', 'Company created successfully!');

    $this->assertDatabaseHas('companies', [
        'name' => 'Test Company Ltd',
        'phone' => '+1234567890',
    ]);

    $this->assertDatabaseHas('locations', [
        'name' => 'Head Office',
        'gstin' => '27AAAAA0000A1Z5',
        'address_line_1' => '123 Business St',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'country' => 'India',
        'postal_code' => '400001',
        'locatable_type' => Company::class,
    ]);
});

test('can create company with multiple emails', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->set('name', 'Multi Email Company')
        ->set('emails.0', 'primary@test.com')
        ->call('addEmailField')
        ->set('emails.1', 'secondary@test.com')
        ->call('addEmailField')
        ->set('emails.2', 'billing@test.com')
        ->set('location_name', 'Office')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save')
        ->assertSessionHas('message', 'Company created successfully!');

    $company = Company::where('name', 'Multi Email Company')->first();
    expect($company->emails->toArray())->toBe([
        'primary@test.com',
        'secondary@test.com', 
        'billing@test.com'
    ]);
});

test('validates required fields when creating company', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'emails' => 'required',
            'location_name' => 'required',
            'address_line_1' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'postal_code' => 'required',
        ]);
});

test('validates email format', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->set('emails.0', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['emails.0' => 'email']);
});

test('requires at least one non-empty email', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->set('name', 'Test Company')
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

test('can edit existing company', function () {
    $company = createCompanyWithLocation([
        'name' => 'Original Company',
        'phone' => '+1111111111',
        'emails' => new EmailCollection(['original@test.com']),
    ], [
        'name' => 'Original Office',
        'gstin' => '27AAAAA0000A1Z5',
        'address_line_1' => '123 Original St',
        'city' => 'Original City',
        'state' => 'Original State',
        'country' => 'Original Country',
        'postal_code' => '12345',
    ]);

    Livewire::test(CompanyManager::class)
        ->call('edit', $company)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $company->id)
        ->assertSet('name', 'Original Company')
        ->assertSet('phone', '+1111111111')
        ->assertSet('emails.0', 'original@test.com')
        ->assertSet('location_name', 'Original Office')
        ->assertSet('gstin', '27AAAAA0000A1Z5')
        ->assertSet('address_line_1', '123 Original St')
        ->assertSet('city', 'Original City');
});

test('can update existing company', function () {
    $company = createCompanyWithLocation([
        'name' => 'Original Company',
        'emails' => new EmailCollection(['original@test.com']),
    ]);

    Livewire::test(CompanyManager::class)
        ->call('edit', $company)
        ->set('name', 'Updated Company')
        ->set('phone', '+9999999999')
        ->set('emails.0', 'updated@test.com')
        ->set('location_name', 'Updated Office')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertSessionHas('message', 'Company updated successfully!');

    $company->refresh();
    expect($company->name)->toBe('Updated Company');
    expect($company->phone)->toBe('+9999999999');
    expect($company->emails->toArray())->toBe(['updated@test.com']);
    expect($company->primaryLocation->name)->toBe('Updated Office');
});

test('can delete company', function () {
    $company = createCompanyWithLocation([
        'name' => 'Company to Delete',
        'emails' => new EmailCollection(['delete@test.com']),
    ]);

    Livewire::test(CompanyManager::class)
        ->call('delete', $company)
        ->assertSessionHas('message', 'Company deleted successfully!');

    $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    $this->assertDatabaseMissing('locations', ['id' => $company->primaryLocation->id]);
});

test('can cancel form', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->set('name', 'Test Company')
        ->assertSet('showForm', true)
        ->call('cancel')
        ->assertSet('showForm', false)
        ->assertSet('name', '')
        ->assertSet('editingId', null);
});

test('resets form after successful save', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->set('name', 'Test Company')
        ->set('emails.0', 'test@company.com')
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

test('handles company without primary location when editing', function () {
    $location = Location::create([
        'name' => 'Test Location',
        'address_line_1' => '123 Test St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Test Country',
        'postal_code' => '12345',
        'locatable_type' => Company::class,
        'locatable_id' => 0,
    ]);

    $company = Company::create([
        'name' => 'No Location Company',
        'emails' => new EmailCollection(['test@company.com']),
        'primary_location_id' => null,
    ]);

    Livewire::test(CompanyManager::class)
        ->call('edit', $company)
        ->assertSet('showForm', true)
        ->assertSet('name', 'No Location Company')
        ->assertSet('location_name', '')
        ->assertSet('address_line_1', '');
});