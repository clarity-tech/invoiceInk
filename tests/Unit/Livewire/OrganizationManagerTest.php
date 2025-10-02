<?php

use App\Currency;
use App\Livewire\OrganizationManager;
use App\Models\Organization;
use App\ValueObjects\EmailCollection;
use Livewire\Livewire;

test('can render organization manager component', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->assertStatus(200)
        ->assertSee('Organizations');
});

test('can load organizations with pagination', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    // Create exactly 11 test organizations to trigger pagination (page size is 10)
    for ($i = 1; $i <= 11; $i++) {
        Organization::factory()->withLocation([
            'name' => "Test Organization {$i}",
            'company_name' => "Test Company {$i}",
        ])->create(['user_id' => $user->id]);
    }

    // Verify organizations were created
    expect(Organization::count())->toBeGreaterThanOrEqual(11);

    $component = Livewire::test(OrganizationManager::class);

    // Test that pagination is working - should have "Next" button when > 10 items
    $component->assertSee('Next');

    // Test basic functionality - should see at least one organization name
    $component->assertSeeHtml('Test Organization');
});

test('can show create form', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->assertSet('showForm', false)
        ->call('create')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSet('currency', Currency::default()->value);
});

test('can add and remove email fields', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
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
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->assertCount('emails', 1)
        ->call('removeEmailField', 0)
        ->assertCount('emails', 1); // Should still have 1
});

test('can create new organization with location', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $initialCount = Organization::count();

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'New Test Organization')
        ->set('phone', '+1-555-0199')
        ->set('emails.0', 'contact@newtest.com')
        ->set('currency', 'USD')
        ->set('location_name', 'New HQ')
        ->set('gstin', 'GSTIN123456789')
        ->set('address_line_1', '123 New Street')
        ->set('address_line_2', 'Suite 100')
        ->set('city', 'New City')
        ->set('state', 'New State')
        ->set('country', 'New Country')
        ->set('postal_code', '99999')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertSee('Organization created successfully!');

    expect(Organization::count())->toBe($initialCount + 1);

    $organization = Organization::where('name', 'New Test Organization')->first();
    expect($organization->name)->toBe('New Test Organization');
    expect($organization->phone)->toBe('+1-555-0199');
    expect($organization->emails->first())->toBe('contact@newtest.com');
    expect($organization->currency->value)->toBe('USD');
    expect($organization->primaryLocation->name)->toBe('New HQ');
    expect($organization->primaryLocation->gstin)->toBe('GSTIN123456789');
});

test('can create organization with multiple emails', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'Multi Email Org')
        ->set('emails.0', 'primary@test.com')
        ->call('addEmailField')
        ->set('emails.1', 'secondary@test.com')
        ->call('addEmailField')
        ->set('emails.2', 'billing@test.com')
        ->set('currency', 'INR')
        ->set('location_name', 'Test Location')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save');

    $organization = Organization::where('name', 'Multi Email Org')->first();
    expect($organization->emails->count())->toBe(3);
    expect($organization->emails->toArray())->toContain('primary@test.com');
    expect($organization->emails->toArray())->toContain('secondary@test.com');
    expect($organization->emails->toArray())->toContain('billing@test.com');
});

test('validates required fields when creating organization', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('currency', '') // Explicitly set to empty to test validation
        ->call('save')
        ->assertHasErrors([
            'name',
            'currency',
            'location_name',
            'address_line_1',
            'city',
            'state',
            'country',
            'postal_code',
        ]);
});

test('validates email format', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'Test Org')
        ->set('emails.0', 'invalid-email')
        ->set('currency', 'INR')
        ->set('location_name', 'Test Location')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save')
        ->assertHasErrors(['emails.0' => 'email']);
});

test('requires at least one non-empty email', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'Test Org')
        ->set('emails.0', '')
        ->set('currency', 'INR')
        ->set('location_name', 'Test Location')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save')
        ->assertHasErrors(['emails.0' => 'At least one email is required.']);
});

test('validates currency code', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'Test Org')
        ->set('emails.0', 'test@example.com')
        ->set('currency', 'INVALID')
        ->set('location_name', 'Test Location')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save')
        ->assertHasErrors(['currency']);
});

test('can edit existing organization', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $organization = createOrganizationWithLocation([
        'name' => 'Original Name',
        'phone' => '+1-555-0100',
        'emails' => new EmailCollection(['original@test.com']),
        'currency' => 'USD',
    ], [
        'name' => 'Original Location',
        'gstin' => 'ORIGINAL123',
        'address_line_1' => '100 Original St',
        'city' => 'Original City',
        'state' => 'Original State',
        'country' => 'Original Country',
        'postal_code' => '10000',
    ], $user);

    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $organization->id)
        ->assertSet('name', 'Original Name')
        ->assertSet('phone', '+1-555-0100')
        ->assertSet('emails.0', 'original@test.com')
        ->assertSet('currency', 'USD')
        ->assertSet('location_name', 'Original Location')
        ->assertSet('gstin', 'ORIGINAL123')
        ->assertSet('address_line_1', '100 Original St')
        ->assertSet('city', 'Original City')
        ->assertSet('state', 'Original State')
        ->assertSet('country', 'Original Country')
        ->assertSet('postal_code', '10000');
});

