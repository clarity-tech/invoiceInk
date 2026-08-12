<?php

use App\Models\Organization;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| RBAC button visibility (frontend)
|--------------------------------------------------------------------------
|
| Complements RbacRouteEnforcementTest (which guards the HTTP layer): these
| assert that mutation controls are not even rendered for roles that lack
| the permission. Editors lack "delete" (so no Delete / Delete Forever) and
| viewers lack "update" + "delete" (so no Edit / Set Primary / Restore /
| Delete Forever). Covers customer-location sub-resources and the trash pages.
|
*/

if (! function_exists('rbacBrowserMember')) {
    function rbacBrowserMember(Organization $org, string $role): User
    {
        $member = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $org->users()->attach($member, ['role' => $role]);
        $member->forceFill(['current_team_id' => $org->id])->save();

        return $member->fresh();
    }
}

/** The detail page of the organization's only customer, where locations live. */
function customerDetailPath(Organization $org): string
{
    return '/customers/'.$org->customers()->firstOrFail()->id;
}

/** Create a customer that owns a non-primary "Branch Office" location too. */
function customerWithTwoLocations(Organization $org): void
{
    $customer = createCustomerWithLocation(['name' => 'Test Customer'], [], $org);
    $customer->locations()->create([
        'name' => 'Branch Office',
        'address_line_1' => '1 Test St',
        'city' => 'Testville',
        'state' => 'TS',
        'country' => 'IN',
        'postal_code' => '000000',
    ]);
}

/*
|--------------------------------------------------------------------------
| Customer location sub-resource buttons
|--------------------------------------------------------------------------
*/

it('hides location delete buttons from an editor', function () {
    $org = createOrganizationWithLocation([], [], createUserWithTeam());
    customerWithTwoLocations($org);

    $this->actingAs(rbacBrowserMember($org, 'editor'));

    $this->visit(customerDetailPath($org))
        ->waitForText('Branch Office')
        ->assertSee('Set Primary') // editor keeps update-level controls
        ->assertSee('Edit')
        ->assertDontSee('Delete'); // delete is hidden everywhere on the page
});

it('hides every location action from a viewer', function () {
    $org = createOrganizationWithLocation([], [], createUserWithTeam());
    customerWithTwoLocations($org);

    $this->actingAs(rbacBrowserMember($org, 'viewer'));

    $this->visit(customerDetailPath($org))
        ->waitForText('Branch Office')
        ->assertDontSee('Set Primary')
        ->assertDontSee('Edit')
        ->assertDontSee('Delete');
});

it('shows location delete buttons to an admin', function () {
    // Positive control: the buttons exist and are gated, not removed outright.
    $org = createOrganizationWithLocation([], [], createUserWithTeam());
    customerWithTwoLocations($org);

    $this->actingAs(rbacBrowserMember($org, 'admin'));

    $this->visit(customerDetailPath($org))
        ->waitForText('Branch Office')
        ->assertSee('Set Primary')
        ->assertSee('Delete');
});

/*
|--------------------------------------------------------------------------
| Customer trash page
|--------------------------------------------------------------------------
*/

it('lets an editor restore but not permanently delete a trashed customer', function () {
    $org = createOrganizationWithLocation([], [], createUserWithTeam());
    $customer = createCustomerWithLocation(['name' => 'Trashed Customer'], [], $org);
    $customer->delete();

    $this->actingAs(rbacBrowserMember($org, 'editor'));

    $this->visit('/customers/trash')
        ->assertSee('Trashed Customer')
        ->assertSee('Restore')
        ->assertDontSee('Delete Forever');
});

it('shows a viewer no actions on the customer trash page', function () {
    $org = createOrganizationWithLocation([], [], createUserWithTeam());
    $customer = createCustomerWithLocation(['name' => 'Trashed Customer'], [], $org);
    $customer->delete();

    $this->actingAs(rbacBrowserMember($org, 'viewer'));

    $this->visit('/customers/trash')
        ->assertSee('Trashed Customer')
        ->assertDontSee('Restore')
        ->assertDontSee('Delete Forever');
});

/*
|--------------------------------------------------------------------------
| Invoice trash page
|--------------------------------------------------------------------------
*/

it('lets an editor restore but not permanently delete a trashed invoice', function () {
    $owner = createUserWithTeam();
    $org = createOrganizationWithLocation([], [], $owner);
    $customer = createCustomerWithLocation(['name' => 'Trash Invoice Customer'], [], $org);
    $invoice = createInvoiceWithItems([], null, $org, $customer);
    $invoice->delete();

    $this->actingAs(rbacBrowserMember($org, 'editor'));

    $this->visit('/invoices/trash')
        ->assertSee('Trash Invoice Customer')
        ->assertSee('Restore')
        ->assertDontSee('Delete Forever');
});

it('shows a viewer no actions on the invoice trash page', function () {
    $owner = createUserWithTeam();
    $org = createOrganizationWithLocation([], [], $owner);
    $customer = createCustomerWithLocation(['name' => 'Trash Invoice Customer'], [], $org);
    $invoice = createInvoiceWithItems([], null, $org, $customer);
    $invoice->delete();

    $this->actingAs(rbacBrowserMember($org, 'viewer'));

    $this->visit('/invoices/trash')
        ->assertSee('Trash Invoice Customer')
        ->assertDontSee('Restore')
        ->assertDontSee('Delete Forever');
});
