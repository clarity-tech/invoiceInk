<?php

use App\Models\User;

/**
 * Focus restoration after a confirmed delete (#58).
 *
 * The dialog hands focus back to the row action that opened it, but the row is
 * removed moments later -- optimistically on the listings, and after the server
 * responds on the customers trash. Either way the restore target is detached by
 * the time it matters, so focus falls to <body> and keyboard and screen-reader
 * users are dumped at the top of the document.
 *
 * The guard is `document.activeElement` after confirming. A component test
 * cannot reproduce this: the detachment is the whole defect, and a synthetic
 * opener that never unmounts never detaches.
 */
function focusFixture(string $email): array
{
    $user = User::factory()->withPersonalTeam()->create([
        'name' => 'Maya Sharma',
        'email' => $email,
    ]);

    $org = createOrganizationWithLocation([
        'name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    return [$user, $org];
}

/** Where focus landed, as a readable tag plus any aria-label. */
const ACTIVE_EL = 'function () {
    const a = document.activeElement;
    if (!a) { return "NONE"; }
    return a.tagName + (a.getAttribute("aria-label") ? "[" + a.getAttribute("aria-label") + "]" : "");
}';

it('keeps focus in the document after deleting a customer', function () {
    [$user, $org] = focusFixture('focus-cust@example.test');
    createCustomerWithLocation(['name' => 'Acme Diagnostics Ltd'], [], $org);
    createCustomerWithLocation(['name' => 'Sandline Pharmacy LLC'], [], $org);

    $this->actingAs($user);

    $page = visit('/customers')->waitForText('Acme Diagnostics Ltd');
    $page->click('tbody tr:first-child button:has-text("Delete")')
        ->waitForText('Delete Customer')
        ->click('[role=dialog] button:has-text("Delete")')
        ->wait(1)
        ->assertScript(ACTIVE_EL.'() === "DIV[Customers list]"');
});

it('keeps focus in the document after force-deleting a trashed invoice', function () {
    [$user, $org] = focusFixture('focus-inv-trash@example.test');
    $cust = createCustomerWithLocation(['name' => 'Acme Diagnostics Ltd'], [], $org);

    foreach (['CT-INV-901', 'CT-INV-902'] as $n) {
        $inv = createInvoiceWithItems(['invoice_number' => $n], null, $org, $cust);
        $inv->delete();
    }

    $this->actingAs($user);

    $page = visit('/invoices/trash')->waitForText('CT-INV-901');
    $page->click('tbody tr:first-child button:has-text("Delete Forever")')
        ->waitForText('Delete Forever')
        ->click('[role=dialog] button:has-text("Delete Forever")')
        ->wait(1)
        ->assertScript(ACTIVE_EL.'() === "DIV[Trashed invoices list]"');
});

it('keeps focus in the document after force-deleting a trashed customer', function () {
    // This page deletes without an optimistic update, so the row survives the
    // dialog closing and only disappears once the server responds -- the same
    // defect, one round trip later.
    [$user, $org] = focusFixture('focus-cust-trash@example.test');

    foreach (['Acme Diagnostics Ltd', 'Sandline Pharmacy LLC'] as $n) {
        $c = createCustomerWithLocation(['name' => $n], [], $org);
        $c->delete();
    }

    $this->actingAs($user);

    $page = visit('/customers/trash')->waitForText('Acme Diagnostics Ltd');
    $page->click('tbody tr:first-child button:has-text("Delete Forever")')
        ->waitForText('Delete Forever')
        ->click('[role=dialog] button:has-text("Delete Forever")')
        ->wait(2)
        ->assertScript(ACTIVE_EL.'() === "DIV[Trashed customers list]"');
});

it('still returns focus to the opener when the dialog is cancelled', function () {
    // The fix only diverts focus on confirm. Cancelling must behave as before
    // and hand focus back to the button that opened the dialog -- this guards
    // the other nine ConfirmationModal consumers, none of which delete a row.
    [$user, $org] = focusFixture('focus-cancel@example.test');
    createCustomerWithLocation(['name' => 'Acme Diagnostics Ltd'], [], $org);

    $this->actingAs($user);

    $page = visit('/customers')->waitForText('Acme Diagnostics Ltd');
    $page->click('tbody tr:first-child button:has-text("Delete")')
        ->waitForText('Delete Customer')
        ->click('[role=dialog] button:has-text("Cancel")')
        ->wait(1)
        // The row is untouched, so focus belongs back on its Delete button.
        ->assertScript(
            'function () {
                const a = document.activeElement;
                return !!a && a.tagName === "BUTTON"
                    && (a.textContent || "").trim() === "Delete";
            }',
        );
});
