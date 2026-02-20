<?php

use App\Models\User;

it('creates a numbering series', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'ns-create@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/numbering-series');

    $page->assertPathIs('/numbering-series')
        ->assertNoJavascriptErrors()
        ->click('text=Create New Series')
        ->select('#organization_id', $organization->id)
        ->fill('#name', 'Monthly Invoice Series')
        ->fill('#prefix', 'MINV')
        ->fill('#format_pattern', '{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}')
        ->fill('#current_number', '0')
        ->select('#reset_frequency', 'monthly')
        ->click('button:has-text("Create Series")')
        ->waitForText('Monthly Invoice Series')
        ->assertSee('Monthly Invoice Series');
});

it('opens edit form for a numbering series', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'ns-edit@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createNumberingSeries(['name' => 'Edit Me Series', 'prefix' => 'EDT', 'reset_frequency' => 'yearly'], $organization);

    $this->actingAs($user);

    $page = $this->visit('/numbering-series');

    $page->assertSee('Edit Me Series')
        ->click('button.text-brand-600:has-text("Edit")')
        ->waitForText('Edit Numbering Series')
        ->assertSee('Edit Numbering Series')
        ->assertSee('EDT');
});

it('toggles series active status', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'ns-toggle@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createNumberingSeries(['name' => 'Toggle Series', 'prefix' => 'TGL'], $organization);

    $this->actingAs($user);

    $page = $this->visit('/numbering-series');

    $page->assertSee('Toggle Series')
        ->assertSee('Active')
        ->click('button:has-text("Deactivate")')
        ->waitForText('Inactive')
        ->assertSee('Inactive');
});

it('sets series as default', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'ns-default@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createNumberingSeries(['name' => 'Make Default Series', 'prefix' => 'DEF'], $organization);

    $this->actingAs($user);

    $page = $this->visit('/numbering-series');

    $page->assertSee('Make Default Series')
        ->click('button:has-text("Set Default")')
        ->waitForText('Default')
        ->assertSee('Default');
});

it('shows delete confirmation modal for series', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'ns-delete@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createNumberingSeries(['name' => 'Delete Me Series', 'prefix' => 'DEL'], $organization);

    $this->actingAs($user);

    $page = $this->visit('/numbering-series');

    $page->assertSee('Delete Me Series')
        ->click('button.text-red-600:has-text("Delete")')
        ->waitForText('Delete Numbering Series')
        ->assertSee('Are you sure you want to delete this numbering series');
});

it('cannot delete series with invoices', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'ns-nodelete@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $series = createNumberingSeries(['name' => 'Has Invoices Series', 'prefix' => 'HAS', 'current_number' => 1], $organization);
    createInvoiceWithItems(['invoice_numbering_series_id' => $series->id], null, $organization);

    $this->actingAs($user);

    $page = $this->visit('/numbering-series');

    $page->assertSee('Has Invoices Series')
        ->click('button.text-red-600:has-text("Delete")')
        ->waitForText('Delete Numbering Series')
        ->click('button.bg-red-600')
        ->waitForText('Has Invoices Series')
        ->assertSee('Has Invoices Series');
});

it('shows validation errors on create', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'ns-validation@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/numbering-series');

    $page->click('text=Create New Series')
        ->click('button:has-text("Create Series")')
        ->waitForText('name');
});
