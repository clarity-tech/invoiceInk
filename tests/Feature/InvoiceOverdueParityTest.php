<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

/**
 * `scopeOverdue()` answers "which invoices are overdue" as SQL; `isOverdue()`
 * answers it per row in PHP. The dashboard uses the first, the invoice list
 * uses the second, and nothing stops them drifting apart.
 *
 * This asserts they agree across every status, so a change to one that is not
 * mirrored in the other fails here rather than showing a user two different
 * answers on two pages.
 */
it('agrees with the overdue scope across every status', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    $cases = [
        // [status, due_at, label]
        [InvoiceStatus::DRAFT, now()->subDays(5), 'draft, past due'],
        [InvoiceStatus::SENT, now()->subDays(5), 'sent, past due'],
        [InvoiceStatus::SENT, now()->addDays(5), 'sent, not yet due'],
        [InvoiceStatus::ACCEPTED, now()->subDays(5), 'accepted, past due'],
        [InvoiceStatus::PARTIALLY_PAID, now()->subDays(5), 'part paid, past due'],
        [InvoiceStatus::PAID, now()->subDays(5), 'paid, past due'],
        [InvoiceStatus::VOID, now()->subDays(5), 'void, past due'],
    ];

    foreach ($cases as [$status, $dueAt, $label]) {
        createInvoiceWithItems([
            'status' => $status,
            'due_at' => $dueAt,
            'issued_at' => now()->subDays(40),
        ], null, $organization, $customer);
    }

    $scopeIds = Invoice::invoices()->overdue()->pluck('id')->sort()->values()->all();

    $methodIds = Invoice::invoices()->get()
        ->filter(fn (Invoice $invoice) => $invoice->isOverdue())
        ->pluck('id')->sort()->values()->all();

    expect($methodIds)->toBe($scopeIds)
        ->and($scopeIds)->not->toBeEmpty();
});