test('can update existing organization', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $organization = createOrganizationWithLocation([
        'name' => 'Original Name',
        'phone' => '+1-555-0100',
        'emails' => new EmailCollection(['original@test.com']),
        'currency' => 'USD',
    ], [], $user);

    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->set('name', 'Updated Name')
        ->set('phone', '+1-555-0200')
        ->set('emails.0', 'updated@test.com')
        ->set('currency', 'EUR')
        ->call('save')
        ->assertSet('showForm', false);

    $organization->refresh();
    expect($organization->name)->toBe('Updated Name');
    expect($organization->phone)->toBe('+1-555-0200');
    expect($organization->emails->first())->toBe('updated@test.com');
    expect($organization->currency->value)->toBe('EUR');
});

test('can delete organization', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $organization = createOrganizationWithLocation([], [], $user);
    $organizationId = $organization->id;

    Livewire::test(OrganizationManager::class)
        ->call('delete', $organization)
        ->assertSee('Organization deleted successfully!');

    expect(Organization::find($organizationId))->toBeNull();
});

test('can cancel form', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'Test Name')
        ->assertSet('showForm', true)
        ->call('cancel')
        ->assertSet('showForm', false)
        ->assertSet('name', '')
        ->assertSet('editingId', null);
});

test('resets form after successful save', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'Test Organization')
        ->set('phone', '+1-555-0123')
        ->set('emails.0', 'test@example.com')
        ->set('currency', 'USD')
        ->set('location_name', 'Test Location')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('phone', '')
        ->assertSet('emails', [''])
        ->assertSet('currency', Currency::default()->value)
        ->assertSet('editingId', null)
        ->assertSet('showForm', false);
});

test('handles organization without primary location when editing', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    // Create organization without location by updating existing one
    $organization = createOrganizationWithLocation([], [], $user);
    $organization->update(['primary_location_id' => null]);
    $organization->primaryLocation()->delete();

    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $organization->id)
        ->assertSet('name', $organization->name)
        ->assertSet('location_name', '')
        ->assertSet('address_line_1', '')
        ->assertSet('city', '')
        ->assertSet('state', '')
        ->assertSet('country', '')
        ->assertSet('postal_code', '');
});

test('filters out empty emails when saving', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'Email Filter Test Org')
        ->set('emails.0', 'valid@test.com')
        ->call('addEmailField')
        ->set('emails.1', '')
        ->call('addEmailField')
        ->set('emails.2', 'another@test.com')
        ->call('addEmailField')
        ->set('emails.3', '   ')
        ->set('currency', 'INR')
        ->set('location_name', 'Test Location')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save');

    $organization = Organization::where('name', 'Email Filter Test Org')->first();
    expect($organization->emails->count())->toBe(2);
    expect($organization->emails->toArray())->toContain('valid@test.com');
    expect($organization->emails->toArray())->toContain('another@test.com');
    expect($organization->emails->toArray())->not->toContain('');
});

test('handles phone number as nullable field', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'Test Organization')
        ->set('phone', '')
        ->set('emails.0', 'test@example.com')
        ->set('currency', 'INR')
        ->set('location_name', 'Test Location')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save');

    $organization = Organization::latest()->first();
    expect($organization->phone)->toBeNull();
});

test('handles gstin as nullable field', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'GSTIN Test Organization')
        ->set('emails.0', 'test@example.com')
        ->set('currency', 'INR')
        ->set('location_name', 'Test Location')
        ->set('gstin', '')
        ->set('address_line_1', '123 Test St')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save');

    $organization = Organization::where('name', 'GSTIN Test Organization')->first();
    expect($organization->primaryLocation->gstin)->toBeNull();
});

test('validates field lengths', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    $longString = str_repeat('a', 256);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', $longString)
        ->set('phone', str_repeat('1', 21))
        ->set('location_name', $longString)
        ->set('gstin', str_repeat('G', 51))
        ->set('address_line_1', str_repeat('a', 501))
        ->set('city', str_repeat('c', 101))
        ->set('state', str_repeat('s', 101))
        ->set('country', str_repeat('x', 101))
        ->set('postal_code', str_repeat('1', 21))
        ->call('save')
        ->assertHasErrors([
            'name' => 'max:255',
            'phone' => 'max:20',
            'location_name' => 'max:255',
            'gstin' => 'max:50',
            'address_line_1' => 'max:500',
            'city' => 'max:100',
            'state' => 'max:100',
            'country' => 'max:100',
            'postal_code' => 'max:20',
        ]);
});

test('loads organizations through computed property', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Organization::factory()->withLocation(['name' => 'Test Organization 1'])->create(['user_id' => $user->id]);
    Organization::factory()->withLocation(['name' => 'Test Organization 2'])->create(['user_id' => $user->id]);

    $component = Livewire::test(OrganizationManager::class);
    $organizations = $component->instance()->organizations;

    expect($organizations->total())->toBeGreaterThanOrEqual(2);
});

test('correctly handles address line 2 as optional', function () {
    $user = createUserWithTeam();
    $this->actingAs($user);

    Livewire::test(OrganizationManager::class)
        ->call('create')
        ->set('name', 'Address Line 2 Test Org')
        ->set('emails.0', 'test@example.com')
        ->set('currency', 'INR')
        ->set('location_name', 'Test Location')
        ->set('address_line_1', '123 Test St')
        ->set('address_line_2', '')
        ->set('city', 'Test City')
        ->set('state', 'Test State')
        ->set('country', 'Test Country')
        ->set('postal_code', '12345')
        ->call('save');

    $organization = Organization::where('name', 'Address Line 2 Test Org')->first();
    expect($organization->primaryLocation->address_line_2)->toBeNull();
});
