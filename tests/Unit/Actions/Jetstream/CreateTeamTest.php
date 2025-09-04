<?php

use App\Actions\Jetstream\CreateTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Events\AddingTeam;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new CreateTeam;
    $this->user = User::factory()->withPersonalTeam()->create();
});

it('can create a team', function () {
    Event::fake();

    $input = ['name' => 'New Team'];

    $team = $this->action->create($this->user, $input);

    expect($team)
        ->toBeInstanceOf(Team::class)
        ->name->toBe('New Team')
        ->personal_team->toBeFalse()
        ->user_id->toBe($this->user->id);

    Event::assertDispatched(AddingTeam::class);
});

it('requires authorization to create team', function () {
    Gate::shouldReceive('forUser')
        ->with($this->user)
        ->andReturnSelf()
        ->shouldReceive('authorize')
        ->with('create', Mockery::type(Team::class))
        ->andThrow(new \Illuminate\Auth\Access\AuthorizationException);

    $input = ['name' => 'New Team'];

    expect(fn () => $this->action->create($this->user, $input))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});

it('validates team name is required', function () {
    $input = ['name' => ''];

    expect(fn () => $this->action->create($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('validates team name is string', function () {
    $input = ['name' => 123];

    expect(fn () => $this->action->create($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('validates team name maximum length', function () {
    $input = ['name' => str_repeat('a', 256)]; // Too long

    expect(fn () => $this->action->create($this->user, $input))
        ->toThrow(ValidationException::class);
});

it('uses createTeam validation bag', function () {
    $input = ['name' => ''];

    try {
        $this->action->create($this->user, $input);
        expect(false)->toBeTrue('Expected validation exception');
    } catch (ValidationException $e) {
        expect($e->validator->getMessageBag()->getMessages())->toBeArray();
    }
});

it('dispatches adding team event', function () {
    Event::fake();

    $input = ['name' => 'New Team'];

    $this->action->create($this->user, $input);

    Event::assertDispatched(AddingTeam::class, function ($event) {
        return $event->owner->id === $this->user->id;
    });
});

it('switches user to new team', function () {
    $input = ['name' => 'New Team'];

    $team = $this->action->create($this->user, $input);

    $this->user->refresh();

    expect($this->user->current_team_id)->toBe($team->id);
});

it('creates team as non-personal team', function () {
    $input = ['name' => 'Business Team'];

    $team = $this->action->create($this->user, $input);

    expect($team->personal_team)->toBeFalse();
});

it('adds team to user owned teams', function () {
    $initialTeamCount = $this->user->ownedTeams()->count();

    $input = ['name' => 'New Team'];

    $this->action->create($this->user, $input);

    $this->user->refresh();

    expect($this->user->ownedTeams()->count())->toBe($initialTeamCount + 1);
});

it('returns created team instance', function () {
    $input = ['name' => 'Test Team'];

    $team = $this->action->create($this->user, $input);

    expect($team)->toBeInstanceOf(Team::class);
    expect($team->exists)->toBeTrue();
    expect($team->wasRecentlyCreated)->toBeTrue();
});

it('accepts maximum valid name length', function () {
    $input = ['name' => str_repeat('a', 255)]; // Exactly 255 characters

    $team = $this->action->create($this->user, $input);

    expect($team->name)->toBe(str_repeat('a', 255));
});

it('creates team with correct user relationship', function () {
    $input = ['name' => 'User Team'];

    $team = $this->action->create($this->user, $input);

    expect($team->user_id)->toBe($this->user->id);
    expect($team->owner->id)->toBe($this->user->id);
});
