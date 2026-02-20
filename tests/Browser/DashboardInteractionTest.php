<?php

use App\Models\User;

it('shows quick action links on dashboard', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'dash-quick-actions@example.test',
    ]);

    createOrganizationWithLocation(['company_name' => 'Quick Actions Corp'], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertSee('New Invoice')
        ->assertSee('New Estimate');
});

it('new invoice link navigates to invoice create page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'dash-new-invoice@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/dashboard');

    $page->click('a[href="/invoices/create"]')
        ->assertPathIs('/invoices/create');
});

it('new estimate link navigates to estimate create page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'dash-new-estimate@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/dashboard');

    $page->click('a[href="/estimates/create"]')
        ->assertPathIs('/estimates/create');
});

it('displays the organization name on the dashboard', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'dash-org-name@example.test',
    ]);

    createOrganizationWithLocation(['company_name' => 'Acme Dashboard Ltd'], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/dashboard');

    $page->assertSee('Acme Dashboard Ltd');
});

it('shows zero state without data and no javascript errors', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'dash-zero-state@example.test',
    ]);

    createOrganizationWithLocation(['company_name' => 'Empty State Org'], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavascriptErrors();
});
