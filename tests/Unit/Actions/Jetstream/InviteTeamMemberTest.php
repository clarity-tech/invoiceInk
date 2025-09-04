<?php

use App\Actions\Jetstream\InviteTeamMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Events\InvitingTeamMember;
use Laravel\Jetstream\Mail\TeamInvitation as TeamInvitationMail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new InviteTeamMember;
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->ownedTeams()->first();
});

it('can invite a team member', function () {
    Mail::fake();
    Event::fake();

    $this->action->invite($this->user, $this->team, 'invited@example.com', 'editor');

    Event::assertDispatched(InvitingTeamMember::class);
    Mail::assertSent(TeamInvitationMail::class);

    expect($this->team->teamInvitations)
        ->toHaveCount(1)
        ->first()->email->toBe('invited@example.com')
        ->first()->role->toBe('editor');
});

it('requires authorization to invite team members', function () {
    Gate::shouldReceive('forUser')
        ->with($this->user)
        ->andReturnSelf()
        ->shouldReceive('authorize')
        ->with('addTeamMember', $this->team)
        ->andThrow(new \Illuminate\Auth\Access\AuthorizationException);

    expect(fn () => $this->action->invite($this->user, $this->team, 'invited@example.com', 'member'))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});

it('validates email is required', function () {
    expect(fn () => $this->action->invite($this->user, $this->team, '', 'member'))
        ->toThrow(ValidationException::class);
});

it('validates email format', function () {
    expect(fn () => $this->action->invite($this->user, $this->team, 'invalid-email', 'member'))
        ->toThrow(ValidationException::class);
});

it('validates email is unique for team invitations', function () {
    // First create a team invitation
    $this->team->teamInvitations()->create([
        'email' => 'existing@example.com',
        'role' => 'editor',
    ]);

    expect(fn () => $this->action->invite($this->user, $this->team, 'existing@example.com', 'admin'))
        ->toThrow(ValidationException::class);
});

it('validates role when roles are enabled', function () {
    expect(fn () => $this->action->invite($this->user, $this->team, 'test@example.com', 'invalid-role'))
        ->toThrow(ValidationException::class);
});

it('prevents inviting existing team members', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $this->team->users()->attach($existingUser);

    expect(fn () => $this->action->invite($this->user, $this->team, 'existing@example.com', 'editor'))
        ->toThrow(ValidationException::class);
});

it('allows inviting with admin role', function () {
    Mail::fake();
    Event::fake();

    $this->action->invite($this->user, $this->team, 'test@example.com', 'admin');

    Event::assertDispatched(InvitingTeamMember::class);
    Mail::assertSent(TeamInvitationMail::class);

    expect($this->team->teamInvitations)
        ->toHaveCount(1)
        ->first()->email->toBe('test@example.com')
        ->first()->role->toBe('admin');
});

it('sends invitation email to correct recipient', function () {
    Mail::fake();

    $this->action->invite($this->user, $this->team, 'recipient@example.com', 'editor');

    Mail::assertSent(TeamInvitationMail::class, function ($mail) {
        return $mail->hasTo('recipient@example.com');
    });
});

it('dispatches inviting team member event with correct data', function () {
    Event::fake();

    $this->action->invite($this->user, $this->team, 'test@example.com', 'admin');

    Event::assertDispatched(InvitingTeamMember::class, function ($event) {
        return $event->team->id === $this->team->id &&
               $event->email === 'test@example.com' &&
               $event->role === 'admin';
    });
});

it('creates team invitation record with correct attributes', function () {
    Mail::fake();

    $this->action->invite($this->user, $this->team, 'test@example.com', 'editor');

    $invitation = $this->team->teamInvitations()->first();

    expect($invitation)
        ->email->toBe('test@example.com')
        ->role->toBe('editor')
        ->team_id->toBe($this->team->id);
});

it('validates with custom error message for duplicate invitation', function () {
    // First create a team invitation
    $this->team->teamInvitations()->create([
        'email' => 'duplicate@example.com',
        'role' => 'editor',
    ]);

    try {
        $this->action->invite($this->user, $this->team, 'duplicate@example.com', 'admin');
        expect(false)->toBeTrue('Expected validation exception');
    } catch (ValidationException $e) {
        expect($e->errors()['email'])->toContain('This user has already been invited to the team.');
    }
});

it('validates with custom error message for existing team member', function () {
    $existingUser = User::factory()->create(['email' => 'member@example.com']);
    $this->team->users()->attach($existingUser);

    try {
        $this->action->invite($this->user, $this->team, 'member@example.com', 'editor');
        expect(false)->toBeTrue('Expected validation exception');
    } catch (ValidationException $e) {
        expect($e->errors()['email'])->toContain('This user already belongs to the team.');
    }
});
