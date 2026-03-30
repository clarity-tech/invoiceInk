<?php

use App\Models\User;

test('dashboard shows business overview', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('organizations index redirects single-org user to show page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $org = $user->currentTeam;

    $response = $this->actingAs($user)->get('/organizations');
    $response->assertRedirect("/organizations/{$org->id}");
});

test('organization show page renders for owner', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    $response = $this->actingAs($user)->get("/organizations/{$organization->id}");
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Organizations/Show')
        ->has('organization')
    );
});

test('organization edit page renders with tabs', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    $response = $this->actingAs($user)->get("/organizations/{$organization->id}/edit");
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Organizations/Edit')
        ->has('organization')
        ->has('countries')
        ->has('currencies')
        ->where('tab', 'basics')
    );
});

test('user can update organization via PUT request', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    $response = $this->actingAs($user)->put("/organizations/{$organization->id}", [
        'name' => 'Updated Business Name',
        'phone' => '+91-9876543210',
        'emails' => ['updated@business.test'],
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);

    $response->assertRedirect();

    $organization->refresh();
    expect($organization->name)->toBe('Updated Business Name');
    expect($organization->phone)->toBe('+91-9876543210');
});

test('dashboard renders with Inertia', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
});
