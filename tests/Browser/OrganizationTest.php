<?php

use App\Models\User;

it('loads the organizations page for authenticated users', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'org-list@example.test',
        'password' => 'password',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'Org List Test Corp',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($user);

    $page = $this->visit("/organizations/{$organization->id}");

    $page->assertPathIs("/organizations/{$organization->id}")
        ->assertNoJavascriptErrors();
});

it('shows organization details', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'org-details@example.test',
        'password' => 'password',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'Org Details Corp',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($user);

    $page = $this->visit("/organizations/{$organization->id}");

    $page->assertSee($organization->displayName);
});
