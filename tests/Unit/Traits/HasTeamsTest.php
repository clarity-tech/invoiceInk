<?php

use App\Models\Membership;
use App\Models\User;
use App\Support\Jetstream;
use App\Support\OwnerRole;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->personalTeam();
});

// --- isCurrentTeam ---

test('isCurrentTeam returns true for the current team', function () {
    $this->user->switchTeam($this->team);

    expect($this->user->isCurrentTeam($this->team))->toBeTrue();
});

test('isCurrentTeam returns false for a different team', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    $this->user->switchTeam($this->team);

    expect($this->user->isCurrentTeam($otherTeam))->toBeFalse();
});

// --- currentTeam ---

test('currentTeam auto-switches to personal team when current_team_id is null', function () {
    $this->user->forceFill(['current_team_id' => null])->save();
    $this->user->unsetRelation('currentTeam');

    $currentTeam = $this->user->currentTeam;

    expect($currentTeam)->not->toBeNull();
    expect($currentTeam->id)->toBe($this->team->id);
});

test('currentTeam returns the set team', function () {
    $this->user->switchTeam($this->team);

    expect($this->user->currentTeam->id)->toBe($this->team->id);
});

// --- ownsTeam ---

test('ownsTeam returns true for owned team', function () {
    expect($this->user->ownsTeam($this->team))->toBeTrue();
});

test('ownsTeam returns false for null', function () {
    expect($this->user->ownsTeam(null))->toBeFalse();
});

test('ownsTeam returns false for team owned by another user', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    expect($this->user->ownsTeam($otherTeam))->toBeFalse();
});

// --- belongsToTeam ---

test('belongsToTeam returns true for owned team', function () {
    expect($this->user->belongsToTeam($this->team))->toBeTrue();
});

test('belongsToTeam returns false for null', function () {
    expect($this->user->belongsToTeam(null))->toBeFalse();
});

test('belongsToTeam returns true for team user is member of', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    // Add user as a member of otherTeam
    Membership::create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'role' => 'editor',
    ]);
    $this->user->unsetRelation('teams');

    expect($this->user->belongsToTeam($otherTeam))->toBeTrue();
});

test('belongsToTeam returns false for unrelated team', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    expect($this->user->belongsToTeam($otherTeam))->toBeFalse();
});

// --- allTeams ---

test('allTeams includes owned teams', function () {
    $allTeams = $this->user->allTeams();

    expect($allTeams)->toHaveCount(1);
    expect($allTeams->first()->id)->toBe($this->team->id);
});

test('allTeams includes teams user is member of', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    Membership::create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'role' => 'editor',
    ]);

    $allTeams = $this->user->allTeams();

    expect($allTeams)->toHaveCount(2);
    expect($allTeams->pluck('id')->toArray())->toContain($this->team->id, $otherTeam->id);
});

// --- teamRole ---

test('teamRole returns OwnerRole for team owner', function () {
    $role = $this->user->teamRole($this->team);

    expect($role)->toBeInstanceOf(OwnerRole::class);
    expect($role->key)->toBe('owner');
});

test('teamRole returns null for non-member', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    $role = $this->user->teamRole($otherTeam);

    expect($role)->toBeNull();
});

test('teamRole returns correct role for team member', function () {
    Jetstream::role('editor', 'Editor', ['read', 'create']);

    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    Membership::create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'role' => 'editor',
    ]);

    // Load the team with users relationship
    $otherTeam->load('users');

    $role = $this->user->teamRole($otherTeam);

    expect($role)->not->toBeNull();
    expect($role->key)->toBe('editor');

    // Cleanup static state
    Jetstream::$roles = [];
    Jetstream::$permissions = [];
});

test('teamRole returns null when member has no recognized role', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    Membership::create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'role' => 'nonexistent_role',
    ]);

    $otherTeam->load('users');
    Jetstream::$roles = [];

    $role = $this->user->teamRole($otherTeam);

    expect($role)->toBeNull();
});

// --- hasTeamRole ---

test('hasTeamRole returns true for owner regardless of role name', function () {
    expect($this->user->hasTeamRole($this->team, 'admin'))->toBeTrue();
    expect($this->user->hasTeamRole($this->team, 'editor'))->toBeTrue();
});

