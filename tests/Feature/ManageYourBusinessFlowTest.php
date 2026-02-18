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

test('organization edit route renders organizations page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $response = $this->actingAs($user)->get('/organization/edit');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Organizations/Index'));
});

test('organization edit route shows organizations with data', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $response = $this->actingAs($user)->get('/organization/edit');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Organizations/Index')
        ->has('organizations')
        ->has('countries')
        ->has('currencies')
    );
});

test('user can navigate to organizations page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $response = $this->actingAs($user)->get('/organizations');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Organizations/Index'));
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

test('organizations index page shows organizations list', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.test',
    ]);

    $response = $this->actingAs($user)->get('/organization/edit');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Organizations/Index')
        ->has('organizations.data')
    );
});
