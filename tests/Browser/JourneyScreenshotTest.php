<?php

use App\Enums\InvoiceStatus;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;

/**
 * Capture organized screenshots of all user journeys.
 * Screenshots saved to tests/Browser/Screenshots/journeys/{story}/
 */

// Reuse seedDemoData() from DemoScreenshotTest
require_once __DIR__.'/DemoScreenshotTest.php';

function ensureDir(string $path): void
{
    $full = base_path("tests/Browser/Screenshots/journeys/{$path}");
    if (! is_dir($full)) {
        mkdir($full, 0755, true);
    }
}

// ─── Story 1: Authentication ───

it('captures authentication journey', function () {
    ensureDir('01-authentication');

    $this->visit('/login')
        ->assertSee('Log in')
        ->screenshot(fullPage: true, filename: 'journeys/01-authentication/01-login-page');

    $this->visit('/register')
        ->assertSee('Register')
        ->screenshot(fullPage: true, filename: 'journeys/01-authentication/02-register-page');

    $this->visit('/forgot-password')
        ->assertSee('Forgot')
        ->screenshot(fullPage: true, filename: 'journeys/01-authentication/03-forgot-password');

    // Register a user and capture post-login
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'journey-auth@example.test',
    ]);

    $this->actingAs($user);

    $this->visit('/user/profile')
        ->assertPathIs('/user/profile')
        ->screenshot(fullPage: true, filename: 'journeys/01-authentication/04-user-profile');
});

// ─── Story 2: Organization Setup ───

it('captures organization setup journey', function () {
    ensureDir('02-organization-setup');

    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'journey-setup@example.test',
    ]);

    // Create a non-personal org that needs setup
    $org = Organization::create([
        'name' => 'New Business',
        'user_id' => $user->id,
        'personal_team' => false,
        'currency' => 'INR',
        'country_code' => 'IN',
    ]);
    $user->switchTeam($org);

    $this->actingAs($user);

    $this->visit('/organization/setup')
        ->assertPathIs('/organization/setup')
        ->screenshot(fullPage: true, filename: 'journeys/02-organization-setup/01-setup-wizard-step1');
});

// ─── Story 3: Organization Management ───

it('captures organization management journey', function () {
    ensureDir('03-organization-management');

    $data = seedDemoData();
    $this->actingAs($data['user']);

    $this->visit('/organizations')
        ->assertPathIs('/organizations')
        ->screenshot(fullPage: true, filename: 'journeys/03-organization-management/01-organization-list');

    $this->visit('/organization/edit')
        ->assertPathIs('/organization/edit')
        ->screenshot(fullPage: true, filename: 'journeys/03-organization-management/02-organization-edit');
});

// ─── Story 4: Customer Management ───

it('captures customer management journey', function () {
    ensureDir('04-customer-management');

    $data = seedDemoData();
    $this->actingAs($data['user']);

    $this->visit('/customers')
        ->assertPathIs('/customers')
        ->screenshot(fullPage: true, filename: 'journeys/04-customer-management/01-customer-list');
});

// ─── Story 5: Invoice Creation & Management ───

it('captures invoice journey', function () {
    ensureDir('05-invoice-management');

    $data = seedDemoData();
    $this->actingAs($data['user']);

    // Invoice list with various statuses
    $this->visit('/invoices')
        ->assertPathIs('/invoices')
        ->screenshot(fullPage: true, filename: 'journeys/05-invoice-management/01-invoice-list');

    // Create new invoice form
    $this->visit('/invoices/create')
        ->assertPathIs('/invoices/create')
        ->screenshot(fullPage: true, filename: 'journeys/05-invoice-management/02-invoice-create-form');

    // Edit an existing invoice
    $invoice = $data['invoices']['draft'];
    $this->visit("/invoices/{$invoice->id}/edit")
        ->assertPathIs("/invoices/{$invoice->id}/edit")
        ->screenshot(fullPage: true, filename: 'journeys/05-invoice-management/03-invoice-edit');
});

// ─── Story 6: Estimate Creation ───

