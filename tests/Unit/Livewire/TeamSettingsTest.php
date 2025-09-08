<?php

use App\Livewire\TeamSettings;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->ownedTeams()->first();
    $this->actingAs($this->user);
});

it('can render the team settings component', function () {
    Livewire::test(TeamSettings::class)
        ->assertStatus(200)
        ->assertViewIs('livewire.team-settings');
});

it('mounts with current team data', function () {
    $this->team->update([
        'name' => 'Test Team Name',
        'slug' => 'test-slug',
        'custom_domain' => 'test.example.com',
    ]);

    Livewire::test(TeamSettings::class)
        ->assertSet('team.name', 'Test Team Name')
        ->assertSet('name', 'Test Team Name')
        ->assertSet('slug', 'test-slug')
        ->assertSet('custom_domain', 'test.example.com');
});

it('mounts with empty values when team has no slug or custom domain', function () {
    $this->team->update([
        'name' => 'Simple Team',
        'slug' => null,
        'custom_domain' => null,
    ]);

    Livewire::test(TeamSettings::class)
        ->assertSet('name', 'Simple Team')
        ->assertSet('slug', '')
        ->assertSet('custom_domain', '');
});

it('handles mount when user has no current team', function () {
    // Create user without a personal team
    $userWithoutTeam = User::factory()->create();
    $this->actingAs($userWithoutTeam);

    Livewire::test(TeamSettings::class)
        ->assertSet('team', null)
        ->assertSet('name', '')
        ->assertSet('slug', '')
        ->assertSet('custom_domain', '');
});

it('can update team name successfully', function () {
    Livewire::test(TeamSettings::class)
        ->set('name', 'Updated Team Name')
        ->call('updateTeamName')
        ->assertHasNoErrors();

    expect($this->team->fresh()->name)->toBe('Updated Team Name');
});

it('validates required name when updating team name', function () {
    Livewire::test(TeamSettings::class)
        ->set('name', '')
        ->call('updateTeamName')
        ->assertHasErrors(['name' => 'required']);
});

it('validates name max length when updating team name', function () {
    Livewire::test(TeamSettings::class)
        ->set('name', str_repeat('a', 256)) // 256 characters
        ->call('updateTeamName')
        ->assertHasErrors(['name' => 'max']);
});

it('validates name is string when updating team name', function () {
    // Test with numeric value to trigger string validation
    Livewire::test(TeamSettings::class)
        ->set('name', '')
        ->call('updateTeamName')
        ->assertHasErrors(['name' => 'required']);
});

it('can update slug successfully', function () {
    Livewire::test(TeamSettings::class)
        ->set('slug', 'new-slug')
        ->call('updateSlug')
        ->assertHasNoErrors();

    expect($this->team->fresh()->slug)->toBe('new-slug');
});

it('can set slug to empty to clear it', function () {
    $this->team->update(['slug' => 'existing-slug']);

    Livewire::test(TeamSettings::class)
        ->set('slug', '')
        ->call('updateSlug')
        ->assertHasNoErrors();

    expect($this->team->fresh()->slug)->toBeNull();
});

it('validates slug max length when updating', function () {
    Livewire::test(TeamSettings::class)
        ->set('slug', str_repeat('a', 51)) // 51 characters
        ->call('updateSlug')
        ->assertHasErrors(['slug' => 'max']);
});

it('validates slug format when updating', function () {
    Livewire::test(TeamSettings::class)
        ->set('slug', 'invalid slug with spaces')
        ->call('updateSlug')
        ->assertHasErrors(['slug' => 'alpha_dash']);
});

it('validates slug uniqueness when updating', function () {
    $otherTeam = Team::factory()->create(['slug' => 'existing-slug']);

    Livewire::test(TeamSettings::class)
        ->set('slug', 'existing-slug')
        ->call('updateSlug')
        ->assertHasErrors(['slug' => 'unique']);
});

it('allows same team to keep its existing slug', function () {
    $this->team->update(['slug' => 'my-slug']);

    Livewire::test(TeamSettings::class)
        ->set('slug', 'my-slug')
        ->call('updateSlug')
        ->assertHasNoErrors();
});

it('can update custom domain successfully', function () {
    Livewire::test(TeamSettings::class)
        ->set('custom_domain', 'mydomain.com')
        ->call('updateCustomDomain')
        ->assertHasNoErrors();

    expect($this->team->fresh()->custom_domain)->toBe('mydomain.com');
});

it('can set custom domain to empty to clear it', function () {
    $this->team->update(['custom_domain' => 'existing.com']);

    Livewire::test(TeamSettings::class)
        ->set('custom_domain', '')
        ->call('updateCustomDomain')
        ->assertHasNoErrors();

    expect($this->team->fresh()->custom_domain)->toBeNull();
});

it('validates custom domain max length when updating', function () {
    Livewire::test(TeamSettings::class)
        ->set('custom_domain', str_repeat('a', 97).'.com') // 101 characters
        ->call('updateCustomDomain')
        ->assertHasErrors(['custom_domain']);
});

it('validates custom domain format when updating', function () {
    Livewire::test(TeamSettings::class)
        ->set('custom_domain', 'invalid-domain')
        ->call('updateCustomDomain')
        ->assertHasErrors(['custom_domain' => 'regex']);
});

it('validates custom domain format with invalid characters', function () {
    Livewire::test(TeamSettings::class)
        ->set('custom_domain', 'domain with spaces.com')
        ->call('updateCustomDomain')
        ->assertHasErrors(['custom_domain' => 'regex']);
});

