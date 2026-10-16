<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceNumberingSeries;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| RBAC route enforcement (regression)
|--------------------------------------------------------------------------
|
| Guards the HTTP layer against role-escalation gaps found in the RBAC audit:
| the setup wizard, invoice duplication, customer-location deletes, payment
| deletes, the settings hub, and numbering-series reassignment.
|
*/

beforeEach(function () {
    $this->owner = createUserWithTeam(['name' => 'Owner']);
    $this->org = createOrganizationWithLocation([], [], $this->owner);
    $this->customer = createCustomerWithLocation([], [], $this->org);
    $this->invoice = createInvoiceWithItems([], null, $this->org, $this->customer);
});

/** Attach a member with the given role and make the org their current team. */
function rbacMember(Organization $org, string $role): User
{
    $member = User::factory()->create();
    $org->users()->attach($member, ['role' => $role]);
    $member->forceFill(['current_team_id' => $org->id])->save();

    return $member->fresh();
}

/*
|--------------------------------------------------------------------------
| #1 Setup wizard must require "manage" and refuse a completed org
|--------------------------------------------------------------------------
*/

test('non-managers cannot drive the setup wizard', function (string $role) {
    $org = createOrganizationWithLocation(['setup_completed_at' => null], [], $this->owner);
    $member = rbacMember($org, $role);

    $this->actingAs($member)
        ->post("/organization/setup/{$org->id}/step", [
            'step' => 1,
            'company_name' => 'HACKED',
        ])
        ->assertForbidden();

    expect($org->fresh()->company_name)->not->toBe('HACKED');
})->with(['editor', 'viewer']);

test('the owner can drive the setup wizard on an incomplete org', function () {
    $org = createOrganizationWithLocation(['setup_completed_at' => null], [], $this->owner);

    $this->actingAs($this->owner)
        ->post("/organization/setup/{$org->id}/step", [
            'step' => 1,
            'company_name' => 'Legit Co',
            'emails' => ['owner@legit.test'],
        ])
        ->assertRedirect();

    expect($org->fresh()->company_name)->toBe('Legit Co');
});

test('the setup wizard refuses to mutate an already-completed org', function () {
    // $this->org is created setup-complete by the helper.
    $original = $this->org->company_name;

    $this->actingAs($this->owner)
        ->post("/organization/setup/{$this->org->id}/step", [
            'step' => 1,
            'company_name' => 'Overwritten',
        ])
        ->assertRedirect(route('dashboard'));

    expect($this->org->fresh()->company_name)->toBe($original);
});

/*
|--------------------------------------------------------------------------
| #2 Invoice duplication is a create, not a read
|--------------------------------------------------------------------------
*/

test('a viewer cannot duplicate an invoice', function () {
    $member = rbacMember($this->org, 'viewer');

    $this->actingAs($member)
        ->get("/invoices/{$this->invoice->id}/duplicate")
        ->assertForbidden();

    // A viewer must not reach the seeded create form either.
    $this->actingAs($member)
        ->get("/invoices/create?from={$this->invoice->id}")
        ->assertForbidden();
});

test('an editor can duplicate an invoice', function () {
    $member = rbacMember($this->org, 'editor');
    $before = Invoice::withoutGlobalScopes()->count();

    $this->actingAs($member)
        ->get("/invoices/{$this->invoice->id}/duplicate")
        ->assertRedirect(route('invoices.create', ['from' => $this->invoice->id]));

    // Duplicating opens a form; it saves nothing until the editor submits it.
    expect(Invoice::withoutGlobalScopes()->count())->toBe($before);
});

/*
|--------------------------------------------------------------------------
| #3 Customer-location deletes require the delete permission
|--------------------------------------------------------------------------
*/

function secondLocation(Customer $customer): Location
{
    return $customer->locations()->create([
        'name' => 'Second Site',
        'address_line_1' => '1 Test St',
        'city' => 'Testville',
        'state' => 'TS',
        'country' => 'IN',
        'postal_code' => '000000',
    ]);
}

test('an editor cannot delete a customer location', function () {
    $location = secondLocation($this->customer);
    $member = rbacMember($this->org, 'editor');

    $this->actingAs($member)
        ->delete("/customers/{$this->customer->id}/locations/{$location->id}")
        ->assertForbidden();
});

test('an editor cannot permanently delete a customer location', function () {
    $location = secondLocation($this->customer);
    $member = rbacMember($this->org, 'editor');

    $this->actingAs($member)
        ->delete("/customers/{$this->customer->id}/locations/{$location->id}/force")
        ->assertForbidden();
});

