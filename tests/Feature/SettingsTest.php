<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows whether AI features are on and whether AI is set up', function (?string $key, bool $configured) {
    config(['ai.providers.anthropic.key' => $key]);
    $organization = createOrganizationWithLocation(['ai_drafting_enabled' => true]);

    $this->actingAs($organization->owner)->get('/settings')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Index')
            ->where('ai', ['enabled' => true, 'configured' => $configured]));
})->with([
    'with a key' => ['test-key', true],
    'without a key' => [null, false],
]);

it('lets a manager turn AI features on and off for the current organization only', function () {
    $organization = createOrganizationWithLocation();
    $admin = User::factory()->create();
    $organization->users()->attach($admin, ['role' => 'admin']);
    $admin->switchTeam($organization);
    $otherOrganization = createOrganizationWithLocation([], [], createUserWithTeam());
    $otherOrganization->users()->attach($admin, ['role' => 'owner']);

    $this->actingAs($admin)->from('/settings')->put('/settings/ai', ['enabled' => true])
        ->assertRedirect('/settings')
        ->assertSessionHas('success', 'AI features turned on.');
    expect($organization->refresh()->ai_drafting_enabled)->toBeTrue()
        ->and($otherOrganization->refresh()->ai_drafting_enabled)->toBeFalse();

    $this->actingAs($admin)->put('/settings/ai', ['enabled' => false])
        ->assertSessionHas('success', 'AI features turned off.');
    expect($organization->refresh()->ai_drafting_enabled)->toBeFalse();
});

it('needs a yes or no', function () {
    $organization = createOrganizationWithLocation();

    $this->actingAs($organization->owner)->put('/settings/ai', ['enabled' => 'maybe'])
        ->assertSessionHasErrors('enabled');
    expect($organization->refresh()->ai_drafting_enabled)->toBeFalse();
});
