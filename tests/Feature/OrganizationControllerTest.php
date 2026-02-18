<?php

use App\Models\Organization;

beforeEach(function () {
    $this->user = createUserWithTeam();
    $this->actingAs($this->user);
});

test('can render organizations index page', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->get('/organizations');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Organizations/Index')
        ->has('organizations')
        ->has('countries')
        ->has('currencies')
    );
});

test('can update organization basics', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => 'Updated Org Name',
        'phone' => '+1-555-9999',
        'emails' => ['updated@test.com'],
        'currency' => 'USD',
        'country_code' => 'US',
        'financial_year_type' => 'january_december',
        'financial_year_start_month' => 1,
        'financial_year_start_day' => 1,
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->name)->toBe('Updated Org Name');
    expect($organization->phone)->toBe('+1-555-9999');
    expect($organization->emails->getFirstEmail())->toBe('updated@test.com');
    expect($organization->currency->value)->toBe('USD');
});

test('validates required fields when updating organization', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => '',
        'emails' => [],
        'currency' => '',
        'country_code' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'currency', 'country_code', 'emails']);
});

test('validates email format when updating', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => 'Test Org',
        'emails' => ['invalid-email'],
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);

    $response->assertSessionHasErrors(['emails.0']);
});

test('requires at least one non-empty email', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => 'Test Org',
        'emails' => [''],
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);

    $response->assertSessionHasErrors(['emails.0']);
});

test('can update organization location', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}/location", [
        'location_name' => 'New Office',
        'gstin' => 'NEWGSTIN123',
        'address_line_1' => '456 New St',
        'address_line_2' => 'Suite 200',
        'city' => 'New City',
        'state' => 'New State',
        'country' => 'IN',
        'postal_code' => '99999',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    $location = $organization->primaryLocation;
    expect($location->name)->toBe('New Office');
    expect($location->gstin)->toBe('NEWGSTIN123');
    expect($location->address_line_1)->toBe('456 New St');
    expect($location->city)->toBe('New City');
});

test('validates location required fields', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}/location", [
        'address_line_1' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'postal_code' => '',
    ]);

    $response->assertSessionHasErrors(['address_line_1', 'city', 'state', 'country', 'postal_code']);
});

test('can update bank details', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}/bank-details", [
        'bank_account_name' => 'Test Account',
        'bank_account_number' => '1234567890',
        'bank_name' => 'Test Bank',
        'bank_ifsc' => 'TEST0001234',
        'bank_branch' => 'Main Branch',
        'bank_swift' => 'TESTSWFT',
        'bank_pan' => 'ABCDE1234F',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->bank_details->accountName)->toBe('Test Account');
    expect($organization->bank_details->bankName)->toBe('Test Bank');
    expect($organization->bank_details->ifsc)->toBe('TEST0001234');
});

test('can delete organization', function () {
    $organization = createOrganizationWithLocation([
        'personal_team' => false,
    ], [], $this->user);
    $orgId = $organization->id;

    $response = $this->delete("/organizations/{$orgId}");

    $response->assertRedirect('/organizations');
    expect(Organization::find($orgId))->toBeNull();
});

test('cannot delete personal organization', function () {
    $personalTeam = $this->user->currentTeam;

    $response = $this->delete("/organizations/{$personalTeam->id}");

    $response->assertRedirect();
    expect(Organization::find($personalTeam->id))->not->toBeNull();
});

test('cannot update organization user does not own', function () {
    $otherUser = createUserWithTeam();
    $otherOrg = createOrganizationWithLocation([], [], $otherUser);

    $response = $this->put("/organizations/{$otherOrg->id}", [
        'name' => 'Hacked Name',
        'emails' => ['hack@test.com'],
        'currency' => 'USD',
        'country_code' => 'US',
    ]);

    $response->assertStatus(403);
});

test('location country defaults from organization when empty', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}/location", [
        'location_name' => '',
        'address_line_1' => '123 Test St',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'IN',
        'postal_code' => '12345',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->primaryLocation->name)->toBe($organization->name);
});

test('filters empty emails when updating', function () {
    $organization = createOrganizationWithLocation([], [], $this->user);

    $response = $this->put("/organizations/{$organization->id}", [
        'name' => 'Filter Test Org',
        'emails' => ['valid@test.com', '', 'another@test.com', '   '],
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->emails->count())->toBe(2);
    expect($organization->emails->getEmails())->toContain('valid@test.com');
    expect($organization->emails->getEmails())->toContain('another@test.com');
});