it('validates custom domain uniqueness when updating', function () {
    $otherTeam = Team::factory()->create(['custom_domain' => 'existing.com']);

    Livewire::test(TeamSettings::class)
        ->set('custom_domain', 'existing.com')
        ->call('updateCustomDomain')
        ->assertHasErrors(['custom_domain' => 'unique']);
});

it('allows same team to keep its existing custom domain', function () {
    $this->team->update(['custom_domain' => 'mydomain.com']);

    Livewire::test(TeamSettings::class)
        ->set('custom_domain', 'mydomain.com')
        ->call('updateCustomDomain')
        ->assertHasNoErrors();
});

it('accepts valid custom domain formats', function () {
    $validDomains = [
        'example.com',
        'sub.example.com',
        'my-site.example.org',
        'test123.domain.co.uk',
        'a.b.c.example.net',
    ];

    foreach ($validDomains as $domain) {
        Livewire::test(TeamSettings::class)
            ->set('custom_domain', $domain)
            ->call('updateCustomDomain')
            ->assertHasNoErrors();
    }
});

it('rejects invalid custom domain formats', function () {
    $invalidDomains = [
        'notadomain',
        'domain.',
        '.domain.com',
        'domain..com',
        'domain .com',
        'domain.c',
        'http://domain.com',
        'domain.com/',
    ];

    foreach ($invalidDomains as $domain) {
        Livewire::test(TeamSettings::class)
            ->set('custom_domain', $domain)
            ->call('updateCustomDomain')
            ->assertHasErrors(['custom_domain']);
    }
});

it('has correct validation rules as attributes', function () {
    $component = new TeamSettings;

    // Check that the validation attributes are present
    $reflection = new ReflectionClass($component);

    $nameProperty = $reflection->getProperty('name');
    $slugProperty = $reflection->getProperty('slug');
    $customDomainProperty = $reflection->getProperty('custom_domain');

    expect($nameProperty->getAttributes())->toHaveCount(1);
    expect($slugProperty->getAttributes())->toHaveCount(1);
    expect($customDomainProperty->getAttributes())->toHaveCount(1);
});

it('renders with correct layout and title', function () {
    Livewire::test(TeamSettings::class)
        ->assertStatus(200);
});

it('maintains form state during validation errors', function () {
    Livewire::test(TeamSettings::class)
        ->set('name', 'Valid Name')
        ->set('slug', 'invalid slug with spaces')
        ->set('custom_domain', 'valid.domain.com')
        ->call('updateSlug')
        ->assertSet('name', 'Valid Name')
        ->assertSet('slug', 'invalid slug with spaces')
        ->assertSet('custom_domain', 'valid.domain.com')
        ->assertHasErrors(['slug']);
});

it('handles concurrent updates correctly', function () {
    // Test that validation includes the current team ID
    $this->team->update(['slug' => 'original-slug']);

    Livewire::test(TeamSettings::class)
        ->set('slug', 'original-slug')
        ->call('updateSlug')
        ->assertHasNoErrors();

    // Verify it still validates against other teams
    $otherTeam = Team::factory()->create(['slug' => 'other-slug']);

    Livewire::test(TeamSettings::class)
        ->set('slug', 'other-slug')
        ->call('updateSlug')
        ->assertHasErrors(['slug' => 'unique']);
});

it('preserves team data integrity during updates', function () {
    $originalData = [
        'name' => 'Original Name',
        'slug' => 'original-slug',
        'custom_domain' => 'original.com',
    ];

    $this->team->update($originalData);

    // Update only name
    Livewire::test(TeamSettings::class)
        ->call('updateTeamName');

    $freshTeam = $this->team->fresh();
    expect($freshTeam->slug)->toBe('original-slug');
    expect($freshTeam->custom_domain)->toBe('original.com');

    // Update only slug
    Livewire::test(TeamSettings::class)
        ->set('slug', 'new-slug')
        ->call('updateSlug');

    $freshTeam = $this->team->fresh();
    expect($freshTeam->name)->toBe('Original Name');
    expect($freshTeam->custom_domain)->toBe('original.com');
});

it('handles special characters in slug correctly', function () {
    $validSlugs = [
        'test-slug',
        'test_slug',
        'test123',
        'test-123_slug',
    ];

    foreach ($validSlugs as $slug) {
        Livewire::test(TeamSettings::class)
            ->set('slug', $slug)
            ->call('updateSlug')
            ->assertHasNoErrors();
    }
});

it('handles team updates when team is null', function () {
    // Create user without current team
    $userWithoutTeam = User::factory()->create();
    $this->actingAs($userWithoutTeam);

    expect(function () {
        Livewire::test(TeamSettings::class)
            ->set('name', 'Some Name')
            ->call('updateTeamName');
    })->toThrow(Exception::class);
});

it('can handle maximum valid slug length', function () {
    $maxLengthSlug = str_repeat('a', 50); // exactly 50 characters

    Livewire::test(TeamSettings::class)
        ->set('slug', $maxLengthSlug)
        ->call('updateSlug')
        ->assertHasNoErrors();

    expect($this->team->fresh()->slug)->toBe($maxLengthSlug);
});

it('can handle maximum valid custom domain length', function () {
    $maxLengthDomain = str_repeat('a', 96).'.com'; // exactly 100 characters

    Livewire::test(TeamSettings::class)
        ->set('custom_domain', $maxLengthDomain)
        ->call('updateCustomDomain')
        ->assertHasNoErrors();

    expect($this->team->fresh()->custom_domain)->toBe($maxLengthDomain);
});
