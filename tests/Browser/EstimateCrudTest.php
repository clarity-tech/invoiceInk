<?php

use App\Models\User;

it('creates an estimate via the estimate create page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'est-create@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $customer = createCustomerWithLocation(['name' => 'Estimate Customer'], [], $organization);

    $this->actingAs($user);

    $page = $this->visit('/estimates/create');

    $page->assertPathIs('/estimates/create')
        ->assertNoJavascriptErrors()
        ->assertSee('Create Estimate')
        ->select('select:has(option:text("Select customer"))', (string) $customer->id)
        ->fill('input[placeholder="Item description"]', 'Consulting Service')
        ->fill('input[type="number"][min="1"]', '10')
        ->fill('input[type="number"][min="0"][placeholder="0"] >> nth=0', '15000')
        ->click('button[type="submit"]')
        ->waitForText('Edit Estimate')
        ->assertSee('Edit Estimate');
});

it('shows estimate actions and badge in the list', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'est-actions@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createInvoiceWithItems(
        ['invoice_number' => 'EST-ACTIONS-001', 'type' => 'estimate'],
        null,
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit('/invoices');

    $page->assertSee('EST-ACTIONS-001')
        ->assertSee('ESTIMATE')
        ->assertSee('Convert')
        ->assertSee('Duplicate')
        ->assertNoJavascriptErrors();
});

it('shows estimates on the estimates tab', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'est-tab@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createInvoiceWithItems(
        ['invoice_number' => 'EST-TABCHECK-001', 'type' => 'estimate'],
        null,
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit('/invoices');

    $page->click('button:has-text("Estimates"):not(:has-text("&"))')
        ->waitForText('EST-TABCHECK-001')
        ->assertSee('EST-TABCHECK-001');
});
