<?php

use App\Models\Organization;

beforeEach(function () {
    // A fully set-up user with one organization, mirroring a real logged-in owner.
    $this->user = createUserWithTeam();
    $this->user->currentTeam->markSetupComplete();
    $this->actingAs($this->user);
});

test('the create organization page renders', function () {
    $response = $this->get('/teams/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Teams/Create'));
});

test('user can create a new organization and is sent into the setup wizard', function () {
    $before = $this->user->ownedTeams()->count();

    $response = $this->post('/teams', ['name' => 'Acme Trading']);

    $response->assertRedirect(route('organization.setup'));

    expect($this->user->ownedTeams()->count())->toBe($before + 1);

    $newOrg = $this->user->ownedTeams()->where('name', 'Acme Trading')->first();
    expect($newOrg)->not->toBeNull();
    expect($newOrg->setup_completed_at)->toBeNull();
});

test('creating an organization switches the user to it', function () {
    $this->post('/teams', ['name' => 'Second Org']);

    $newOrg = Organization::where('name', 'Second Org')->where('user_id', $this->user->id)->first();

    expect($this->user->fresh()->current_team_id)->toBe($newOrg->id);
});

test('creating an organization requires a name', function () {
    $response = $this->post('/teams', ['name' => '']);

    $response->assertSessionHasErrors('name', null, 'createTeam');
});

test('a new organization blocks the dashboard until setup is completed', function () {
    $this->post('/teams', ['name' => 'Needs Setup Co']);

    // The EnsureOrganizationSetup middleware should bounce the now-active,
    // un-set-up org back to the setup wizard.
    $this->get('/dashboard')->assertRedirect(route('organization.setup'));
});
