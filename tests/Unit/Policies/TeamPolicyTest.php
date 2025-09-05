<?php

use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new TeamPolicy;
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->ownedTeams()->first();
    $this->otherUser = User::factory()->withPersonalTeam()->create();
    $this->otherTeam = $this->otherUser->ownedTeams()->first();
});

it('allows any user to view any teams', function () {
    $result = $this->policy->viewAny($this->user);

    expect($result)->toBeTrue();
});

it('allows any user to view any teams regardless of ownership', function () {
    $result = $this->policy->viewAny($this->otherUser);

    expect($result)->toBeTrue();
});

it('allows team members to view their team', function () {
    $result = $this->policy->view($this->user, $this->team);

    expect($result)->toBeTrue();
});

it('prevents non-members from viewing team', function () {
    $result = $this->policy->view($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('allows team members added via invitation to view team', function () {
    // Add other user to the team
    $this->team->users()->attach($this->otherUser, ['role' => 'editor']);
    $this->otherUser->refresh();

    $result = $this->policy->view($this->otherUser, $this->team);

    expect($result)->toBeTrue();
});

it('allows any user to create teams', function () {
    $result = $this->policy->create($this->user);

    expect($result)->toBeTrue();
});

it('allows any user to create teams regardless of existing teams', function () {
    $result = $this->policy->create($this->otherUser);

    expect($result)->toBeTrue();
});

it('allows team owners to update their team', function () {
    $result = $this->policy->update($this->user, $this->team);

    expect($result)->toBeTrue();
});

it('prevents non-owners from updating team', function () {
    $result = $this->policy->update($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('prevents team members from updating team if not owner', function () {
    // Add other user as member but not owner
    $this->team->users()->attach($this->otherUser, ['role' => 'editor']);

    $result = $this->policy->update($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('allows team owners to add team members', function () {
    $result = $this->policy->addTeamMember($this->user, $this->team);

    expect($result)->toBeTrue();
});

it('prevents non-owners from adding team members', function () {
    $result = $this->policy->addTeamMember($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('prevents team members from adding other team members if not owner', function () {
    // Add other user as member but not owner
    $this->team->users()->attach($this->otherUser, ['role' => 'admin']);

    $result = $this->policy->addTeamMember($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('allows team owners to update team member permissions', function () {
    $result = $this->policy->updateTeamMember($this->user, $this->team);

    expect($result)->toBeTrue();
});

it('prevents non-owners from updating team member permissions', function () {
    $result = $this->policy->updateTeamMember($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('prevents team members from updating team member permissions if not owner', function () {
    // Add other user as admin member but not owner
    $this->team->users()->attach($this->otherUser, ['role' => 'admin']);

    $result = $this->policy->updateTeamMember($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('allows team owners to remove team members', function () {
    $result = $this->policy->removeTeamMember($this->user, $this->team);

    expect($result)->toBeTrue();
});

it('prevents non-owners from removing team members', function () {
    $result = $this->policy->removeTeamMember($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('prevents team members from removing other team members if not owner', function () {
    // Add other user as admin member but not owner
    $this->team->users()->attach($this->otherUser, ['role' => 'admin']);

    $result = $this->policy->removeTeamMember($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('allows team owners to delete their team', function () {
    $result = $this->policy->delete($this->user, $this->team);

    expect($result)->toBeTrue();
});

it('prevents non-owners from deleting team', function () {
    $result = $this->policy->delete($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('prevents team members from deleting team if not owner', function () {
    // Add other user as admin member but not owner
    $this->team->users()->attach($this->otherUser, ['role' => 'admin']);

    $result = $this->policy->delete($this->otherUser, $this->team);

    expect($result)->toBeFalse();
});

it('uses HandlesAuthorization trait', function () {
    $traits = class_uses(TeamPolicy::class);

    expect($traits)->toHaveKey('Illuminate\Auth\Access\HandlesAuthorization');
});

it('has all required policy methods', function () {
    $methods = get_class_methods(TeamPolicy::class);

    $expectedMethods = [
        'viewAny',
        'view',
        'create',
        'update',
        'addTeamMember',
        'updateTeamMember',
        'removeTeamMember',
        'delete',
    ];

    foreach ($expectedMethods as $method) {
        expect($methods)->toContain($method);
    }
});

it('correctly identifies team ownership through ownsTeam method', function () {
    // Test that the policy relies on the User model's ownsTeam method
    expect($this->user->ownsTeam($this->team))->toBeTrue();
    expect($this->otherUser->ownsTeam($this->team))->toBeFalse();

    // Test policy methods that depend on ownership
    expect($this->policy->update($this->user, $this->team))->toBeTrue();
    expect($this->policy->update($this->otherUser, $this->team))->toBeFalse();
});

it('correctly identifies team membership through belongsToTeam method', function () {
    // Test that the policy relies on the User model's belongsToTeam method
    expect($this->user->belongsToTeam($this->team))->toBeTrue();
    expect($this->otherUser->belongsToTeam($this->team))->toBeFalse();

    // Add other user to team and test again
    $this->team->users()->attach($this->otherUser, ['role' => 'editor']);
    $this->otherUser->refresh(); // Refresh the user to reload relationships
    expect($this->otherUser->belongsToTeam($this->team))->toBeTrue();

    // Test policy method that depends on membership
    expect($this->policy->view($this->otherUser, $this->team))->toBeTrue();
});

it('owner permissions are different from member permissions', function () {
    // Add other user as team member
    $this->team->users()->attach($this->otherUser, ['role' => 'admin']);
    $this->otherUser->refresh();

    // Owner can do everything
    expect($this->policy->view($this->user, $this->team))->toBeTrue();
    expect($this->policy->update($this->user, $this->team))->toBeTrue();
    expect($this->policy->addTeamMember($this->user, $this->team))->toBeTrue();
    expect($this->policy->removeTeamMember($this->user, $this->team))->toBeTrue();
    expect($this->policy->delete($this->user, $this->team))->toBeTrue();

    // Member can only view
    expect($this->policy->view($this->otherUser, $this->team))->toBeTrue();
    expect($this->policy->update($this->otherUser, $this->team))->toBeFalse();
    expect($this->policy->addTeamMember($this->otherUser, $this->team))->toBeFalse();
    expect($this->policy->removeTeamMember($this->otherUser, $this->team))->toBeFalse();
    expect($this->policy->delete($this->otherUser, $this->team))->toBeFalse();
});

it('personal teams follow same ownership rules', function () {
    $personalTeam = $this->user->personalTeam();

    // Owner can manage personal team
    expect($this->policy->view($this->user, $personalTeam))->toBeTrue();
    expect($this->policy->update($this->user, $personalTeam))->toBeTrue();
    expect($this->policy->addTeamMember($this->user, $personalTeam))->toBeTrue();

    // Other users cannot manage personal team
    expect($this->policy->view($this->otherUser, $personalTeam))->toBeFalse();
    expect($this->policy->update($this->otherUser, $personalTeam))->toBeFalse();
    expect($this->policy->addTeamMember($this->otherUser, $personalTeam))->toBeFalse();
});
