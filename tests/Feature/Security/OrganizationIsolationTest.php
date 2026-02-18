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
| Organization Scope Isolation Tests
|--------------------------------------------------------------------------
|
| Verify that OrganizationScope correctly filters data to the current
| team context. These tests create two isolated organizations and verify
| that data from one cannot leak into the other.
|
*/

beforeEach(function () {
    // Organization A — owned by User A
    $this->userA = createUserWithTeam(['name' => 'User A']);
    $this->orgA = createOrganizationWithLocation(['company_name' => 'Org A Inc.'], [], $this->userA);
    $this->customerA = createCustomerWithLocation(['name' => 'Customer A'], [], $this->orgA);
    $this->invoiceA = createInvoiceWithItems(['invoice_number' => 'ORGA-'.uniqid()], null, $this->orgA, $this->customerA);

    // Organization B — owned by User B
    $this->userB = createUserWithTeam(['name' => 'User B']);
    $this->orgB = createOrganizationWithLocation(['company_name' => 'Org B Inc.'], [], $this->userB);
    $this->customerB = createCustomerWithLocation(['name' => 'Customer B'], [], $this->orgB);
    $this->invoiceB = createInvoiceWithItems(['invoice_number' => 'ORGB-'.uniqid()], null, $this->orgB, $this->customerB);
});

// ──────────────────────────────────────────────────────
// OrganizationScope: Invoice isolation
// ──────────────────────────────────────────────────────

test('user A only sees their own invoices via OrganizationScope', function () {
    $this->actingAs($this->userA);

    $invoices = Invoice::all();

    expect($invoices)->toHaveCount(1);
    expect($invoices->first()->id)->toBe($this->invoiceA->id);
});

test('user B only sees their own invoices via OrganizationScope', function () {
    $this->actingAs($this->userB);

    $invoices = Invoice::all();

    expect($invoices)->toHaveCount(1);
    expect($invoices->first()->id)->toBe($this->invoiceB->id);
});

test('unauthenticated users see all invoices (for public ULID routes)', function () {
    $invoices = Invoice::all();

    expect($invoices)->toHaveCount(2);
});

// ──────────────────────────────────────────────────────
// OrganizationScope: Customer isolation
// ──────────────────────────────────────────────────────

test('user A only sees their own customers via OrganizationScope', function () {
    $this->actingAs($this->userA);

    $customers = Customer::all();

    expect($customers)->toHaveCount(1);
    expect($customers->first()->id)->toBe($this->customerA->id);
});

test('user B only sees their own customers via OrganizationScope', function () {
    $this->actingAs($this->userB);

    $customers = Customer::all();

    expect($customers)->toHaveCount(1);
    expect($customers->first()->id)->toBe($this->customerB->id);
});

// ──────────────────────────────────────────────────────
// OrganizationScope: TaxTemplate isolation
// ──────────────────────────────────────────────────────

test('user only sees tax templates for their organization', function () {
    TaxTemplate::create([
        'organization_id' => $this->orgA->id,
        'name' => 'GST 18%',
        'rate' => 1800,
        'country_code' => 'IN',
        'type' => 'GST',
    ]);

    TaxTemplate::create([
        'organization_id' => $this->orgB->id,
        'name' => 'VAT 5%',
        'rate' => 500,
        'country_code' => 'AE',
        'type' => 'VAT',
    ]);

    $this->actingAs($this->userA);
    $templates = TaxTemplate::all();

    expect($templates)->toHaveCount(1);
    expect($templates->first()->name)->toBe('GST 18%');
});

// ──────────────────────────────────────────────────────
// OrganizationScope: Null currentTeam edge case
// ──────────────────────────────────────────────────────

test('user with no current team sees no scoped data', function () {
    $user = User::create([
        'name' => 'Orphan User',
        'email' => 'orphan'.uniqid().'@example.test',
        'email_verified_at' => now(),
        'password' => 'password',
    ]);

    $this->actingAs($user);

    expect(Invoice::count())->toBe(0);
    expect(Customer::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Policy Authorization Tests
|--------------------------------------------------------------------------
|
| Verify that all policies correctly allow/deny based on team membership.
|
*/

// ──────────────────────────────────────────────────────
// InvoicePolicy
// ──────────────────────────────────────────────────────

test('InvoicePolicy: owner can view their own invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('view', $this->invoiceA))->toBeTrue();
});

test('InvoicePolicy: user cannot view another organizations invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('view', $this->invoiceB))->toBeFalse();
});

test('InvoicePolicy: owner can update their own invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('update', $this->invoiceA))->toBeTrue();
});

