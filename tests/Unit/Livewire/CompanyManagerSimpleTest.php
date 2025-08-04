<?php

use App\Livewire\CompanyManager;
use App\Models\Company;
use App\ValueObjects\EmailCollection;
use Livewire\Livewire;

test('company manager component loads', function () {
    $component = Livewire::test(CompanyManager::class);
    expect($component)->not->toBeNull();
});

test('can show and hide create form', function () {
    Livewire::test(CompanyManager::class)
        ->assertSet('showForm', false)
        ->call('create')
        ->assertSet('showForm', true)
        ->call('cancel')
        ->assertSet('showForm', false);
});

test('can manage email fields', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->assertCount('emails', 1)
        ->call('addEmailField')
        ->assertCount('emails', 2)
        ->call('removeEmailField', 1)
        ->assertCount('emails', 1);
});

test('loads companies through computed property', function () {
    createCompanyWithLocation([
        'name' => 'Test Company',
        'emails' => new EmailCollection(['test@company.com']),
    ]);

    $component = Livewire::test(CompanyManager::class);
    $companies = $component->instance()->companies;
    expect($companies->total())->toBeGreaterThan(0);
});

test('can populate form for editing', function () {
    $company = createCompanyWithLocation([
        'name' => 'Edit Company',
        'phone' => '+1234567890',
        'emails' => new EmailCollection(['edit@company.com']),
    ]);

    Livewire::test(CompanyManager::class)
        ->call('edit', $company)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $company->id)
        ->assertSet('name', 'Edit Company')
        ->assertSet('phone', '+1234567890')
        ->assertSet('emails.0', 'edit@company.com');
});

test('resets form correctly', function () {
    $component = Livewire::test(CompanyManager::class)
        ->set('name', 'Test Name')
        ->set('phone', '+1234567890')
        ->set('emails.0', 'test@test.com')
        ->call('cancel');

    expect($component->get('name'))->toBe('');
    expect($component->get('phone'))->toBe('');
    expect($component->get('emails'))->toBe(['']);
});

test('validates required fields', function () {
    Livewire::test(CompanyManager::class)
        ->call('create')
        ->set('name', '') // Empty required field
        ->set('emails.0', 'invalid-email') // Invalid email
        ->call('save')
        ->assertHasErrors(['name', 'emails.0']);
});

test('can create company with valid data', function () {
    $initialCount = Company::count();

    Livewire::test(CompanyManager::class)
        ->call('create')
        ->set('name', 'New Company')
        ->set('emails.0', 'new@company.com')
        ->set('location_name', 'Head Office')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save');

    expect(Company::count())->toBe($initialCount + 1);
    expect(Company::latest()->first()->name)->toBe('New Company');
});

test('can delete company', function () {
    $company = createCompanyWithLocation([
        'name' => 'Delete Me',
        'emails' => new EmailCollection(['delete@company.com']),
    ]);

    $initialCount = Company::count();

    Livewire::test(CompanyManager::class)
        ->call('delete', $company);

    expect(Company::count())->toBe($initialCount - 1);
    expect(Company::find($company->id))->toBeNull();
});