<?php

use App\Models\User;

test('user can go to organizations page and update location details including GSTIN', function () {
    $user = User::factory()->withBusinessOrganization()->create([
        'email' => 'business@example.test',
    ]);

    $organization = $user->ownedTeams()->first();

    // User goes to organizations page
    $response = $this->actingAs($user)->get('/organizations');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Organizations/Index'));

    // Update location details via HTTP
    $response = $this->actingAs($user)->put("/organizations/{$organization->id}/location", [
        'location_name' => 'Updated Head Office',
        'gstin' => '29AABCU9603R1ZV',
        'address_line_1' => '123 Updated Business Park',
        'address_line_2' => 'Tower B, Floor 5',
        'city' => 'Bangalore',
        'state' => 'Karnataka',
        'country' => 'IN',
        'postal_code' => '560001',
    ]);

    $response->assertRedirect();

    $organization->refresh();
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

test('user can update organization via Manage Your Business flow with location details', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'personal@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    // User goes to /organization/edit
    $response = $this->actingAs($user)->get('/organization/edit');
    $response->assertStatus(200);

    // Update organization basics
    $response = $this->actingAs($user)->put("/organizations/{$organization->id}", [
        'name' => 'My Updated Personal Business',
        'emails' => ['personal@example.test'],
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);
    $response->assertRedirect();

    // Update location
    $response = $this->actingAs($user)->put("/organizations/{$organization->id}/location", [
        'location_name' => 'Home Office',
        'gstin' => '27AABCU9603R1ZX',
        'address_line_1' => '456 Residential Complex',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'country' => 'IN',
        'postal_code' => '400001',
    ]);
    $response->assertRedirect();

    $organization->refresh();
    expect($organization->name)->toBe('My Updated Personal Business');

    $location = $organization->primaryLocation;
    expect($location->name)->toBe('Home Office');
    expect($location->gstin)->toBe('27AABCU9603R1ZX');
    expect($location->address_line_1)->toBe('456 Residential Complex');
    expect($location->city)->toBe('Mumbai');
});

test('GSTIN updates are reflected immediately in database', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $organization = createOrganizationWithLocation([], [], $user);

    // Update with GSTIN
    $response = $this->actingAs($user)->put("/organizations/{$organization->id}/location", [
        'location_name' => 'Test Office',
        'gstin' => '36AABCU9603R1ZU',
        'address_line_1' => '789 Test Street',
        'city' => 'Hyderabad',
        'state' => 'Telangana',
        'country' => 'IN',
        'postal_code' => '500001',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    $location = $organization->primaryLocation;
    expect($location->gstin)->toBe('36AABCU9603R1ZU');

    // Update GSTIN again
    $response = $this->actingAs($user)->put("/organizations/{$organization->id}/location", [
        'location_name' => 'Test Office',
        'gstin' => 'UPDATED123456789',
        'address_line_1' => '789 Test Street',
        'city' => 'Hyderabad',
        'state' => 'Telangana',
        'country' => 'IN',
        'postal_code' => '500001',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->primaryLocation->gstin)->toBe('UPDATED123456789');
});

test('location updates work with all supported country formats', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $organization = createOrganizationWithLocation([], [], $user);

    // Test with US format
    $response = $this->actingAs($user)->put("/organizations/{$organization->id}", [
        'name' => 'US Business',
        'emails' => ['us@example.test'],
        'currency' => 'USD',
        'country_code' => 'US',
    ]);
    $response->assertRedirect();

    $response = $this->actingAs($user)->put("/organizations/{$organization->id}/location", [
        'location_name' => 'US Office',
        'address_line_1' => '123 Main Street',
        'city' => 'New York',
        'state' => 'New York',
        'country' => 'US',
        'postal_code' => '10001',
    ]);
    $response->assertRedirect();

    $organization->refresh();
    $location = $organization->primaryLocation;
    expect($location->country)->toBe('US');
    expect($location->postal_code)->toBe('10001');
});