test('hasTeamRole returns false for non-member', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    expect($this->user->hasTeamRole($otherTeam, 'editor'))->toBeFalse();
});

test('hasTeamRole returns true when member has matching role', function () {
    Jetstream::role('editor', 'Editor', ['read', 'create']);

    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    Membership::create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'role' => 'editor',
    ]);

    $otherTeam->load('users');
    $this->user->unsetRelation('teams');

    expect($this->user->hasTeamRole($otherTeam, 'editor'))->toBeTrue();

    Jetstream::$roles = [];
    Jetstream::$permissions = [];
});

// --- teamPermissions ---

test('teamPermissions returns wildcard for owner', function () {
    expect($this->user->teamPermissions($this->team))->toBe(['*']);
});

test('teamPermissions returns empty array for non-member', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    expect($this->user->teamPermissions($otherTeam))->toBe([]);
});

test('teamPermissions returns role permissions for member', function () {
    Jetstream::role('editor', 'Editor', ['read', 'create']);

    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    Membership::create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'role' => 'editor',
    ]);

    $otherTeam->load('users');
    $this->user->unsetRelation('teams');

    $permissions = $this->user->teamPermissions($otherTeam);

    expect($permissions)->toContain('read', 'create');

    Jetstream::$roles = [];
    Jetstream::$permissions = [];
});

// --- hasTeamPermission ---

test('hasTeamPermission returns true for owner', function () {
    expect($this->user->hasTeamPermission($this->team, 'anything'))->toBeTrue();
});

test('hasTeamPermission returns false for non-member', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    expect($this->user->hasTeamPermission($otherTeam, 'read'))->toBeFalse();
});

test('hasTeamPermission checks role permissions for member', function () {
    Jetstream::role('editor', 'Editor', ['read', 'create']);

    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    Membership::create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'role' => 'editor',
    ]);

    $otherTeam->load('users');
    $this->user->unsetRelation('teams');

    expect($this->user->hasTeamPermission($otherTeam, 'read'))->toBeTrue();
    expect($this->user->hasTeamPermission($otherTeam, 'delete'))->toBeFalse();

    Jetstream::$roles = [];
    Jetstream::$permissions = [];
});

test('hasTeamPermission supports wildcard create permissions', function () {
    Jetstream::role('creator', 'Creator', ['*:create']);

    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    Membership::create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'role' => 'creator',
    ]);

    $otherTeam->load('users');
    $this->user->unsetRelation('teams');

    expect($this->user->hasTeamPermission($otherTeam, 'invoice:create'))->toBeTrue();
    expect($this->user->hasTeamPermission($otherTeam, 'invoice:update'))->toBeFalse();

    Jetstream::$roles = [];
    Jetstream::$permissions = [];
});

test('hasTeamPermission supports wildcard update permissions', function () {
    Jetstream::role('updater', 'Updater', ['*:update']);

    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    Membership::create([
        'team_id' => $otherTeam->id,
        'user_id' => $this->user->id,
        'role' => 'updater',
    ]);

    $otherTeam->load('users');
    $this->user->unsetRelation('teams');

    expect($this->user->hasTeamPermission($otherTeam, 'invoice:update'))->toBeTrue();
    expect($this->user->hasTeamPermission($otherTeam, 'invoice:create'))->toBeFalse();

    Jetstream::$roles = [];
    Jetstream::$permissions = [];
});

// --- switchTeam ---

test('switchTeam returns false for non-member team', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $otherTeam = $otherUser->personalTeam();

    expect($this->user->switchTeam($otherTeam))->toBeFalse();
});

test('switchTeam returns true and updates current team', function () {
    expect($this->user->switchTeam($this->team))->toBeTrue();
    expect($this->user->fresh()->current_team_id)->toBe($this->team->id);
});

// --- personalTeam ---

test('personalTeam returns the personal team', function () {
    expect($this->user->personalTeam())->not->toBeNull();
    expect($this->user->personalTeam()->personal_team)->toBeTrue();
});

test('personalTeam returns null when user has no personal team', function () {
    $user = User::factory()->create();

    expect($user->personalTeam())->toBeNull();
});
