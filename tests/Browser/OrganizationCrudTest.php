<?php

use App\Models\User;

it('loads the organization edit page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'org-edit-load@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit("/organizations/{$organization->id}/edit");

    $page->assertPathIs("/organizations/{$organization->id}/edit")
        ->assertNoJavascriptErrors()
        ->assertSee($organization->name);
});

it('navigates all tabs on edit page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'org-edit-tabs@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit("/organizations/{$organization->id}/edit");

    // Default tab is General (basics)
    $page->assertSee('General')
        ->assertSee('Location')
        ->assertSee('Bank Details')
        // Click Location tab
        ->click('button:has-text("Location")')
        ->waitForText('Address Line 1')
        ->assertSee('Address Line 1')
        // Click Bank Details tab
        ->click('button:has-text("Bank Details")')
        ->waitForText('Account Name')
        ->assertSee('Account Name');
});

it('shows organization details on show page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'org-details@example.test',
    ]);

    $organization = createOrganizationWithLocation(
        ['currency' => 'INR'],
        ['city' => 'Bangalore', 'state' => 'Karnataka'],
        $user
    );

    $this->actingAs($user);

    $page = $this->visit("/organizations/{$organization->id}");

    $page->assertSee('INR')
        ->assertSee('Bangalore')
        ->assertNoJavascriptErrors();
});
