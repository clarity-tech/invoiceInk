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

it('says so when the KPI totals were converted from another currency', function () {
    // Regression: the disclosure keyed off "more than one currency", so an
    // organization invoicing entirely in AED saw rupee totals with nothing
    // explaining where they came from.
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'dashboard-fx@example.test',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    createOrganizationWithLocation([], [], $user);
    $organization->refresh();

    $customer = createCustomerWithLocation(
        ['name' => 'Sandline Pharmacy LLC', 'currency' => 'AED'],
        [],
        $organization
    );

    createInvoiceWithItems(
        ['currency' => 'AED', 'issued_at' => now(), 'due_at' => now()->subWeek()],
        null,
        $organization,
        $customer
    );

    $this->actingAs($user);

    $this->visit('/dashboard')
        ->assertNoJavascriptErrors()
        ->assertSee('Original Currency')
        ->assertSee('converted to INR')
        ->assertSee('AED');
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
