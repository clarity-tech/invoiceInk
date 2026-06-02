<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceNumberingSeries;
use App\Models\Organization;
use App\Models\TaxTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Role Permission Matrix
|--------------------------------------------------------------------------
|
| Verifies that the admin / editor / viewer roles are actually enforced by
| the policies. The organization OWNER is covered separately (owner bypasses
| all permission checks).
|
*/

beforeEach(function () {
    $this->owner = createUserWithTeam(['name' => 'Owner']);
    $this->org = createOrganizationWithLocation([], [], $this->owner);
    $this->customer = createCustomerWithLocation([], [], $this->org);
    $this->invoice = createInvoiceWithItems([], null, $this->org, $this->customer);
});

/**
 * Attach a fresh user to the org with the given role, make the org their
 * current team, and return the (reloaded) member.
 */
function memberWithRole(Organization $org, string $role): User
{
    $member = User::factory()->create();
    $org->users()->attach($member, ['role' => $role]);
    $member->forceFill(['current_team_id' => $org->id])->save();

    return $member->fresh();
}

test('invoice policy enforces the role matrix', function (
    string $role, bool $view, bool $create, bool $update, bool $delete, bool $send, bool $pdf
) {
    $member = memberWithRole($this->org, $role);
    $gate = Gate::forUser($member);

    expect($gate->allows('view', $this->invoice))->toBe($view);
    expect($gate->allows('create', Invoice::class))->toBe($create);
    expect($gate->allows('update', $this->invoice))->toBe($update);
    expect($gate->allows('delete', $this->invoice))->toBe($delete);
    expect($gate->allows('send', $this->invoice))->toBe($send);
    expect($gate->allows('downloadPdf', $this->invoice))->toBe($pdf);
})->with([
    ['admin', true, true, true, true, true, true],
    ['editor', true, true, true, false, true, true],
    ['viewer', true, false, false, false, false, true],
]);

test('customer policy enforces the role matrix', function (
    string $role, bool $view, bool $create, bool $update, bool $delete
) {
    $member = memberWithRole($this->org, $role);
    $gate = Gate::forUser($member);

    expect($gate->allows('view', $this->customer))->toBe($view);
    expect($gate->allows('create', Customer::class))->toBe($create);
    expect($gate->allows('update', $this->customer))->toBe($update);
    expect($gate->allows('delete', $this->customer))->toBe($delete);
})->with([
    ['admin', true, true, true, true],
    ['editor', true, true, true, false],
    ['viewer', true, false, false, false],
]);

test('settings resources require the manage permission', function (
    string $role, bool $canManage
) {
    $member = memberWithRole($this->org, $role);
    $gate = Gate::forUser($member);

    // Numbering series + tax templates are settings → manage only, but readable by all.
    expect($gate->allows('viewAny', InvoiceNumberingSeries::class))->toBeTrue();
    expect($gate->allows('create', InvoiceNumberingSeries::class))->toBe($canManage);
    expect($gate->allows('create', TaxTemplate::class))->toBe($canManage);

    // Organization settings + member management → manage only.
    expect($gate->allows('update', $this->org))->toBe($canManage);
    expect($gate->allows('addTeamMember', $this->org))->toBe($canManage);
})->with([
    ['admin', true],
    ['editor', false],
    ['viewer', false],
]);

test('only the owner can delete the organization', function () {
    expect(Gate::forUser($this->owner)->allows('delete', $this->org))->toBeTrue();

    foreach (['admin', 'editor', 'viewer'] as $role) {
        $member = memberWithRole($this->org, $role);
        expect(Gate::forUser($member)->allows('delete', $this->org))->toBeFalse();
    }
});

test('the owner bypasses every permission check', function () {
    $gate = Gate::forUser($this->owner);

    expect($gate->allows('delete', $this->invoice))->toBeTrue();
    expect($gate->allows('create', Invoice::class))->toBeTrue();
    expect($gate->allows('update', $this->org))->toBeTrue();
    expect($gate->allows('create', TaxTemplate::class))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| HTTP enforcement
|--------------------------------------------------------------------------
*/

test('an editor cannot delete a customer over HTTP', function () {
    $member = memberWithRole($this->org, 'editor');

    $this->actingAs($member)
        ->delete("/customers/{$this->customer->id}")
        ->assertForbidden();
});

test('a viewer cannot create a customer over HTTP', function () {
    $member = memberWithRole($this->org, 'viewer');

    $this->actingAs($member)
        ->post('/customers', [
            'name' => 'New Co',
            'currency' => 'INR',
            'contacts' => [['name' => 'A', 'email' => 'a@example.test']],
        ])
        ->assertForbidden();
});

test('an editor cannot delete an invoice over HTTP', function () {
    $member = memberWithRole($this->org, 'editor');

    $this->actingAs($member)
        ->delete("/invoices/{$this->invoice->id}")
        ->assertForbidden();
});

test('an admin can delete a customer over HTTP', function () {
    $member = memberWithRole($this->org, 'admin');

    $this->actingAs($member)
        ->delete("/customers/{$this->customer->id}")
        ->assertRedirect();

    expect(Customer::find($this->customer->id))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Members tab (organization settings)
|--------------------------------------------------------------------------
*/

test('an editor cannot reach organization settings', function () {
    $member = memberWithRole($this->org, 'editor');

    $this->actingAs($member)
        ->get("/organizations/{$this->org->id}/edit")
        ->assertForbidden();
});

test('the owner sees the members payload on the settings page', function () {
    $this->actingAs($this->owner)
        ->get("/organizations/{$this->org->id}/edit?tab=members")
        ->assertInertia(fn ($page) => $page
            ->component('Organizations/Edit')
            ->has('availableRoles')
            ->has('memberPermissions')
            ->where('memberPermissions.canAddTeamMembers', true)
        );
});