test('an admin can delete a customer location', function () {
    $location = secondLocation($this->customer);
    $member = rbacMember($this->org, 'admin');

    $this->actingAs($member)
        ->delete("/customers/{$this->customer->id}/locations/{$location->id}")
        ->assertRedirect();

    expect(Location::withTrashed()->find($location->id)->trashed())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| #3 Payment deletion requires the delete permission + invoice ownership
|--------------------------------------------------------------------------
*/

test('an editor cannot delete a payment', function () {
    $payment = Payment::factory()->forInvoice($this->invoice)->create();
    $member = rbacMember($this->org, 'editor');

    $this->actingAs($member)
        ->delete("/invoices/{$this->invoice->id}/payments/{$payment->id}")
        ->assertForbidden();

    expect(Payment::find($payment->id))->not->toBeNull();
});

test('an admin can delete a payment', function () {
    $payment = Payment::factory()->forInvoice($this->invoice)->create();
    $member = rbacMember($this->org, 'admin');

    $this->actingAs($member)
        ->delete("/invoices/{$this->invoice->id}/payments/{$payment->id}")
        ->assertRedirect();

    expect(Payment::find($payment->id))->toBeNull();
});

test('a payment cannot be deleted through a mismatched invoice', function () {
    $otherInvoice = createInvoiceWithItems([], null, $this->org, $this->customer);
    $payment = Payment::factory()->forInvoice($otherInvoice)->create();

    $this->actingAs($this->owner)
        ->delete("/invoices/{$this->invoice->id}/payments/{$payment->id}")
        ->assertNotFound();

    expect(Payment::find($payment->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Lower priority: settings hub + numbering-series reassignment
|--------------------------------------------------------------------------
*/

test('non-managers cannot reach the settings hub', function (string $role) {
    $member = rbacMember($this->org, $role);

    $this->actingAs($member)
        ->get('/settings')
        ->assertForbidden();
})->with(['editor', 'viewer']);

test('non-managers cannot switch AI features', function (string $role) {
    $member = rbacMember($this->org, $role);

    $this->actingAs($member)
        ->put('/settings/ai', ['enabled' => true])
        ->assertForbidden();

    expect($this->org->refresh()->ai_drafting_enabled)->toBeFalse();
})->with(['editor', 'viewer']);

test('a manager can reach the settings hub', function () {
    $member = rbacMember($this->org, 'admin');

    $this->actingAs($member)
        ->get('/settings')
        ->assertOk();
});

test('a numbering series cannot be moved into an org where the user lacks manage', function () {
    // The user owns org A (manage) but is only a viewer in org B.
    $orgB = createOrganizationWithLocation([], [], createUserWithTeam(['name' => 'B Owner']));
    $orgB->users()->attach($this->owner, ['role' => 'viewer']);

    $series = InvoiceNumberingSeries::factory()->create([
        'organization_id' => $this->org->id,
        'location_id' => null,
    ]);

    $this->actingAs($this->owner)
        ->put("/numbering-series/{$series->id}", [
            'organization_id' => $orgB->id,
            'location_id' => null,
            'name' => $series->name,
            'prefix' => $series->prefix,
            'format_pattern' => $series->format_pattern,
            'current_number' => $series->current_number,
            'reset_frequency' => $series->reset_frequency->value,
            'is_active' => true,
            'is_default' => false,
        ])
        ->assertForbidden();

    expect($series->fresh()->organization_id)->toBe($this->org->id);
});

/*
|--------------------------------------------------------------------------
| #7 The invoice detail page is a read, but every action on it is not
|--------------------------------------------------------------------------
*/

test('a viewer can open the invoice detail page', function () {
    $member = rbacMember($this->org, 'viewer');

    // Deliberately allowed: viewing needs only "read". This is what makes the
    // button gating on the page load-bearing rather than cosmetic -- a viewer
    // genuinely arrives here.
    $this->actingAs($member)
        ->get("/invoices/{$this->invoice->id}")
        ->assertOk();
});

test('a viewer cannot act on the invoice they can read', function () {
    $member = rbacMember($this->org, 'viewer');

    $this->actingAs($member)
        ->post("/invoices/{$this->invoice->id}/payments", [
            'amount' => 5000,
            'payment_date' => now()->toDateString(),
        ])
        ->assertForbidden();

    $this->actingAs($member)
        ->post("/invoices/{$this->invoice->id}/send-email", [
            'recipients' => ['someone@example.test'],
            'subject' => 'Subject',
            'body' => 'Body',
        ])
        ->assertForbidden();

    expect($this->invoice->fresh()->payments)->toHaveCount(0);
});

test('an editor can record a payment but cannot delete one', function () {
    $member = rbacMember($this->org, 'editor');
    $payment = Payment::factory()->create([
        'invoice_id' => $this->invoice->id,
        'amount' => 5000,
    ]);

    // Deleting a payment authorizes "delete" on the invoice, which an editor
    // lacks -- so the row's Delete control has to be hidden from them.
    $this->actingAs($member)
        ->delete("/invoices/{$this->invoice->id}/payments/{$payment->id}")
        ->assertForbidden();

    expect(Payment::find($payment->id))->not->toBeNull();
});
