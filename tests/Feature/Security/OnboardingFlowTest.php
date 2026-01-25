<?php

use App\Models\Organization;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Onboarding Flow & Setup Middleware Tests
|--------------------------------------------------------------------------
|
| Verify that the EnsureOrganizationSetup middleware correctly enforces
| setup completion before allowing access to protected routes. Tests cover
| middleware enforcement, bypass routes, personal team skip logic, and
| the setup completion flow.
|
*/

// ──────────────────────────────────────────────────────
// Middleware enforcement: incomplete setup → redirect
// ──────────────────────────────────────────────────────

test('incomplete setup user accessing dashboard is redirected to setup wizard', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('organization.setup'));
});

test('incomplete setup user accessing invoices is redirected to setup wizard', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/invoices')
        ->assertRedirect(route('organization.setup'));
});

test('incomplete setup user accessing customers is redirected to setup wizard', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/customers')
        ->assertRedirect(route('organization.setup'));
});

test('incomplete setup user accessing organizations is redirected to setup wizard', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/organizations')
        ->assertRedirect(route('organization.setup'));
});

test('completed setup user can access dashboard', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => now()]);

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
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/organization/setup')
        ->assertSuccessful();
});

test('incomplete setup user can access profile page', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    $this->actingAs($user)
        ->get('/user/profile')
        ->assertSuccessful();
});

test('incomplete setup user can logout', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');
});

// ──────────────────────────────────────────────────────
// Personal team skip: personal teams bypass setup check
// ──────────────────────────────────────────────────────

test('personal team user can access dashboard without setup completion', function () {
    $user = createUserWithTeam();
    // Default createUserWithTeam sets personal_team=true, setup_completed_at=null
    expect($user->currentTeam->personal_team)->toBeTrue();
    expect($user->currentTeam->setup_completed_at)->toBeNull();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});

test('personal team needsSetup returns false regardless of setup_completed_at', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;

    expect($org->personal_team)->toBeTrue();
    expect($org->setup_completed_at)->toBeNull();
    expect($org->needsSetup())->toBeFalse();
});

test('non-personal team with null setup_completed_at needsSetup returns true', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    expect($org->fresh()->needsSetup())->toBeTrue();
});

// ──────────────────────────────────────────────────────
// Setup completion flow
// ──────────────────────────────────────────────────────

test('markSetupComplete sets setup_completed_at to non-null', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    expect($org->fresh()->isSetupComplete())->toBeFalse();

    $org->markSetupComplete();

    expect($org->fresh()->isSetupComplete())->toBeTrue();
    expect($org->fresh()->setup_completed_at)->not->toBeNull();
});

test('after setup completion middleware allows access to dashboard', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

    // Before completion — redirected
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('organization.setup'));

    // Complete setup
    $org->markSetupComplete();

    // After completion — allowed
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});

test('setup redirect flashes setup-required message', function () {
    $user = createUserWithTeam();
    $org = $user->currentTeam;
    $org->update(['personal_team' => false, 'setup_completed_at' => null]);

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

    // Should not redirect to organization setup — may succeed or redirect elsewhere
    expect($response->headers->get('Location', ''))
        ->not->toContain('organization/setup');
});
