<?php

use App\Models\User;
use App\ValueObjects\ContactCollection;
use Livewire\Livewire;

test('user can go to organizations page and update location details including GSTIN', function () {
    // Create a user with a business organization (not personal team)
    $user = User::factory()->withBusinessOrganization()->create([
        'email' => 'business@example.test'
    ]);
    
    $organization = $user->ownedTeams()->first();
    
    // Simulate the exact user workflow: go to organizations page
    $response = $this->actingAs($user)->get('/organizations');
    $response->assertStatus(200);
    $response->assertSee('Organizations');
    
    // User clicks edit on their organization and updates location details
    Livewire::actingAs($user)
        ->test(\App\Livewire\OrganizationManager::class)
        ->call('edit', $organization) // User clicks Edit button
        ->assertSet('showForm', true)
        ->assertSet('editingId', $organization->id)
        // Update organization details
        ->set('name', 'Updated Business Name')
        ->set('phone', '+91-9876543210')
        ->set('emails.0', 'updated@business.test')
        ->set('currency', 'INR')
        ->set('country_code', 'IN')
        // Update location details including GSTIN
        ->set('location_name', 'Updated Head Office')
        ->set('gstin', '29AABCU9603R1ZV') // Valid Indian GSTIN format
        ->set('address_line_1', '123 Updated Business Park')
        ->set('address_line_2', 'Tower B, Floor 5')
        ->set('city', 'Bangalore')
        ->set('state', 'Karnataka')
        ->set('country', 'IN')
        ->set('postal_code', '560001')
        ->call('save') // User clicks Update Organization
        ->assertHasNoErrors()
        ->assertSet('showForm', false);
    
    // Verify all updates were saved correctly
    $organization->refresh();
    
    // Organization fields
    expect($organization->name)->toBe('Updated Business Name');
    expect($organization->phone)->toBe('+91-9876543210');
    expect($organization->emails->getEmails())->toBe(['updated@business.test']);
    
    // Location fields
    $location = $organization->primaryLocation;
    expect($location)->not->toBeNull();
    expect($location->name)->toBe('Updated Head Office');
    expect($location->gstin)->toBe('29AABCU9603R1ZV');
    expect($location->address_line_1)->toBe('123 Updated Business Park');
    expect($location->address_line_2)->toBe('Tower B, Floor 5');
    expect($location->city)->toBe('Bangalore');
    expect($location->state)->toBe('Karnataka');
    expect($location->postal_code)->toBe('560001');
});

test('user can update organization via new Manage Your Business flow with location details', function () {
    // Test the new direct edit route
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'personal@example.test'
    ]);
    
    $organization = $user->currentTeam;
    
    // User goes directly to /organization/edit (Manage Your Business)
    $response = $this->actingAs($user)->get('/organization/edit');
    $response->assertStatus(200);
    $response->assertSee('Manage Your Business');
    
    // Update organization and location details in auto-edit mode
    Livewire::actingAs($user)
        ->test(\App\Livewire\OrganizationManager::class)
        ->set('autoEdit', true) // Simulate auto-edit mode
        ->call('edit', $organization)
        ->set('name', 'My Updated Personal Business')
        ->set('currency', 'INR')
        ->set('country_code', 'IN')
        ->set('location_name', 'Home Office')
        ->set('gstin', '27AABCU9603R1ZX') // Different GSTIN
        ->set('address_line_1', '456 Residential Complex')
        ->set('city', 'Mumbai')
        ->set('state', 'Maharashtra')
        ->set('country', 'IN')
        ->set('postal_code', '400001')
        ->call('save')
        ->assertHasNoErrors();
    
    // Verify updates
    $organization->refresh();
    expect($organization->name)->toBe('My Updated Personal Business');
    
    $location = $organization->primaryLocation;
    expect($location->name)->toBe('Home Office');
    expect($location->gstin)->toBe('27AABCU9603R1ZX');
    expect($location->address_line_1)->toBe('456 Residential Complex');
    expect($location->city)->toBe('Mumbai');
    expect($location->state)->toBe('Maharashtra');
    expect($location->postal_code)->toBe('400001');
});

test('GSTIN updates are reflected immediately in database', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $organization = $user->currentTeam;
    
    // Initial state - no GSTIN
    expect($organization->primaryLocation)->toBeNull();
    
    // Update with GSTIN via Livewire
    Livewire::actingAs($user)
        ->test(\App\Livewire\OrganizationManager::class)
        ->call('edit', $organization)
        ->set('name', 'Test Business')
        ->set('currency', 'INR')
        ->set('country_code', 'IN')
        ->set('location_name', 'Test Office')
        ->set('gstin', '36AABCU9603R1ZU')
        ->set('address_line_1', '789 Test Street')
        ->set('city', 'Hyderabad')
        ->set('state', 'Telangana')
        ->set('country', 'IN')
        ->set('postal_code', '500001')
        ->call('save')
        ->assertHasNoErrors();
    
    // Immediately check database
    $organization->refresh();
    $location = $organization->primaryLocation;
    
    expect($location)->not->toBeNull();
    expect($location->gstin)->toBe('36AABCU9603R1ZU');
    
    // Update GSTIN again
    Livewire::actingAs($user)
        ->test(\App\Livewire\OrganizationManager::class)
        ->call('edit', $organization)
        ->set('gstin', 'UPDATED123456789')
        ->call('save')
        ->assertHasNoErrors();
    
    // Verify immediate update
    $organization->refresh();
    expect($organization->primaryLocation->gstin)->toBe('UPDATED123456789');
});

test('location updates work with all supported country formats', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $organization = $user->currentTeam;
    
    // Test with US format
    Livewire::actingAs($user)
        ->test(\App\Livewire\OrganizationManager::class)
        ->call('edit', $organization)
        ->set('name', 'US Business')
        ->set('currency', 'USD')
        ->set('country_code', 'US')
        ->set('location_name', 'US Office')
        ->set('address_line_1', '123 Main Street')
        ->set('city', 'New York')
        ->set('state', 'New York')
        ->set('country', 'US')
        ->set('postal_code', '10001')
        ->call('save')
        ->assertHasNoErrors();
    
    $organization->refresh();
    $location = $organization->primaryLocation;
    expect($location->country)->toBe('US');
    expect($location->postal_code)->toBe('10001');
    
    // Test with German format
    Livewire::actingAs($user)
        ->test(\App\Livewire\OrganizationManager::class)
        ->call('edit', $organization)
        ->set('name', 'German Business')
        ->set('currency', 'EUR')
        ->set('country_code', 'DE')
        ->set('address_line_1', '123 Main Street') // Keep existing address
        ->set('city', 'Berlin')
        ->set('state', 'Berlin')
        ->set('country', 'DE')
        ->set('postal_code', '10115')
        ->call('save')
        ->assertHasNoErrors();
    
    $organization->refresh();
    $location = $organization->primaryLocation;
    expect($location->country)->toBe('DE');
    expect($location->city)->toBe('Berlin');
    expect($location->postal_code)->toBe('10115');
});