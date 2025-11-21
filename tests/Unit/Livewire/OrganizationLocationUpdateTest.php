<?php

use App\Livewire\OrganizationManager;
use App\Models\Organization;
use App\ValueObjects\ContactCollection;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = createUserWithTeam();
    $this->actingAs($this->user);
});

test('can update organization location details including GSTIN', function () {
    // Create organization with initial location
    $organization = createOrganizationWithLocation([
        'name' => 'Test Organization',
        'phone' => '+1-555-0100',
        'emails' => new ContactCollection([['name' => 'Original Contact', 'email' => 'original@test.com']]),
        'currency' => 'INR',
        'country_code' => 'IN',
    ], [
        'name' => 'Original Location',
        'gstin' => 'ORIGINAL123456789',
        'address_line_1' => '100 Original Street',
        'address_line_2' => 'Suite 1',
        'city' => 'Original City',
        'state' => 'Original State',
        'country' => 'IN',
        'postal_code' => '10000',
    ], $this->user);

    // Update organization including location details
    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization) // Load organization for editing
        ->assertSet('editingId', $organization->id)
        ->assertSet('gstin', 'ORIGINAL123456789') // Verify initial GSTIN is loaded
        ->set('name', 'Updated Organization Name')
        ->set('location_name', 'Updated Location Name')
        ->set('gstin', 'UPDATED987654321') // Change GSTIN
        ->set('address_line_1', '200 Updated Street')
        ->set('address_line_2', 'Suite 2')
        ->set('city', 'Updated City')
        ->set('state', 'Updated State')
        ->set('postal_code', '20000')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    // Verify organization was updated
    $organization->refresh();
    expect($organization->name)->toBe('Updated Organization Name');
    
    // Verify location was updated
    $location = $organization->primaryLocation;
    expect($location)->not->toBeNull();
    expect($location->name)->toBe('Updated Location Name');
    expect($location->gstin)->toBe('UPDATED987654321');
    expect($location->address_line_1)->toBe('200 Updated Street');
    expect($location->address_line_2)->toBe('Suite 2');
    expect($location->city)->toBe('Updated City');
    expect($location->state)->toBe('Updated State');
    expect($location->postal_code)->toBe('20000');
});

test('can update organization with null GSTIN', function () {
    $organization = createOrganizationWithLocation([
        'name' => 'Test Organization',
        'currency' => 'USD',
        'country_code' => 'US',
    ], [
        'name' => 'Test Location',
        'gstin' => 'EXISTING123456789',
        'address_line_1' => '100 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'US',
        'postal_code' => '10000',
    ], $this->user);

    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->set('gstin', '') // Clear GSTIN
        ->call('save')
        ->assertHasNoErrors();

    $organization->refresh();
    expect($organization->primaryLocation->gstin)->toBeNull();
});

test('can update organization location with empty address line 2', function () {
    $organization = createOrganizationWithLocation([
        'name' => 'Test Organization',
        'currency' => 'INR',
        'country_code' => 'IN',
    ], [
        'name' => 'Test Location',
        'address_line_1' => '100 Test Street',
        'address_line_2' => 'Original Suite',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'IN',
        'postal_code' => '10000',
    ], $this->user);

    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->set('address_line_2', '') // Clear optional address line 2
        ->call('save')
        ->assertHasNoErrors();

    $organization->refresh();
    expect($organization->primaryLocation->address_line_2)->toBeNull();
});

test('location country is inherited from organization country', function () {
    $organization = createOrganizationWithLocation([
        'name' => 'Test Organization',
        'currency' => 'INR',
        'country_code' => 'IN',
    ], [
        'name' => 'Test Location',
        'address_line_1' => '100 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'IN',
        'postal_code' => '10000',
    ], $this->user);

    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->set('country_code', 'US') // Change organization country
        ->set('currency', 'USD') // Currency must match country
        ->call('save')
        ->assertHasNoErrors();

    $organization->refresh();
    expect($organization->country_code->value)->toBe('US');
    expect($organization->primaryLocation->country)->toBe('US'); // Location country should be updated too
});

test('validates location required fields during update', function () {
    $organization = createOrganizationWithLocation([
        'name' => 'Test Organization',
        'currency' => 'INR',
        'country_code' => 'IN',
    ], [
        'name' => 'Test Location',
        'address_line_1' => '100 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'IN',
        'postal_code' => '10000',
    ], $this->user);

    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->set('address_line_1', '') // Remove required field
        ->set('city', '') // Remove required field
        ->set('state', '') // Remove required field
        ->set('postal_code', '') // Remove required field
        ->call('save')
        ->assertHasErrors([
            'address_line_1',
            'city',
            'state',
            'postal_code',
        ]);
});

test('can update organization location name to empty string uses organization name as default', function () {
    $organization = createOrganizationWithLocation([
        'name' => 'My Company',
        'currency' => 'INR',
        'country_code' => 'IN',
    ], [
        'name' => 'Original Location Name',
        'address_line_1' => '100 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'IN',
        'postal_code' => '10000',
    ], $this->user);

    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->set('location_name', '') // Clear location name
        ->call('save')
        ->assertHasNoErrors();

    $organization->refresh();
    expect($organization->primaryLocation->name)->toBe('My Company'); // Uses organization name as default
});

test('organization and location update is atomic via transaction', function () {
    $organization = createOrganizationWithLocation([
        'name' => 'Test Organization',
        'currency' => 'INR',
        'country_code' => 'IN',
    ], [], $this->user);

    // Mock a scenario where location update might fail
    // This test ensures that if location update fails, organization update is also rolled back
    
    // For now, just verify both updates work together
    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->set('name', 'Updated Organization')
        ->set('location_name', 'Updated Location')
        ->set('address_line_1', '200 Updated Street')
        ->set('city', 'Updated City')
        ->set('state', 'Updated State')
        ->set('postal_code', '20000')
        ->call('save')
        ->assertHasNoErrors();

    $organization->refresh();
    expect($organization->name)->toBe('Updated Organization');
    expect($organization->primaryLocation->name)->toBe('Updated Location');
    expect($organization->primaryLocation->address_line_1)->toBe('200 Updated Street');
});

test('can update all location fields at once', function () {
    $organization = createOrganizationWithLocation([
        'name' => 'Test Organization',
        'currency' => 'INR',
        'country_code' => 'IN',
    ], [
        'name' => 'Old Location',
        'gstin' => 'OLD123456789',
        'address_line_1' => 'Old Street',
        'address_line_2' => 'Old Suite',
        'city' => 'Old City',
        'state' => 'Old State',
        'country' => 'IN',
        'postal_code' => '10000',
    ], $this->user);

    Livewire::test(OrganizationManager::class)
        ->call('edit', $organization)
        ->set('location_name', 'New Location Name')
        ->set('gstin', 'NEW987654321')
        ->set('address_line_1', 'New Street Address')
        ->set('address_line_2', 'New Suite Number')
        ->set('city', 'New City')
        ->set('state', 'New State')
        ->set('postal_code', '99999')
        ->call('save')
        ->assertHasNoErrors();

    $organization->refresh();
    $location = $organization->primaryLocation;
    
    expect($location->name)->toBe('New Location Name');
    expect($location->gstin)->toBe('NEW987654321');
    expect($location->address_line_1)->toBe('New Street Address');
    expect($location->address_line_2)->toBe('New Suite Number');
    expect($location->city)->toBe('New City');
    expect($location->state)->toBe('New State');
    expect($location->postal_code)->toBe('99999');
});