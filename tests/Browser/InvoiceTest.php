<?php

use App\Models\User;

it('loads the invoices page for authenticated users', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-list@example.test',
        'password' => 'password',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'Invoice List Test Corp',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($user);

    $page = $this->visit('/invoices');

    $page->assertPathIs('/invoices')
        ->assertNoJavascriptErrors();
});

it('shows invoice list when invoices exist', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-show@example.test',
        'password' => 'password',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems(
        ['invoice_number' => 'INV-BROWSER-001'],
        null,
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit('/invoices');

    $page->assertSee('INV-BROWSER-001');
});
