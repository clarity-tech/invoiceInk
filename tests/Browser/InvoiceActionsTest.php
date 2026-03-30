<?php

use App\Models\User;

it('duplicates an invoice from the list', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-duplicate@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createInvoiceWithItems(
        ['invoice_number' => 'INV-DUP-001'],
        null,
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit('/invoices');

    $page->assertSee('INV-DUP-001')
        ->click('button:has-text("Duplicate")')
        ->waitForText('Edit Invoice')
        ->assertSee('Edit Invoice');
});

it('shows action buttons on invoice edit page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-actions@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems(
        ['invoice_number' => 'INV-ACTIONS-001'],
        null,
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit("/invoices/{$invoice->id}/edit");

    $page->assertPathIs("/invoices/{$invoice->id}/edit")
        ->assertNoJavascriptErrors()
        ->assertSee('Send Email')
        ->assertSee('View Public')
        ->assertSee('Download PDF');
});
