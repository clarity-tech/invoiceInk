<?php

use App\Models\Organization;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Onboarding Flow & Setup Middleware Tests
|--------------------------------------------------------------------------
|
| Verify that the EnsureOrganizationSetup middleware correctly enforces
| setup completion before allowing access to protected routes. Every org
| must complete setup — there is no "personal team" bypass.
|
*/

// ──────────────────────────────────────────────────────
// Middleware enforcement: incomplete setup → redirect
// ──────────────────────────────────────────────────────

test('incomplete setup user accessing dashboard is redirected to setup wizard', function () {
    $user = createUserWithTeam();
    $user->currentTeam->update(['setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('organization.setup'));
});

test('incomplete setup user accessing invoices is redirected to setup wizard', function () {
    $user = createUserWithTeam();
    $user->currentTeam->update(['setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/invoices')
        ->assertRedirect(route('organization.setup'));
});

test('incomplete setup user accessing customers is redirected to setup wizard', function () {
    $user = createUserWithTeam();
    $user->currentTeam->update(['setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/customers')
        ->assertRedirect(route('organization.setup'));
});

test('incomplete setup user accessing organizations is redirected to setup wizard', function () {
    $user = createUserWithTeam();
    $user->currentTeam->update(['setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/organizations')
        ->assertRedirect(route('organization.setup'));
});

test('completed setup user can access dashboard', function () {
    $user = createUserWithTeam();
    $user->currentTeam->update(['setup_completed_at' => now()]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});

test('unauthenticated user is redirected to login not setup', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

// ──────────────────────────────────────────────────────
// Middleware bypass: allowed routes for incomplete setup
// ──────────────────────────────────────────────────────

test('incomplete setup user can access setup wizard', function () {
    $user = createUserWithTeam();
    $user->currentTeam->update(['setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/organization/setup')
        ->assertSuccessful();
});

test('incomplete setup user can access profile page', function () {
    $user = createUserWithTeam();
    $user->currentTeam->update(['setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/user/profile')
        ->assertSuccessful();
});

test('incomplete setup user can logout', function () {
    $user = createUserWithTeam();
    $user->currentTeam->update(['setup_completed_at' => null]);

    $response = $this->actingAs($user)->post('/logout');

    // Logout bypasses the setup-enforcement middleware; the exact redirect
    // target depends on Fortify config but must not bounce back to setup.
    $response->assertRedirect();
    expect($response->headers->get('Location', ''))
        ->not->toContain('organization/setup');
    $this->assertGuest();
});

test('incomplete setup user can switch to a setup-complete org', function () {
    $user = createUserWithTeam();
    $user->currentTeam->update(['setup_completed_at' => null]);

    $completeOrg = Organization::factory()->completedSetup()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->put('/current-team', ['team_id' => $completeOrg->id])
        ->assertRedirect();

    expect($user->fresh()->current_team_id)->toBe($completeOrg->id);
});

// ──────────────────────────────────────────────────────
// needsSetup logic: depends only on setup_completed_at
// ──────────────────────────────────────────────────────

test('org with null setup_completed_at needs setup', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['setup_completed_at' => null]);

    expect($org->fresh()->needsSetup())->toBeTrue();
});

test('org with non-null setup_completed_at does not need setup', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['setup_completed_at' => now()]);

    expect($org->fresh()->needsSetup())->toBeFalse();
});

// ──────────────────────────────────────────────────────
// Setup completion flow
// ──────────────────────────────────────────────────────

test('markSetupComplete sets setup_completed_at to non-null', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['setup_completed_at' => null]);

    expect($org->fresh()->isSetupComplete())->toBeFalse();

    $org->markSetupComplete();

    expect($org->fresh()->isSetupComplete())->toBeTrue();
    expect($org->fresh()->setup_completed_at)->not->toBeNull();
});

test('after setup completion middleware allows access to dashboard', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('organization.setup'));

    $org->markSetupComplete();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});

test('setup redirect flashes setup-required message', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('organization.setup'))
        ->assertSessionHas('setup-required');
});

test('user with no current team is not redirected to setup by middleware', function () {
    $user = User::create([
        'name' => 'No Team User',
        'email' => 'noteam'.uniqid().'@example.test',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    expect($response->headers->get('Location', ''))
        ->not->toContain('organization/setup');
});
