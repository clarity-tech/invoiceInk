<?php

use App\Models\User;

it('loads the organization edit page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'org-edit-load@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/organization/edit');

    $page->assertPathIs('/organization/edit')
        ->assertNoJavascriptErrors()
        ->assertSee($organization->name);
});

it('opens edit form and navigates all tabs', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'org-edit-tabs@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/organization/edit');

    $page->click('button.text-blue-600:has-text("Edit")')
        ->waitForText('Edit Organization')
        ->assertSee('Edit Organization')
        ->assertSee('Name')
        ->assertSee('Save Basics')
        // Location tab
        ->click('button:has-text("Location"):not(:has-text("Save"))')
        ->waitForText('Address Line 1')
        ->assertSee('Address Line 1')
        ->assertSee('Save Location')
        // Bank Details tab
        ->click('button:has-text("Bank Details"):not(:has-text("Save"))')
        ->waitForText('Account Name')
        ->assertSee('Account Name')
        ->assertSee('Save Bank Details');
});

it('shows organization details in table', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'org-details@example.test',
    ]);

    createOrganizationWithLocation(
        ['currency' => 'INR'],
        ['city' => 'Bangalore', 'state' => 'Karnataka'],
        $user
    );

    $this->actingAs($user);

    $page = $this->visit('/organization/edit');

    $page->assertSee('INR')
        ->assertSee('Bangalore')
        ->assertNoJavascriptErrors();
});
