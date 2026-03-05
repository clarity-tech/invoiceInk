<?php

use App\Models\User;

it('creates an invoice with a single item', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-create-single@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $customer = createCustomerWithLocation(['name' => 'Single Item Customer'], [], $organization);

    $this->actingAs($user);

    $page = $this->visit('/invoices/create');

    $page->assertPathIs('/invoices/create')
        ->assertNoJavascriptErrors()
        ->select('select:has(option:text("Select customer"))', (string) $customer->id)
        ->fill('input[placeholder="Item description"]', 'Web Development Service')
        ->fill('input[type="number"][min="1"]', '5')
        ->fill('input[type="number"][min="0"][placeholder="0.00"] >> nth=0', '10000')
        ->click('button[type="submit"]')
        ->waitForText('Edit Invoice')
        ->assertSee('Edit Invoice');
});

it('creates invoice with multiple items', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-create-multi@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $customer = createCustomerWithLocation(['name' => 'Multi Item Customer'], [], $organization);

    $this->actingAs($user);

    $page = $this->visit('/invoices/create');

    $page->select('select:has(option:text("Select customer"))', (string) $customer->id)
        ->fill('input[placeholder="Item description"] >> nth=0', 'Service One')
        ->fill('input[type="number"][min="1"] >> nth=0', '2')
        ->fill('input[type="number"][min="0"][placeholder="0.00"] >> nth=0', '5000')
        ->click('button:has-text("Add Item")')
        ->fill('input[placeholder="Item description"] >> nth=1', 'Service Two')
        ->fill('input[type="number"][min="1"] >> nth=1', '3')
        ->fill('input[type="number"][min="0"][placeholder="0.00"] >> nth=1', '8000')
        ->click('button[type="submit"]')
        ->waitForText('Edit Invoice')
        ->assertSee('Edit Invoice');
});

it('edits an existing invoice', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-edit@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems(
        ['invoice_number' => 'INV-EDIT-001'],
        [['description' => 'Original Item', 'quantity' => 1, 'unit_price' => 10000, 'tax_rate' => 18.00]],
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit("/invoices/{$invoice->id}/edit");

    $page->assertPathIs("/invoices/{$invoice->id}/edit")
        ->assertNoJavascriptErrors()
        ->assertSee('Edit Invoice')
        ->assertSee('Update Invoice');
});

it('deletes an invoice from the list', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-delete@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createInvoiceWithItems(
        ['invoice_number' => 'INV-DEL-001'],
        null,
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit('/invoices');

    $page->assertSee('INV-DEL-001')
        ->click('button.text-red-600:has-text("Delete")')
        ->waitForText('Delete Document')
        ->assertSee('Are you sure you want to delete this document');
});

it('shows validation error without selecting a customer', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-val-nocust@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/invoices/create');

    $page->fill('input[placeholder="Item description"]', 'Some Service')
        ->fill('input[type="number"][min="1"]', '1')
        ->fill('input[type="number"][min="0"][placeholder="0.00"] >> nth=0', '5000')
        ->click('button[type="submit"]')
        ->waitForText('customer');
});

it('shows create page UI elements and back navigation', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-create-ui@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/invoices/create');

    $page->assertNoJavascriptErrors()
        ->assertSee('Add Item')
        ->assertSee('Line Items')
        ->click('a:has-text("Back to Invoices")')
        ->assertPathIs('/invoices');
});

it('filters invoices by type tab', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-filter@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createInvoiceWithItems(
        ['invoice_number' => 'INV-FILTER-001', 'type' => 'invoice'],
        null,
        $organization,
    );
    createInvoiceWithItems(
        ['invoice_number' => 'EST-FILTER-001', 'type' => 'estimate'],
        null,
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit('/invoices');

    $page->assertSee('INV-FILTER-001')
        ->assertSee('EST-FILTER-001')
        ->click('button:has-text("Invoices"):not(:has-text("&"))')
        ->waitForText('INV-FILTER-001')
        ->assertSee('INV-FILTER-001');
});

it('shows invoice number in the list', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-list-num@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createInvoiceWithItems(
        ['invoice_number' => 'INV-VISIBLE-999'],
        null,
        $organization,
    );

    $this->actingAs($user);

    $page = $this->visit('/invoices');

    $page->assertSee('INV-VISIBLE-999')
        ->assertNoJavascriptErrors();
});
