<?php

use App\Models\Customer;
use App\Models\User;

it('explains why an invoice cannot be saved for a customer with no location', function () {
    // Regression: the billing and shipping blocks are hidden when the customer
    // has no location, taking the `customer_location_id` error with them --
    // clicking Save did nothing at all, with the failure visible only in the
    // network tab.
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-no-location@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $customer = Customer::factory()->create([
        'name' => 'Addressless Customer',
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user);

    $page = $this->visit('/invoices/create');

    $page->select('select:has(option:text("Select customer"))', (string) $customer->id)
        ->assertSee('no billing address yet')
        ->fill('textarea[placeholder="Item description"]', 'Consulting')
        ->fill('input[type="number"][min="1"]', '1')
        ->fill('input[type="number"][min="0"][placeholder="0.00"] >> nth=0', '1000')
        ->click('button[type="submit"]')
        ->waitForText('could not be saved')
        ->assertSee('could not be saved');
});

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
        ->fill('textarea[placeholder="Item description"]', 'Web Development Service')
        ->fill('input[type="number"][min="1"]', '5')
        ->fill('input[type="number"][min="0"][placeholder="0.00"] >> nth=0', '10000')
        ->click('button[type="submit"]')
        ->waitForText('Edit Invoice')
        ->assertSee('Edit Invoice')
        // Said once. The form carried its own copy of the flash message on top
        // of the one the layout renders for every page, so a save reported
        // itself twice, one green box above the other.
        ->assertScript(
            'function() { return (document.body.innerText.match(/Invoice created successfully/g) || []).length; }',
            1,
        );
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
        ->fill('textarea[placeholder="Item description"] >> nth=0', 'Service One')
        ->fill('input[type="number"][min="1"] >> nth=0', '2')
        ->fill('input[type="number"][min="0"][placeholder="0.00"] >> nth=0', '5000')
        // Two of them now, one above the lines and one below; either will do,
        // but Playwright refuses a selector that matches both.
        ->click('button:has-text("Add Item") >> nth=0')
        ->fill('textarea[placeholder="Item description"] >> nth=1', 'Service Two')
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
        ->assertSee('Save & Send');
});

it('deletes an invoice from its own page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-delete@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems(
        ['invoice_number' => 'INV-DEL-001'],
        null,
        $organization,
    );

    $this->actingAs($user);

    // Reached the way a user reaches it: the number in the list is the link to
    // the document, and the document is where it can be deleted.
    $page = $this->visit('/invoices');

    $page->assertSee('INV-DEL-001')
        ->click('a:has-text("INV-DEL-001")')
        ->assertPathIs("/invoices/{$invoice->id}")
        ->click('button[aria-label="Delete invoice INV-DEL-001"]')
        // The dialog names the document and says where it goes. It used
        // to claim the delete could not be undone, which was never true of a
        // soft delete.
        ->waitForText('moves to Trash')
        ->assertSee('INV-DEL-001 moves to Trash')
        ->assertDontSee('cannot be undone');
});

it('shows validation error without selecting a customer', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-val-nocust@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/invoices/create');

    $page->fill('textarea[placeholder="Item description"]', 'Some Service')
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
        ->click('button:has-text("Cancel")')
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

/**
 * Saving on the edit page redirects back to the same page, so Inertia swaps
 * the props without remounting anything. The confirmation has to survive that
 * -- and it is now the only one on the page, the form's own copy having been
 * removed for reporting the save a second time.
 */
it('confirms every save on the edit page, not only the first', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'inv-edit-flash@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $customer = createCustomerWithLocation(['name' => 'Repeat Saver'], [], $organization);
    // Dates included: the helper leaves them null, and the form's date inputs
    // are required, so without them the browser blocks the submit and nothing
    // reaches the server at all.
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-FLASH-001',
        'issued_at' => now()->subDays(3),
        'due_at' => now()->addDays(27),
    ], null, $organization, $customer);

    $this->actingAs($user);

    $page = $this->visit("/invoices/{$invoice->id}/edit");

    $page->click('button[type="submit"]')
        ->waitForText('updated successfully')
        // Said once: the form used to carry its own copy on top of the one the
        // layout renders for every page.
        ->assertScript(
            'function() { return (document.body.innerText.match(/updated successfully/g) || []).length; }',
            1,
        )
        // It clears itself after four seconds. The second save is the one that
        // went silent when the notice was keyed to "is there a flash at all"
        // rather than to the response that carried it.
        ->wait(5)
        ->assertDontSee('updated successfully')
        ->click('button[type="submit"]')
        ->waitForText('updated successfully')
        ->assertScript(
            'function() { return (document.body.innerText.match(/updated successfully/g) || []).length; }',
            1,
        );
});
