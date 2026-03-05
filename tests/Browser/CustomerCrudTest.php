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
        ->click('button.text-red-600:has-text("Delete")')
        ->waitForText('Delete Customer')
        ->assertSee('Are you sure you want to delete this customer');
});

it('cannot delete a customer with invoices', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'cust-nodelete@example.test',
    ]);

    $organization = createOrganizationWithLocation([], [], $user);
    $customer = createCustomerWithLocation(
        ['name' => 'Customer With Invoice'],
        [],
        $organization
    );
    createInvoiceWithItems([], null, $organization, $customer);

    $this->actingAs($user);

    $page = $this->visit('/customers');

    $page->assertSee('Customer With Invoice')
        ->click('button.text-red-600:has-text("Delete")')
        ->waitForText('Delete Customer')
        ->click('button.bg-red-600')
        ->waitForText('Customer With Invoice')
        ->assertSee('Customer With Invoice');
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
    createCustomerWithLocation(
        ['name' => 'Customer For Location'],
        [],
        $organization
    );

    $this->actingAs($user);

    $page = $this->visit('/customers');

    $page->assertSee('Customer For Location')
        ->click('button:has-text("+ Location")')
        ->waitForText('Add Location')
        ->assertSee('Location Name')
        ->assertSee('Address Line 1');
});
