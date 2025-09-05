<?php

use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->invitation = TeamInvitation::factory()->create(['team_id' => $this->team->id]);
});

it('can create a team invitation', function () {
    $invitation = TeamInvitation::factory()->create([
        'email' => 'test@example.com',
        'role' => 'member',
        'team_id' => $this->team->id,
    ]);

    expect($invitation)
        ->email->toBe('test@example.com')
        ->role->toBe('member')
        ->team_id->toBe($this->team->id);
});

it('has fillable attributes', function () {
    $fillable = ['email', 'role'];

    expect($this->invitation->getFillable())->toBe($fillable);
});

it('belongs to a team', function () {
    expect($this->invitation->team)->toBeInstanceOf(Team::class);
    expect($this->invitation->team->id)->toBe($this->team->id);
});

it('extends JetstreamTeamInvitation', function () {
    expect($this->invitation)->toBeInstanceOf(\Laravel\Jetstream\TeamInvitation::class);
});

it('can have different roles', function () {
    $adminInvitation = TeamInvitation::factory()->create([
        'role' => 'admin',
        'team_id' => $this->team->id,
    ]);

    $memberInvitation = TeamInvitation::factory()->create([
        'role' => 'member',
        'team_id' => $this->team->id,
    ]);

    expect($adminInvitation->role)->toBe('admin');
    expect($memberInvitation->role)->toBe('member');
});

it('stores email address', function () {
    $invitation = TeamInvitation::factory()->create([
        'email' => 'invite@example.com',
        'team_id' => $this->team->id,
    ]);

    expect($invitation->email)->toBe('invite@example.com');
});

it('can be mass assigned', function () {
    $invitation = new TeamInvitation;
    $invitation->fill([
        'email' => 'test@example.com',
        'role' => 'editor',
    ]);

    expect($invitation->email)->toBe('test@example.com');
    expect($invitation->role)->toBe('editor');
});

it('has team relationship using Jetstream model', function () {
    $relationship = $this->invitation->team();

    expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($relationship->getRelated())->toBeInstanceOf(\Laravel\Jetstream\Team::class);
});