test('InvoicePolicy: user cannot update another organizations invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('update', $this->invoiceB))->toBeFalse();
});

test('InvoicePolicy: owner can delete their own invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('delete', $this->invoiceA))->toBeTrue();
});

test('InvoicePolicy: user cannot delete another organizations invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('delete', $this->invoiceB))->toBeFalse();
});

test('InvoicePolicy: owner can send their own invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('send', $this->invoiceA))->toBeTrue();
});

test('InvoicePolicy: user cannot send another organizations invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('send', $this->invoiceB))->toBeFalse();
});

test('InvoicePolicy: owner can download PDF for their own invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('downloadPdf', $this->invoiceA))->toBeTrue();
});

test('InvoicePolicy: user cannot download PDF for another organizations invoice', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('downloadPdf', $this->invoiceB))->toBeFalse();
});

// ──────────────────────────────────────────────────────
// CustomerPolicy
// ──────────────────────────────────────────────────────

test('CustomerPolicy: owner can view their own customer', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('view', $this->customerA))->toBeTrue();
});

test('CustomerPolicy: user cannot view another organizations customer', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('view', $this->customerB))->toBeFalse();
});

test('CustomerPolicy: owner can update their own customer', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('update', $this->customerA))->toBeTrue();
});

test('CustomerPolicy: user cannot update another organizations customer', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('update', $this->customerB))->toBeFalse();
});

test('CustomerPolicy: owner can delete their own customer', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('delete', $this->customerA))->toBeTrue();
});

test('CustomerPolicy: user cannot delete another organizations customer', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('delete', $this->customerB))->toBeFalse();
});

// ──────────────────────────────────────────────────────
// TeamPolicy
// ──────────────────────────────────────────────────────

test('TeamPolicy: owner can view their own organization', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('view', $this->orgA))->toBeTrue();
});

test('TeamPolicy: user cannot view another organization', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('view', $this->orgB))->toBeFalse();
});

test('TeamPolicy: owner can update their own organization', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('update', $this->orgA))->toBeTrue();
});

test('TeamPolicy: non-owner cannot update organization', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('update', $this->orgB))->toBeFalse();
});

test('TeamPolicy: owner can delete their own organization', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('delete', $this->orgA))->toBeTrue();
});

