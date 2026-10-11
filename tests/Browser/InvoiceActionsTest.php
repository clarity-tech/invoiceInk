<?php

use App\Models\Invoice;
use App\Models\User;

it('duplicates an invoice into an unsaved, pre-filled create form', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-duplicate@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createInvoiceWithItems(
        ['invoice_number' => 'INV-DUP-001'],
        [['description' => 'Duplicated consulting line', 'sac_code' => '9983', 'quantity' => 2, 'unit_price' => 150000, 'tax_rate' => 1800]],
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit('/invoices');

    $page->assertSee('INV-DUP-001')
        ->click('a:has-text("Duplicate")')
        ->waitForText('Create Invoice')
        ->assertSee('Create Invoice')
        // The copied line item is carried into the form...
        ->assertValue(
            'textarea[placeholder="Item description"]',
            'Duplicated consulting line'
        );

    // ...and nothing was saved just by opening it.
    expect(Invoice::withoutGlobalScopes()->count())->toBe(1);
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
        ->assertSee('Save & Send')
        ->assertSee('View Public')
        ->assertSee('Download PDF');
});
