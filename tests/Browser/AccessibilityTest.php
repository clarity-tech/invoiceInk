<?php

use App\Models\User;

it('login page has no javascript errors', function () {
    $page = $this->visit('/login');

    $page->assertNoJavascriptErrors();
});

it('register page has no javascript errors', function () {
    $page = $this->visit('/register');

    $page->assertNoJavascriptErrors();
});

it('dashboard has no javascript errors for authenticated users', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'a11y-dash@example.test',
        'password' => 'password',
    ]);

    $organization = $user->currentTeam;
    $organization->update([
        'company_name' => 'A11y Dashboard Corp',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ]);

    $this->actingAs($user);

    $page = $this->visit('/dashboard');

    $page->assertNoJavascriptErrors();
});

it('public invoice view has no javascript errors', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-A11Y-001']);

    $page = $this->visit("/invoices/view/{$invoice->ulid}");

    $page->assertNoJavascriptErrors();
});
