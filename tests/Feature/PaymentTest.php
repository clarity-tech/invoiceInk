<?php

use App\Enums\InvoiceStatus;
use App\Models\Payment;
use App\Models\User;

// --- Record payment ---

test('can record a payment for an invoice', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems(
        ['status' => 'sent', 'total' => 100000],
        [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0]],
        $org,
    );

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/payments", [
            'amount' => 50000,
            'payment_date' => '2026-03-27',
            'payment_method' => 'bank_transfer',
            'reference' => 'TXN-001',
            'notes' => 'Partial payment',
        ])
        ->assertRedirect();

    expect(Payment::count())->toBe(1);

    $invoice->refresh();
    expect($invoice->amount_paid)->toBe(50000)
        ->and($invoice->status)->toBe(InvoiceStatus::PARTIALLY_PAID);
});

test('full payment marks invoice as paid', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems(
        ['status' => 'sent', 'total' => 100000],
        [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0]],
        $org,
    );

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/payments", [
            'amount' => 100000,
            'payment_date' => '2026-03-27',
        ])
        ->assertRedirect();

    $invoice->refresh();
    expect($invoice->amount_paid)->toBe(100000)
        ->and($invoice->status)->toBe(InvoiceStatus::PAID);
});

test('multiple payments accumulate correctly', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems(
        ['status' => 'sent', 'total' => 100000],
        [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0]],
        $org,
    );

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/payments", [
            'amount' => 30000,
            'payment_date' => '2026-03-25',
        ]);

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/payments", [
            'amount' => 70000,
            'payment_date' => '2026-03-27',
        ]);

    expect(Payment::count())->toBe(2);

    $invoice->refresh();
    expect($invoice->amount_paid)->toBe(100000)
        ->and($invoice->status)->toBe(InvoiceStatus::PAID);
});

// --- Delete payment ---

test('can delete a payment and updates invoice status', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems(
        ['status' => 'sent', 'total' => 100000],
        [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100000, 'tax_rate' => 0]],
        $org,
    );

    // Record then delete
    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/payments", [
            'amount' => 100000,
            'payment_date' => '2026-03-27',
        ]);

    $payment = Payment::first();

    $this->actingAs($user)
        ->delete("/invoices/{$invoice->id}/payments/{$payment->id}")
        ->assertRedirect();

    expect(Payment::count())->toBe(0);

    $invoice->refresh();
    expect($invoice->amount_paid)->toBe(0)
        ->and($invoice->status)->toBe(InvoiceStatus::SENT);
});

// --- Validation ---

test('payment requires amount and date', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems([], null, $org);

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/payments", [
            'amount' => '',
            'payment_date' => '',
        ])
        ->assertSessionHasErrors(['amount', 'payment_date']);
});

test('payment amount must be positive', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems([], null, $org);

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/payments", [
            'amount' => 0,
            'payment_date' => '2026-03-27',
        ])
        ->assertSessionHasErrors('amount');
});

// --- Authorization ---

test('unauthenticated users cannot record payments', function () {
    $invoice = createInvoiceWithItems();

    $this->post("/invoices/{$invoice->id}/payments", [
        'amount' => 10000,
        'payment_date' => '2026-03-27',
    ])->assertRedirect('/login');
});

// --- Edit page shows payments ---

test('invoice edit page loads with payments data', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems([], null, $org);

    $invoice->payments()->create([
        'amount' => 50000,
        'currency' => $invoice->currency,
        'payment_date' => '2026-03-27',
        'payment_method' => 'bank_transfer',
    ]);

    $this->actingAs($user)
        ->get("/invoices/{$invoice->id}/edit")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Invoices/Edit')
            ->has('invoice.payments', 1)
        );
});
