<?php

use App\Models\Location;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);

    // The setup wizard only operates on an in-progress org; the withPersonalTeam
    // factory marks setup complete, so reset it to the realistic incomplete state.
    // (Tests that assert the "already complete" behaviour set it back themselves.)
    $this->user->currentTeam->update(['setup_completed_at' => null]);
});

test('can render organization setup page', function () {
    // withPersonalTeam alias now marks setup complete; reset it for this test.
    $this->user->currentTeam->update(['setup_completed_at' => null]);

    $response = $this->get('/organization/setup');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Organizations/Setup')
        ->has('organization')
        ->has('countries')
        ->has('currencies')
    );
});

test('redirects to dashboard when setup is already complete', function () {
    $organization = $this->user->currentTeam;
    $organization->update(['setup_completed_at' => now()]);

    $response = $this->get('/organization/setup');

    $response->assertRedirect(route('dashboard'));
});

test('can save step 1 company information', function () {
    $organization = $this->user->currentTeam;

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 1,
        'company_name' => 'New Test Company',
        'tax_number' => 'TAX123',
        'registration_number' => 'REG456',
        'website' => 'https://example.com',
        'notes' => 'Test notes',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('teams', [
        'id' => $organization->id,
        'company_name' => 'New Test Company',
        'tax_number' => 'TAX123',
    ]);
});

test('step 1 sets company name without overwriting the organization name', function () {
    $organization = $this->user->currentTeam;
    $organization->update(['name' => 'Acme Workspace']);

    $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 1,
        'company_name' => 'Acme Manufacturing Pvt Ltd',
    ])->assertRedirect();

    // The switcher label (name) is preserved; only the legal company_name changes.
    $this->assertDatabaseHas('teams', [
        'id' => $organization->id,
        'name' => 'Acme Workspace',
        'company_name' => 'Acme Manufacturing Pvt Ltd',
    ]);
});

test('validates step 1 requires company name', function () {
    $organization = $this->user->currentTeam;

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 1,
        'company_name' => '',
    ]);

    $response->assertSessionHasErrors(['company_name']);
});

test('validates step 1 website must be valid url', function () {
    $organization = $this->user->currentTeam;

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 1,
        'company_name' => 'Test Company',
        'website' => 'invalid-url',
    ]);

    $response->assertSessionHasErrors(['website']);
});

test('can save step 2 location data', function () {
    $organization = $this->user->currentTeam;

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 2,
        'location_name' => 'Main Office',
        'gstin' => '27AABCG9603R1ZV',
        'country_code' => 'IN',
        'address_line_1' => '123 Test Street',
        'address_line_2' => 'Suite 100',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'postal_code' => '400001',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('locations', [
        'name' => 'Main Office',
        'gstin' => '27AABCG9603R1ZV',
        'address_line_1' => '123 Test Street',
        'city' => 'Mumbai',
        'country' => 'IN',
    ]);

    $this->assertDatabaseHas('teams', [
        'id' => $organization->id,
        'country_code' => 'IN',
    ]);
});

test('validates step 2 requires address fields and country', function () {
    $organization = $this->user->currentTeam;

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 2,
        'country_code' => '',
        'address_line_1' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
    ]);

    $response->assertSessionHasErrors([
        'country_code',
        'address_line_1',
        'city',
        'state',
        'postal_code',
    ]);
});

test('can save step 3 configuration when country was set in step 2', function () {
    $organization = $this->user->currentTeam;
    $organization->update(['country_code' => 'IN']);

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 3,
        'currency' => 'INR',
        'financial_year_type' => 'april_march',
        'financial_year_start_month' => 4,
        'financial_year_start_day' => 1,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('teams', [
        'id' => $organization->id,
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);
});

test('validates step 3 requires currency', function () {
    $organization = $this->user->currentTeam;
    $organization->update(['country_code' => 'IN']);

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 3,
        'currency' => '',
    ]);

    $response->assertSessionHasErrors(['currency']);
});

test('step 3 errors out gracefully when country was not set in step 2', function () {
    $organization = $this->user->currentTeam;
    $organization->update(['country_code' => null]);

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 3,
        'currency' => 'INR',
    ]);

    $response->assertSessionHasErrors(['currency']);
});

test('can save step 4 and complete setup', function () {
    $organization = $this->user->currentTeam;

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 4,
        'emails' => ['test@example.test'],
        'phone' => '+91-9876543210',
    ]);

    $response->assertRedirect(route('dashboard'));

    $organization->refresh();
    expect($organization->setup_completed_at)->not->toBeNull();
    expect($organization->phone)->toBe('+91-9876543210');
});

test('validates step 4 requires valid emails', function () {
    $organization = $this->user->currentTeam;

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 4,
        'emails' => ['invalid-email'],
    ]);

    $response->assertSessionHasErrors(['emails.0']);
});

test('validates step 4 requires at least one email', function () {
    $organization = $this->user->currentTeam;

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 4,
        'emails' => [],
    ]);

    $response->assertSessionHasErrors(['emails']);
});

test('step 2 updates existing location', function () {
    $organization = $this->user->currentTeam;
    $location = Location::factory()->create([
        'locatable_type' => Organization::class,
        'locatable_id' => $organization->id,
    ]);
    $organization->update(['primary_location_id' => $location->id]);

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 2,
        'location_name' => 'Updated Location',
        'country_code' => 'IN',
        'address_line_1' => '456 Updated St',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'postal_code' => '110001',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('locations', [
        'id' => $location->id,
        'name' => 'Updated Location',
        'address_line_1' => '456 Updated St',
    ]);
});

test('location name defaults to a generic label when empty', function () {
    $organization = $this->user->currentTeam;
    $organization->update(['company_name' => 'Test Company Ltd']);

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 2,
        'location_name' => '',
        'country_code' => 'IN',
        'address_line_1' => '123 Test St',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'postal_code' => '400001',
    ]);

    $response->assertRedirect();

    // The location is named generically, not after the company.
    $this->assertDatabaseHas('locations', [
        'name' => 'Head Office',
    ]);
    $this->assertDatabaseMissing('locations', [
        'name' => 'Test Company Ltd',
    ]);
});

test('cannot save step for organization user does not own', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherOrg = $otherUser->currentTeam;

    $response = $this->post("/organization/setup/{$otherOrg->id}/step", [
        'step' => 1,
        'company_name' => 'Hacked',
    ]);

    $response->assertStatus(403);
});

test('validates currency country match in step 3', function () {
    $organization = $this->user->currentTeam;
    $organization->update(['country_code' => 'IN']);

    $response = $this->post("/organization/setup/{$organization->id}/step", [
        'step' => 3,
        'currency' => 'USD',
    ]);

    $response->assertSessionHasErrors(['currency']);
});
