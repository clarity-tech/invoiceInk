<?php

use App\Models\User;

it('creates a customer with all fields', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-create@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/customers');

    $page->click('button:has-text("Add Customer") >> nth=0')
        ->waitForText('Create Customer')
        ->fill('#customer-name', 'New Test Customer')
        ->fill('#customer-phone', '+91-9876543210')
        ->select('#customer-currency', 'USD')
        ->fill('[placeholder="Contact name"]', 'John Doe')
        ->fill('[placeholder="Email *"]', 'john@newcustomer.test')
        ->click('button:has-text("Create Customer")')
        ->waitForText('New Test Customer')
        ->assertSee('New Test Customer');
});

it('lands on the detail page after creating, so the promised location can be added', function () {
    // Regression: creating a customer flashed "now add at least one location"
    // but returned to the listing, which has no way to add one -- leaving the
    // new customer reading "0 locations" with nothing to click.
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-create-location@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/customers');

    $page->click('button:has-text("Add Customer") >> nth=0')
        ->waitForText('Create Customer')
        ->fill('#customer-name', 'Location Prompt Corp')
        ->fill('[placeholder="Contact name"]', 'Dana Ops')
        ->fill('[placeholder="Email *"]', 'dana@locationprompt.test')
        ->click('button:has-text("Create Customer")')
        ->waitForText('Locations (0)')
        ->assertSee('Now add at least one location')
        // The modal opens by itself on arrival -- no click needed.
        ->waitForText('Add Location')
        ->assertSee('Location Name')
        ->assertSee('Address Line 1');
});

it('creates a customer with multiple contacts', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-multi-contact@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/customers');

    $page->click('button:has-text("Add Customer") >> nth=0')
        ->waitForText('Create Customer')
        ->fill('#customer-name', 'Multi Contact Corp')
        ->fill('[placeholder="Contact name"]', 'Alice Smith')
        ->fill('[placeholder="Email *"]', 'alice@multicontact.test')
        ->click('button:has-text("+ Add Contact")')
        ->fill('[placeholder="Contact name"] >> nth=1', 'Bob Jones')
        ->fill('[placeholder="Email *"] >> nth=1', 'bob@multicontact.test')
        ->click('button:has-text("Create Customer")')
        ->waitForText('Multi Contact Corp')
        ->assertSee('Multi Contact Corp');
});

it('opens edit form for a customer', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-edit@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createCustomerWithLocation(
        ['name' => 'Original Customer Name'],
        [],
        $organization
    );

    $this->actingAs($user);

    $page = $this->visit('/customers');

    $page->assertSee('Original Customer Name')
        ->click('button:has-text("Edit")')
        ->waitForText('Edit Customer')
        ->assertSee('Edit Customer')
        ->assertSee('Update Customer');
});

it('shows delete confirmation for a customer', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-delete@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    createCustomerWithLocation(
        ['name' => 'Customer To Delete'],
        [],
        $organization
    );

    $this->actingAs($user);

    $page = $this->visit('/customers');

    $page->assertSee('Customer To Delete')
        ->click('button.text-destructive:has-text("Delete")')
        ->waitForText('Delete Customer')
        ->assertSee('Delete this customer');
});

it('shows validation errors for blank name and invalid email', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-val@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user);

    $page = $this->visit('/customers');

    // Blank name
    $page->click('button:has-text("Add Customer") >> nth=0')
        ->waitForText('Create Customer')
        ->fill('#customer-name', '')
        ->fill('[placeholder="Email *"]', 'valid@customer.test')
        ->click('button:has-text("Create Customer")')
        ->waitForText('name');
});

it('opens add location modal for a customer', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-location@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $customer = createCustomerWithLocation(
        ['name' => 'Customer For Location'],
        [],
        $organization
    );

    $this->actingAs($user);

    // Location management lives on the customer detail page, reached from the
    // listing by clicking the customer name.
    $this->visit('/customers')
        ->assertSee('Customer For Location')
        ->click('a:has-text("Customer For Location")')
        ->waitForText('Locations (1)')
        ->assertPathIs("/customers/{$customer->id}")
        ->click('button:has-text("+ Add location")')
        ->waitForText('Add Location')
        ->assertSee('Location Name')
        ->assertSee('Address Line 1');
});