test('TeamPolicy: non-owner cannot delete organization', function () {
    $this->actingAs($this->userA);
    expect(Gate::allows('delete', $this->orgB))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Cross-Tenant Mutation Tests (HTTP)
|--------------------------------------------------------------------------
|
| Verify that controllers prevent cross-tenant write operations.
|
*/

test('user cannot update another organizations customer via HTTP', function () {
    $this->actingAs($this->userA);

    $response = $this->putJson("/customers/{$this->customerB->id}", [
        'name' => 'Hacked Name',
    ]);

    // OrganizationScope filters out cross-tenant records, returning 404
    expect($response->status())->toBeIn([403, 404]);
});

test('user cannot delete another organizations customer via HTTP', function () {
    $this->actingAs($this->userA);

    $response = $this->deleteJson("/customers/{$this->customerB->id}");

    expect($response->status())->toBeIn([403, 404]);
});

test('user cannot delete another organizations invoice via HTTP', function () {
    $this->actingAs($this->userA);

    $response = $this->deleteJson("/invoices/{$this->invoiceB->id}");

    expect($response->status())->toBeIn([403, 404]);
});

test('user cannot download PDF for another organizations invoice via HTTP', function () {
    $this->actingAs($this->userA);

    $response = $this->get("/invoices/{$this->invoiceB->id}/download");

    expect($response->status())->toBeIn([403, 404]);
});

/*
|--------------------------------------------------------------------------
| Team Membership & Switching Tests
|--------------------------------------------------------------------------
|
| Verify team membership checks, switching behavior, and edge cases
| involving users with access to multiple organizations.
|
*/

test('team member can access organization data via allTeams()', function () {
    // Add User A as a member of Org B
    $this->orgB->users()->attach($this->userA, ['role' => 'editor']);

    $this->actingAs($this->userA);

    // User A should be able to access Org B's invoice via policy
    expect(Gate::allows('view', $this->invoiceB))->toBeTrue();

    // But OrganizationScope still restricts to currentTeam (Org A)
    $invoices = Invoice::all();
    expect($invoices)->toHaveCount(1);
    expect($invoices->first()->id)->toBe($this->invoiceA->id);
});

test('switching teams changes OrganizationScope context', function () {
    // Add User A as a member of Org B
    $this->orgB->users()->attach($this->userA, ['role' => 'editor']);

    $this->actingAs($this->userA);

    // Currently on Org A — should see Org A data
    expect(Invoice::count())->toBe(1);
    expect(Invoice::first()->id)->toBe($this->invoiceA->id);

    // Refresh user to pick up the pivot attachment, then switch
    $userA = $this->userA->fresh();
    $switched = $userA->switchTeam($this->orgB);
    expect($switched)->toBeTrue();

    // Re-authenticate with a completely fresh user from DB
    $this->actingAs(User::find($userA->id));
    expect(auth()->user()->current_team_id)->toBe($this->orgB->id);

    // Now should see Org B data
    expect(Invoice::count())->toBe(1);
    expect(Invoice::first()->id)->toBe($this->invoiceB->id);
});

test('user cannot switch to a team they do not belong to', function () {
    $this->actingAs($this->userA);

    $result = $this->userA->switchTeam($this->orgB);

    expect($result)->toBeFalse();
    expect($this->userA->currentTeam->id)->toBe($this->orgA->id);
});

test('removed team member cannot access former organizations data via policy', function () {
    // Add User A as member of Org B, then remove
    $this->orgB->users()->attach($this->userA, ['role' => 'editor']);
    expect(Gate::forUser($this->userA)->allows('view', $this->invoiceB))->toBeTrue();

    $this->orgB->removeUser($this->userA->fresh());

    // After removal, policy should deny
    expect(Gate::forUser($this->userA->fresh())->allows('view', $this->invoiceB))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Public Document Access Tests
|--------------------------------------------------------------------------
|
| Verify that public ULID routes work without authentication and that
| invalid ULIDs are rejected.
|
*/

test('public invoice is accessible without authentication via ULID', function () {
    $response = $this->get(route('invoices.public', $this->invoiceA->ulid));

    $response->assertStatus(200);
});

test('public estimate is accessible without authentication via ULID', function () {
    $estimate = createInvoiceWithItems(
        ['type' => 'estimate', 'invoice_number' => 'EST-'.uniqid()],
        null,
        $this->orgA,
        $this->customerA,
    );

    $response = $this->get(route('estimates.public', $estimate->ulid));

    $response->assertStatus(200);
});

test('invalid ULID returns 404 for public invoice route', function () {
    $response = $this->get('/invoices/view/nonexistent-ulid-value-here');

    $response->assertStatus(404);
});

test('invalid ULID returns 404 for public estimate route', function () {
    $response = $this->get('/estimates/view/nonexistent-ulid-value-here');

    $response->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| NumberingSeries Cross-Tenant Tests (HTTP)
|--------------------------------------------------------------------------
*/

test('user cannot update another organizations numbering series via HTTP', function () {
    $series = InvoiceNumberingSeries::create([
        'organization_id' => $this->orgB->id,
        'name' => 'Org B Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => 'yearly',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->actingAs($this->userA);

    $response = $this->putJson("/numbering-series/{$series->id}", [
        'name' => 'Hacked Series',
        'prefix' => 'HACK',
        'format_pattern' => '{PREFIX}{SEQUENCE:4}',
        'reset_frequency' => 'yearly',
    ]);

    expect($response->status())->toBeIn([403, 404]);
});

test('user cannot delete another organizations numbering series via HTTP', function () {
    $series = InvoiceNumberingSeries::create([
        'organization_id' => $this->orgB->id,
        'name' => 'Org B Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => 'yearly',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->actingAs($this->userA);

    $response = $this->deleteJson("/numbering-series/{$series->id}");

    expect($response->status())->toBeIn([403, 404]);
});

test('user cannot toggle another organizations numbering series via HTTP', function () {
    $series = InvoiceNumberingSeries::create([
        'organization_id' => $this->orgB->id,
        'name' => 'Org B Series',
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}{SEQUENCE:4}',
        'current_number' => 0,
        'reset_frequency' => 'yearly',
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->actingAs($this->userA);

    $response = $this->postJson("/numbering-series/{$series->id}/toggle-active");

    expect($response->status())->toBeIn([403, 404]);
});

/*
|--------------------------------------------------------------------------
| Organization Cross-Tenant Tests (HTTP)
|--------------------------------------------------------------------------
*/

test('user cannot update another users organization via HTTP', function () {
    $this->actingAs($this->userA);

    $response = $this->putJson("/organizations/{$this->orgB->id}", [
        'name' => 'Hacked Org',
    ]);

    $response->assertStatus(403);
});

test('user cannot delete another users organization via HTTP', function () {
    $this->actingAs($this->userA);

    $response = $this->deleteJson("/organizations/{$this->orgB->id}");

    $response->assertStatus(403);
});
