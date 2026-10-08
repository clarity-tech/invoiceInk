<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

/**
 * isOverdue() / daysOverdue() mirror scopeOverdue(), which the dashboard uses
 * as a query. The list needs the same answer per row, so the rule exists in
 * both forms and these pin them to the same behaviour.
 */
it('is overdue once the due date has passed and money is still owed', function () {
    $invoice = new Invoice([
        'status' => InvoiceStatus::SENT,
        'due_at' => now()->subDays(3),
    ]);

    expect($invoice->isOverdue())->toBeTrue()
        ->and($invoice->daysOverdue())->toBe(3);
});

it('is not overdue before the due date', function () {
    $invoice = new Invoice([
        'status' => InvoiceStatus::SENT,
        'due_at' => now()->addDay(),
    ]);

    expect($invoice->isOverdue())->toBeFalse()
        ->and($invoice->daysOverdue())->toBeNull();
});

it('is never overdue once paid, however old', function () {
    $invoice = new Invoice([
        'status' => InvoiceStatus::PAID,
        'due_at' => now()->subYear(),
    ]);

    expect($invoice->isOverdue())->toBeFalse()
        ->and($invoice->daysOverdue())->toBeNull();
});

it('is never overdue once void', function () {
    $invoice = new Invoice([
        'status' => InvoiceStatus::VOID,
        'due_at' => now()->subYear(),
    ]);

    expect($invoice->isOverdue())->toBeFalse();
});

it('is not overdue without a due date', function () {
    $invoice = new Invoice([
        'status' => InvoiceStatus::SENT,
        'due_at' => null,
    ]);

    expect($invoice->isOverdue())->toBeFalse()
        ->and($invoice->daysOverdue())->toBeNull();
});

it('counts whole days, so the hour of day does not change the answer', function () {
    // Due late yesterday, read early today: still exactly one day late.
    $this->travelTo(now()->startOfDay()->addHours(2));

    $invoice = new Invoice([
        'status' => InvoiceStatus::SENT,
        'due_at' => now()->subDay()->endOfDay(),
    ]);

    expect($invoice->daysOverdue())->toBe(1);
});

it('still counts a partially paid document as overdue', function () {
    $invoice = new Invoice([
        'status' => InvoiceStatus::PARTIALLY_PAID,
        'due_at' => now()->subDays(10),
    ]);

    expect($invoice->isOverdue())->toBeTrue()
        ->and($invoice->daysOverdue())->toBe(10);
});
