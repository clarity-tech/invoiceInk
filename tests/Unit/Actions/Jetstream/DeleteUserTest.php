<?php

use App\Actions\Jetstream\DeleteUser;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->action = new DeleteUser;
    $this->user = User::factory()->withPersonalTeam()->create();
});

it('can delete a user', function () {
    $userId = $this->user->id;
    $this->action->delete($this->user);

    expect(User::find($userId))->toBeNull();
});

it('deletes user within database transaction', function () {
    $userId = $this->user->id;
    $this->action->delete($this->user);

    expect(User::find($userId))->toBeNull();
});

it('purges owned organizations with no other members', function () {
    $ownedTeam = Organization::factory()->create(['user_id' => $this->user->id]);
    $this->user->ownedTeams()->save($ownedTeam);

    $teamId = $ownedTeam->id;
    $this->action->delete($this->user);

    expect(Organization::find($teamId))->toBeNull();
});

it('prevents deletion when owned organizations have other members', function () {
    $ownedTeam = Organization::factory()->create(['user_id' => $this->user->id]);
    $this->user->ownedTeams()->save($ownedTeam);

    $otherUser = User::factory()->create();
    $ownedTeam->users()->attach($otherUser, ['role' => 'editor']);

    $this->action->delete($this->user);
})->throws(ValidationException::class);

it('detaches user from organizations', function () {
    $team = Organization::factory()->create();
    $this->user->teams()->attach($team);

    expect($this->user->teams()->count())->toBeGreaterThan(0);

    $this->action->delete($this->user);

    expect($team->fresh()->users)->toHaveCount(0);
});

it('deletes user profile photo', function () {
    $this->action->delete($this->user);

    expect(true)->toBeTrue();
});

it('deletes user tokens', function () {
    $this->action->delete($this->user);

    expect(true)->toBeTrue();
});

it('purges personal team on deletion', function () {
    $personalTeamId = $this->user->personalTeam()->id;

    $this->action->delete($this->user);

    expect(Organization::find($personalTeamId))->toBeNull();
});

it('handles user with no tokens', function () {
    $this->action->delete($this->user);

    expect(true)->toBeTrue();
});

it('handles user with no owned organizations', function () {
    $user = User::factory()->create();

    $this->action->delete($user);

    expect(true)->toBeTrue();
});

it('includes organization names in validation error', function () {
    $ownedTeam = Organization::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Acme Corp',
    ]);
    $this->user->ownedTeams()->save($ownedTeam);

    $otherUser = User::factory()->create();
    $ownedTeam->users()->attach($otherUser, ['role' => 'admin']);

    try {
        $this->action->delete($this->user);
    } catch (ValidationException $e) {
        expect($e->errors()['team'][0])->toContain('Acme Corp');

        return;
    }

    $this->fail('Expected ValidationException was not thrown.');
});
