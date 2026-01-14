<?php

use App\Models\User;

it('loads the dashboard for authenticated users', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'dashboard@example.test',
        'password' => 'password',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'Dashboard Test Corp',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($user);

    $page = $this->visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavascriptErrors();
});

it('shows the organization name on dashboard', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'dashboard-org@example.test',
        'password' => 'password',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($user);

    $page = $this->visit('/dashboard');

    $page->assertSee($organization->displayName);
});
