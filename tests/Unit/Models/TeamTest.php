<?php

use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
});

it('can create a team', function () {
    $team = Team::factory()->create([
        'name' => 'Test Team',
        'personal_team' => false,
        'slug' => 'test-team',
        'custom_domain' => 'test.com',
    ]);

    expect($team)
        ->name->toBe('Test Team')
        ->personal_team->toBeFalse()
        ->slug->toBe('test-team')
        ->custom_domain->toBe('test.com');
});

it('has fillable attributes', function () {
    $fillable = ['name', 'personal_team', 'slug', 'custom_domain'];

    expect($this->team->getFillable())->toBe($fillable);
});

it('casts personal_team to boolean', function () {
    $casts = $this->team->getCasts();

    expect($casts['personal_team'])->toBe('boolean');
});

it('has companies relationship', function () {
    $companies = Company::factory()->count(3)->create(['team_id' => $this->team->id]);

    expect($this->team->companies)->toHaveCount(3);
    expect($this->team->companies->first())->toBeInstanceOf(Company::class);
});

it('generates URL with custom domain', function () {
    $team = Team::factory()->create([
        'custom_domain' => 'example.com',
        'slug' => 'test-slug',
    ]);

    expect($team->url)->toBe('https://example.com');
});

it('generates URL with slug when no custom domain', function () {
    $team = Team::factory()->create([
        'custom_domain' => null,
        'slug' => 'test-slug',
    ]);

    expect($team->url)->toBe('https://test-slug.clarity-invoicing.com');
});

it('generates default URL when no custom domain or slug', function () {
    $team = Team::factory()->create([
        'custom_domain' => null,
        'slug' => null,
    ]);

    expect($team->url)->toBe("https://clarity-invoicing.com/teams/{$team->id}");
});

it('dispatches team created event', function () {
    Event::fake();

    Team::factory()->create();

    Event::assertDispatched(\Laravel\Jetstream\Events\TeamCreated::class);
});

it('dispatches team updated event', function () {
    Event::fake();

    $team = Team::factory()->create();
    $team->update(['name' => 'Updated Name']);

    Event::assertDispatched(\Laravel\Jetstream\Events\TeamUpdated::class);
});

it('dispatches team deleted event', function () {
    Event::fake();

    $team = Team::factory()->create();
    $team->delete();

    Event::assertDispatched(\Laravel\Jetstream\Events\TeamDeleted::class);
});

it('can be a personal team', function () {
    $team = Team::factory()->create(['personal_team' => true]);

    expect($team->personal_team)->toBeTrue();
});

it('can have a slug', function () {
    $team = Team::factory()->create(['slug' => 'my-team-slug']);

    expect($team->slug)->toBe('my-team-slug');
});

it('can have a custom domain', function () {
    $team = Team::factory()->create(['custom_domain' => 'mycompany.com']);

    expect($team->custom_domain)->toBe('mycompany.com');
});

it('handles null values for optional fields', function () {
    $team = Team::factory()->create([
        'slug' => null,
        'custom_domain' => null,
    ]);

    expect($team->slug)->toBeNull();
    expect($team->custom_domain)->toBeNull();
});

it('uses HasFactory trait', function () {
    $traits = class_uses(Team::class);

    expect($traits)->toHaveKey('Illuminate\Database\Eloquent\Factories\HasFactory');
});

it('extends JetstreamTeam', function () {
    expect($this->team)->toBeInstanceOf(\Laravel\Jetstream\Team::class);
});
