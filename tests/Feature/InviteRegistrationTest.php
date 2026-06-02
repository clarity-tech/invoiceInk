<?php

use App\Models\TeamInvitation;
use App\Models\User;
use App\Support\Jetstream;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Invite-first / member-only registration
|--------------------------------------------------------------------------
|
| A brand-new invitee (e.g. an accountant) registers through the invitation
| accept link and joins their employer's org WITHOUT getting one of their own.
|
*/

beforeEach(function () {
    // A set-up employer organization that does the inviting.
    $this->owner = createUserWithTeam(['name' => 'Employer Owner']);
    $this->employerOrg = $this->owner->currentTeam;
    $this->invitation = $this->employerOrg->teamInvitations()->create([
        'email' => 'accountant@firm.test',
        'role' => 'editor',
    ]);
    $this->acceptUrl = URL::signedRoute('team-invitations.accept', ['invitation' => $this->invitation]);
});

test('accept link for a non-registered email redirects to register and stashes the invitation', function () {
    $response = $this->get($this->acceptUrl);

    $response->assertRedirect(route('register'));
    expect(session('pending_invitation_id'))->toBe($this->invitation->id);
});

test('registering through an invite joins the employer org with no org of your own', function () {
    $this->get($this->acceptUrl); // stashes pending_invitation_id

    $response = $this->post('/register', [
        'name' => 'Priya Accountant',
        'email' => 'accountant@firm.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::where('email', 'accountant@firm.test')->first();

    expect($user)->not->toBeNull();
    expect($user->ownedTeams()->count())->toBe(0);               // owns nothing
    expect($user->teams()->count())->toBe(1);                    // member of the employer
    expect($user->teams->first()->id)->toBe($this->employerOrg->id);
    expect($user->teams->first()->membership->role)->toBe('editor');
    expect($user->email_verified_at)->not->toBeNull();           // signed link = verified
    expect($user->current_team_id)->toBe($this->employerOrg->id);
    expect(TeamInvitation::find($this->invitation->id))->toBeNull();
});

test('a member-only user lands in the employer org and can work in it', function () {
    $this->get($this->acceptUrl);
    $this->post('/register', [
        'name' => 'Priya Accountant',
        'email' => 'accountant@firm.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $this->get('/dashboard')->assertOk();

    // Editor can create a customer, scoped to the employer org.
    $this->post('/customers', [
        'name' => 'Client Co',
        'currency' => 'INR',
        'contacts' => [['name' => 'Contact', 'email' => 'contact@client.test']],
    ])->assertRedirect();

    $this->assertDatabaseHas('customers', [
        'name' => 'Client Co',
        'organization_id' => $this->employerOrg->id,
    ]);
});

test('a member-only user may still create their own organization (soft ownership)', function () {
    $this->get($this->acceptUrl);
    $this->post('/register', [
        'name' => 'Priya Accountant',
        'email' => 'accountant@firm.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $user = User::where('email', 'accountant@firm.test')->first();

    $this->post('/teams', ['name' => 'My Practice'])
        ->assertRedirect(route('organization.setup'));

    expect($user->fresh()->ownedTeams()->count())->toBe(1);
});

test('an existing user invited and clicking the link while logged in is attached without a new org', function () {
    $existing = createUserWithTeam(['name' => 'Existing User', 'email' => 'existing@firm.test']);
    $invitation = $this->employerOrg->teamInvitations()->create([
        'email' => 'existing@firm.test',
        'role' => 'admin',
    ]);
    $url = URL::signedRoute('team-invitations.accept', ['invitation' => $invitation]);

    $this->actingAs($existing)->get($url)->assertRedirect(config('fortify.home'));

    expect($existing->fresh()->teams()->where('teams.id', $this->employerOrg->id)->exists())->toBeTrue();
    expect($existing->fresh()->ownedTeams()->count())->toBe(1); // unchanged
    expect(TeamInvitation::find($invitation->id))->toBeNull();
});

test('an existing user clicking the link while logged out is attached and sent to login', function () {
    $existing = createUserWithTeam(['name' => 'Existing User', 'email' => 'existing@firm.test']);
    $invitation = $this->employerOrg->teamInvitations()->create([
        'email' => 'existing@firm.test',
        'role' => 'admin',
    ]);
    $url = URL::signedRoute('team-invitations.accept', ['invitation' => $invitation]);

    $this->get($url)->assertRedirect(route('login'));

    expect($existing->fresh()->teams()->where('teams.id', $this->employerOrg->id)->exists())->toBeTrue();
});

test('a stale invitation in session does not affect a normal business signup', function () {
    // Session points at an invitation for a different email; the registrant is
    // starting their own business and must still get an org.
    $this->get($this->acceptUrl); // stashes invitation for accountant@firm.test

    $this->post('/register', [
        'name' => 'Sole Trader',
        'email' => 'owner@mybiz.test', // different email → not the invitee
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
    ]);

    $user = User::where('email', 'owner@mybiz.test')->first();
    expect($user->ownedTeams()->count())->toBe(1);
    expect(TeamInvitation::find($this->invitation->id))->not->toBeNull(); // untouched
});
