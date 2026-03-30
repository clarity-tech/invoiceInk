<?php

use App\Models\User;

test('user can go to organization show page and then edit location', function () {
    $user = User::factory()->withBusinessOrganization()->create([
        'email' => 'business@example.test',
    ]);

    $organization = $user->ownedTeams()->first();

    // User goes to organization show page
    $response = $this->actingAs($user)->get("/organizations/{$organization->id}");
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Organizations/Show'));

    // User goes to edit page
    $response = $this->actingAs($user)->get("/organizations/{$organization->id}/edit?tab=location");
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Organizations/Edit'));

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

test('user can update organization via edit page with location details', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'personal@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    // User goes to edit page
    $response = $this->actingAs($user)->get("/organizations/{$organization->id}/edit");
    $response->assertStatus(200);

    // Update organization basics
    $response = $this->actingAs($user)->put("/organizations/{$organization->id}", [
        'name' => 'My Updated Personal Business',
        'emails' => ['personal@example.test'],
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->name)->toBe('My Updated Personal Business');
});

test('user can update location from edit page location tab', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'loctest@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    $response = $this->actingAs($user)->put("/organizations/{$organization->id}/location", [
        'location_name' => 'Branch Office',
        'address_line_1' => '456 Branch St',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'country' => 'IN',
        'postal_code' => '400001',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->primaryLocation->city)->toBe('Mumbai');
});

test('user can update bank details from edit page bank tab', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'banktest@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    $response = $this->actingAs($user)->put("/organizations/{$organization->id}/bank-details", [
        'bank_name' => 'HDFC Bank',
        'bank_account_name' => 'Test Account',
        'bank_account_number' => '1234567890',
        'bank_ifsc' => 'HDFC0001234',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->bank_details->bankName)->toBe('HDFC Bank');
});
