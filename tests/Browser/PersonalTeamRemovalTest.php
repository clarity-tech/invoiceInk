<?php

use App\Models\Organization;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Verification: dropping personal_team + forced setup + nav switcher
|--------------------------------------------------------------------------
|
| End-to-end browser verification of the architectural change that removed
| the personal_team distinction. Every signup now forces setup, the
| /organizations route always shows the list, and a multi-org user gets a
| working switcher in the navbar.
|
*/

it('forces a newly registered user through the setup wizard', function () {
    $user = User::factory()->create([
        'email' => 'force-setup@example.test',
    ]);
    $org = Organization::create([
        'user_id' => $user->id,
        'name' => "Test's Organization",
        'currency' => 'INR',
        'setup_completed_at' => null,
    ]);
    $user->forceFill(['current_team_id' => $org->id])->save();

    $this->actingAs($user);

    $this->visit('/dashboard')
        ->waitForEvent('networkidle')
        ->assertPathIs('/organization/setup');
});

it('always shows the organizations list (no single-org redirect)', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'single-org@example.test',
    ]);
    $user->currentTeam->update([
        'company_name' => 'Solo Inc',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($user);

    $this->visit('/organizations')
        ->waitForEvent('networkidle')
        ->assertPathIs('/organizations')
        ->assertSee('Solo Inc');
});

it('shows the org switcher dropdown for a multi-org user', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'multi-org@example.test',
    ]);
    // The switcher renders the org `name` (not `company_name`), so seed and
    // assert on `name` to match what the menu actually displays.
    $user->currentTeam->update([
        'name' => 'Primary Co',
        'setup_completed_at' => now(),
    ]);

    Organization::factory()->completedSetup()->create([
        'user_id' => $user->id,
        'name' => 'Secondary Co',
    ]);

    $this->actingAs($user);

    $page = $this->visit('/dashboard')->waitForEvent('networkidle');

    // Organization switching moved into the user menu (#57) — it cost ~167px
    // of nav width, which is what kept the full nav off tablet widths. So the
    // current org is no longer named in the bar; it is one tap away instead.
    $page->assertDontSee('Primary Co')
        ->click('[data-user-dropdown] > button')
        ->assertSee('Primary Co')
        ->assertSee('Secondary Co')
        ->assertSee('Manage organizations');
});
