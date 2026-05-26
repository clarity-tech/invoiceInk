<?php

use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->user = createUserWithTeam();
    $this->user->currentTeam->markSetupComplete();
    $this->actingAs($this->user);
});

test('user with multiple owned orgs can switch via PUT /current-team', function () {
    $second = Organization::factory()->completedSetup()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->put('/current-team', ['team_id' => $second->id]);

    $response->assertRedirect();
    expect($this->user->fresh()->current_team_id)->toBe($second->id);
});

test('switching to an org the user does not belong to is forbidden', function () {
    $strangerOrg = Organization::factory()->completedSetup()->create();

    $response = $this->put('/current-team', ['team_id' => $strangerOrg->id]);

    $response->assertForbidden();
    expect($this->user->fresh()->current_team_id)->not->toBe($strangerOrg->id);
});

test('switching to an org user is a member of (not owner) works', function () {
    $owner = User::factory()->withBusinessOrganization()->create();
    $otherOrg = $owner->currentTeam;
    $otherOrg->users()->attach($this->user, ['role' => 'editor']);

    // Refresh the cached teams relation so the request handler sees the attach.
    $this->actingAs($this->user->fresh());

    $response = $this->put('/current-team', ['team_id' => $otherOrg->id]);

    $response->assertRedirect();
    expect($this->user->fresh()->current_team_id)->toBe($otherOrg->id);
});