it('captures estimate journey', function () {
    ensureDir('06-estimate-management');

    $data = seedDemoData();
    $this->actingAs($data['user']);

    // Create new estimate form
    $this->visit('/estimates/create')
        ->assertPathIs('/estimates/create')
        ->screenshot(fullPage: true, filename: 'journeys/06-estimate-management/01-estimate-create-form');

    // Edit an existing estimate
    $estimate = $data['estimates']['draft'];
    $this->visit("/invoices/{$estimate->id}/edit")
        ->assertPathIs("/invoices/{$estimate->id}/edit")
        ->screenshot(fullPage: true, filename: 'journeys/06-estimate-management/02-estimate-edit');
});

// ─── Story 7: Public Document Views ───

it('captures public document views', function () {
    ensureDir('07-public-views');

    $data = seedDemoData();

    // Public invoice view — paid
    $paid = $data['invoices']['paid'];
    $this->visit("/invoices/view/{$paid->ulid}")
        ->assertSee($paid->invoice_number)
        ->screenshot(fullPage: true, filename: 'journeys/07-public-views/01-public-invoice-paid');

    // Public invoice view — sent (with due date)
    $sent = $data['invoices']['sent'];
    $this->visit("/invoices/view/{$sent->ulid}")
        ->assertSee($sent->invoice_number)
        ->screenshot(fullPage: true, filename: 'journeys/07-public-views/02-public-invoice-sent');

    // Public invoice view — overdue
    $overdue = $data['invoices']['overdue'];
    $this->visit("/invoices/view/{$overdue->ulid}")
        ->assertSee($overdue->invoice_number)
        ->screenshot(fullPage: true, filename: 'journeys/07-public-views/03-public-invoice-overdue');

    // Public estimate view
    $estimate = $data['estimates']['sent'];
    $this->visit("/estimates/view/{$estimate->ulid}")
        ->assertSee($estimate->invoice_number)
        ->screenshot(fullPage: true, filename: 'journeys/07-public-views/04-public-estimate');
});

// ─── Story 8: Dashboard Analytics ───

it('captures dashboard analytics', function () {
    ensureDir('08-dashboard');

    $data = seedDemoData();

    // Add some payments for dashboard richness
    $service = new PaymentService;
    $paidInvoice = $data['invoices']['paid'];
    $paidInvoice->update(['status' => InvoiceStatus::SENT]); // reset for payment
    $service->recordPayment($paidInvoice, [
        'amount' => $paidInvoice->total,
        'payment_date' => now()->subMonth()->toDateString(),
        'payment_method' => 'bank_transfer',
        'reference' => 'NEFT-20260215-001',
    ]);

    $paid2 = $data['invoices']['paid2'];
    $paid2->update(['status' => InvoiceStatus::SENT]);
    $service->recordPayment($paid2, [
        'amount' => $paid2->total,
        'payment_date' => now()->subMonths(2)->toDateString(),
        'payment_method' => 'bank_transfer',
        'reference' => 'NEFT-20260115-002',
    ]);

    // Partial payment on sent invoice
    $sentInvoice = $data['invoices']['sent'];
    $service->recordPayment($sentInvoice, [
        'amount' => (int) ($sentInvoice->total * 0.5),
        'payment_date' => now()->subDays(3)->toDateString(),
        'payment_method' => 'card',
        'reference' => 'STRIPE-PAY-001',
    ]);

    $this->actingAs($data['user']);

    // Default view (this month)
    $this->visit('/dashboard')
        ->assertPathIs('/dashboard')
        ->screenshot(fullPage: true, filename: 'journeys/08-dashboard/01-dashboard-this-month');
});

// ─── Story 9: Invoice Numbering Series ───

it('captures numbering series management', function () {
    ensureDir('09-numbering-series');

    $data = seedDemoData();
    $this->actingAs($data['user']);

    $org = $data['org'];
    $this->visit("/organizations/{$org->id}/edit?tab=numbering")
        ->assertPathIs("/organizations/{$org->id}/edit")
        ->screenshot(fullPage: true, filename: 'journeys/09-numbering-series/01-numbering-series-list');
});

// ─── Story 10: Team Management ───

it('captures team management pages', function () {
    ensureDir('10-team-management');

    $data = seedDemoData();
    $this->actingAs($data['user']);

    $org = $data['org'];

    $this->visit("/teams/{$org->id}")
        ->assertPathIs("/teams/{$org->id}")
        ->screenshot(fullPage: true, filename: 'journeys/10-team-management/01-team-settings');

    $this->visit('/teams/create')
        ->assertPathIs('/teams/create')
        ->screenshot(fullPage: true, filename: 'journeys/10-team-management/02-create-team');
});
