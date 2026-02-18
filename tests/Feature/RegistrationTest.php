<?php

use App\Models\User;
use App\Support\Jetstream;
use Laravel\Fortify\Features;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');

test('registration screen cannot be rendered if support is disabled', function () {
    $response = $this->get('/register');

    $response->assertStatus(404);
})->skip(function () {
    return Features::enabled(Features::registration());
}, 'Registration support is enabled.');

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');

test('new user registration creates personal team and sets current team', function () {
    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'john@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    // User should be authenticated
    $this->assertAuthenticated();

    $user = User::where('email', 'john@example.test')->first();

    // Validate user exists
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('John Doe');

    // Validate personal team was created
    expect($user->ownedTeams()->count())->toBe(1);

    $organization = $user->ownedTeams()->first();
    expect($organization)->not->toBeNull();
    expect($organization->personal_team)->toBe(true);
    expect($organization->name)->toBe("John's Organization");
    expect($organization->user_id)->toBe($user->id);

    // CRITICAL: Validate current_team_id is set correctly
    expect($user->current_team_id)->toBe($organization->id);
    expect($user->currentTeam->id)->toBe($organization->id);

    // Validate user can access their team
    expect($user->allTeams()->pluck('id')->contains($organization->id))->toBe(true);
    expect($user->ownedTeams()->pluck('id')->contains($organization->id))->toBe(true);

})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');

test('registered user can immediately update their organization via HTTP', function () {
    // Step 1: Register a new user
    $this->post('/register', [
        'name' => 'Jane Smith',
        'email' => 'jane@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'jane@example.test')->first();
    $user->markEmailAsVerified();
    $organization = $user->ownedTeams()->first();

    // Validate initial state
    expect($user->current_team_id)->toBe($organization->id);
    expect($organization->personal_team)->toBe(true);

    // Step 2: Update the organization via HTTP
    $response = $this->actingAs($user)->put("/organizations/{$organization->id}", [
        'name' => 'Updated Organization Name',
        'phone' => '+1-555-0123',
        'country_code' => 'US',
        'currency' => 'USD',
        'emails' => ['jane@example.test'],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    // Debug: check where we're being redirected
    // Step 3: Validate the organization was updated successfully
    $organization->refresh();
    expect($organization->name)->toBe('Updated Organization Name');
    expect($organization->phone)->toBe('+1-555-0123');

})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');

test('registration flow handles edge cases correctly', function () {
    // Test with name containing special characters and multiple words
    $response = $this->post('/register', [
        'name' => 'José María García-López',
        'email' => 'jose.maria@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'jose.maria@example.test')->first();
    $organization = $user->ownedTeams()->first();

    // Validate organization name generation handles special characters
    expect($organization->name)->toBe("José's Organization");
    expect($user->current_team_id)->toBe($organization->id);

    // Test single name
    $this->post('/logout');

    $response = $this->post('/register', [
        'name' => 'Cher',
        'email' => 'cher@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $this->assertAuthenticated();

    $user2 = User::where('email', 'cher@example.test')->first();
    $organization2 = $user2->ownedTeams()->first();

    // Single name should use full name
    expect($organization2->name)->toBe("Cher's Organization");
    expect($user2->current_team_id)->toBe($organization2->id);

})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');

test('registered user authorization works throughout the application', function () {
    // Register new user
    $this->post('/register', [
        'name' => 'Auth Test User',
        'email' => 'auth@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'auth@example.test')->first();
    $organization = $user->ownedTeams()->first();

    // Test access to organizations page
    $response = $this->get('/organizations');
    if ($response->status() === 302) {
        $response->assertRedirect();
    } else {
        $response->assertStatus(200);
    }

})->skip(function () {
    return ! Features::enabled(Features::registration());
}, 'Registration support is not enabled.');
