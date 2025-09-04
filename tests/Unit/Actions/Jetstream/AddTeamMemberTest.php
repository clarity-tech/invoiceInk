<?php

use App\Actions\Jetstream\AddTeamMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Events\AddingTeamMember;
use Laravel\Jetstream\Events\TeamMemberAdded;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new AddTeamMember;
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->ownedTeams()->first();
    $this->newMember = User::factory()->create(['email' => 'member@example.com']);
});

it('can add team member', function () {
    Event::fake();

    $this->action->add($this->user, $this->team, 'member@example.com', 'editor');

    Event::assertDispatched(AddingTeamMember::class);
    Event::assertDispatched(TeamMemberAdded::class);

    $this->team->refresh();
    expect($this->team->users)
        ->toHaveCount(1) // new member (owner is not in users relationship)
        ->pluck('email')->toContain('member@example.com');
});

it('requires authorization to add team members', function () {
    Gate::shouldReceive('forUser')
        ->with($this->user)
        ->andReturnSelf()
        ->shouldReceive('authorize')
        ->with('addTeamMember', $this->team)
        ->andThrow(new \Illuminate\Auth\Access\AuthorizationException);

    expect(fn () => $this->action->add($this->user, $this->team, 'member@example.com', 'editor'))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});

it('validates email is required', function () {
    expect(fn () => $this->action->add($this->user, $this->team, '', 'editor'))
        ->toThrow(ValidationException::class);
});

it('validates email format', function () {
    expect(fn () => $this->action->add($this->user, $this->team, 'invalid-email', 'editor'))
        ->toThrow(ValidationException::class);
});

it('validates user exists', function () {
    expect(fn () => $this->action->add($this->user, $this->team, 'nonexistent@example.com', 'editor'))
        ->toThrow(ValidationException::class);
});

it('validates role when roles are enabled', function () {
    expect(fn () => $this->action->add($this->user, $this->team, 'member@example.com', 'invalid-role'))
        ->toThrow(ValidationException::class);
});

it('prevents adding existing team members', function () {
    $this->team->users()->attach($this->newMember, ['role' => 'editor']);

    expect(fn () => $this->action->add($this->user, $this->team, 'member@example.com', 'admin'))
        ->toThrow(ValidationException::class);
});

it('dispatches adding team member event', function () {
    Event::fake();

    $this->action->add($this->user, $this->team, 'member@example.com', 'editor');

    Event::assertDispatched(AddingTeamMember::class, function ($event) {
        return $event->team->id === $this->team->id &&
               $event->user->email === 'member@example.com';
    });
});

it('dispatches team member added event', function () {
    Event::fake();

    $this->action->add($this->user, $this->team, 'member@example.com', 'editor');

    Event::assertDispatched(TeamMemberAdded::class, function ($event) {
        return $event->team->id === $this->team->id &&
               $event->user->email === 'member@example.com';
    });
});

it('attaches user with correct role', function () {
    $this->action->add($this->user, $this->team, 'member@example.com', 'admin');

    // Check that the user was attached to the team
    expect($this->team->users()->where('email', 'member@example.com')->exists())->toBeTrue();

    // Check the role in the database directly
    $membership = \DB::table('team_user')
        ->where('team_id', $this->team->id)
        ->where('user_id', $this->newMember->id)
        ->first();

    expect($membership)->not->toBeNull();
    expect($membership->role)->toBe('admin');
});

it('allows adding with editor role', function () {
    $this->action->add($this->user, $this->team, 'member@example.com', 'editor');

    // Check that the user was attached to the team
    expect($this->team->users()->where('email', 'member@example.com')->exists())->toBeTrue();

    // Check the role in the database directly
    $membership = \DB::table('team_user')
        ->where('team_id', $this->team->id)
        ->where('user_id', $this->newMember->id)
        ->first();

    expect($membership)->not->toBeNull();
    expect($membership->role)->toBe('editor');
});

it('validates with custom error message for non-existent user', function () {
    try {
        $this->action->add($this->user, $this->team, 'nonexistent@example.com', 'editor');
        expect(false)->toBeTrue('Expected validation exception');
    } catch (ValidationException $e) {
        expect($e->errors()['email'])->toContain('We were unable to find a registered user with this email address.');
    }
});

it('validates with custom error message for existing team member', function () {
    $this->team->users()->attach($this->newMember, ['role' => 'editor']);

    try {
        $this->action->add($this->user, $this->team, 'member@example.com', 'admin');
        expect(false)->toBeTrue('Expected validation exception');
    } catch (ValidationException $e) {
        expect($e->errors()['email'])->toContain('This user already belongs to the team.');
    }
});

it('uses addTeamMember validation bag', function () {
    try {
        $this->action->add($this->user, $this->team, '', 'editor');
        expect(false)->toBeTrue('Expected validation exception');
    } catch (ValidationException $e) {
        expect($e->validator->getMessageBag()->getMessages())->toBeArray();
    }
});

it('finds user by email successfully', function () {
    $initialCount = $this->team->users()->count();

    $this->action->add($this->user, $this->team, 'member@example.com', 'editor');

    expect($this->team->users()->count())->toBe($initialCount + 1);
    expect($this->team->users()->where('email', 'member@example.com')->exists())->toBeTrue();
});
