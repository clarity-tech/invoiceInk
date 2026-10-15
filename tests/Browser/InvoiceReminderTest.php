<?php

use App\Enums\EmailTemplateType;
use App\Models\Customer;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Models\User;

it('sends an overdue reminder from the list shortcut after review', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'reminder-invoice@example.test',
    ]);
    $org = createOrganizationWithLocation(['setup_completed_at' => now()], [], $user);
    $customer = createCustomerWithLocation([
        'name' => 'Sandline Pharmacy LLC',
        'emails' => [['name' => 'Accounts', 'email' => 'accounts@sandline.test']],
    ], [], $org);
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-0201',
        'status' => 'sent',
        'issued_at' => now()->subDays(40),
        'due_at' => now()->subDays(10),
        'email_recipients' => [],
    ], null, $org, $customer);

    $this->actingAs($user);

    $this->visit('/invoices')
        ->waitForText('CT-INV-0201')
        ->click('[aria-label="Remind invoice CT-INV-0201"]')
        ->waitForText('Email to Sandline Pharmacy LLC')
        ->assertPathIs("/invoices/{$invoice->id}")
        ->assertValue('input[type="text"]', 'Overdue: Invoice #CT-INV-0201 — 10 days past due')
        ->assertSee('accounts@sandline.test')
        // Sending is synchronous in tests; skip rendering a PDF.
        ->click('label:has-text("CT-INV-0201.pdf")')
        ->click('[role="dialog"] button:has-text("Send")')
        ->waitForText('Invoice queued for sending.')
        ->assertSee('Overdue Notice')
        ->assertSee('Last reminder')
        ->assertNoJavaScriptErrors();

    $delivery = EmailDelivery::where('invoice_id', $invoice->id)->sole();

    expect($delivery->template_type)->toBe(EmailTemplateType::InvoiceOverdue)
        ->and($delivery->recipients)->toBe(['accounts@sandline.test']);
});

/**
 * @return array{0: User, 1: Customer, 2: Invoice, 3: Invoice}
 */
function customerOwingTwoInvoices(string $email): array
{
    $user = User::factory()->withPersonalTeam()->create(['email' => $email]);
    $org = createOrganizationWithLocation(['setup_completed_at' => now()], [], $user);
    $customer = createCustomerWithLocation([
        'name' => 'Sandline Pharmacy LLC',
        'emails' => [['name' => 'Accounts', 'email' => 'accounts@sandline.test']],
    ], [], $org);
    $owed = fn (string $number, int $daysOverdue) => createInvoiceWithItems([
        'invoice_number' => $number,
        'status' => 'sent',
        'due_at' => now()->subDays($daysOverdue),
    ], null, $org, $customer);

    return [$user, $customer, $owed('CT-INV-0301', 20), $owed('CT-INV-0302', 5)];
}

it('sends one reminder for the invoices left selected on the customer page', function () {
    [$user, $customer, $first, $second] = customerOwingTwoInvoices('reminder-customer@example.test');
    createInvoiceWithItems([
        'invoice_number' => 'CT-INV-0303',
        'status' => 'sent',
        'due_at' => now()->addDays(10),
    ], null, $user->currentTeam, $customer);

    $this->actingAs($user);

    // The overdue two start selected, the one not yet due does not, and the
    // subject names whatever is selected.
    $this->visit("/customers/{$customer->id}")
        ->waitForText('Unpaid invoices (3)')
        ->click('button:has-text("Send reminder")')
        ->waitForText('Remind Sandline Pharmacy LLC')
        ->assertValue('#customer-reminder-subject', 'Payment reminder: CT-INV-0301, CT-INV-0302')
        ->click('[role="dialog"] label:has-text("CT-INV-0302")')
        ->assertValue('#customer-reminder-subject', 'Payment reminder: CT-INV-0301')
        ->click('[role="dialog"] button:has-text("Send")')
        ->waitForText('Reminder queued for sending.')
        ->assertNoJavaScriptErrors();

    $delivery = EmailDelivery::sole();

    expect($delivery->invoice_id)->toBe($first->id)
        ->and($delivery->template_type)->toBe(EmailTemplateType::CustomerReminder)
        ->and($delivery->recipients)->toBe(['accounts@sandline.test'])
        ->and($second->emailDeliveries()->exists())->toBeFalse();
});

it('switches an invoice reminder to one for all the customer owes', function () {
    [$user, , $first] = customerOwingTwoInvoices('reminder-switch@example.test');

    $this->actingAs($user);

    $this->visit("/invoices/{$first->id}")
        ->waitForText('CT-INV-0301')
        ->click('button:has-text("Remind")')
        ->waitForText('Sandline Pharmacy LLC has 2 unpaid invoices.')
        ->click('button:has-text("Send one combined reminder")')
        ->waitForText('Remind Sandline Pharmacy LLC')
        ->assertSee('CT-INV-0301')
        ->assertSee('CT-INV-0302')
        ->assertDontSee('Email to Sandline Pharmacy LLC')
        ->assertNoJavaScriptErrors();
});

it('offers the reminder on the edit page too', function () {
    [$user, , $first] = customerOwingTwoInvoices('reminder-edit@example.test');

    $this->actingAs($user);

    $this->visit("/invoices/{$first->id}/edit")
        ->waitForText('Edit Invoice')
        ->click('button:has-text("Remind")')
        ->waitForText('Email to Sandline Pharmacy LLC')
        ->assertValue('[role="dialog"] input[type="text"]', 'Overdue: Invoice #CT-INV-0301 — 20 days past due')
        ->click('button:has-text("Send one combined reminder")')
        ->waitForText('Remind Sandline Pharmacy LLC')
        ->assertValue('#customer-reminder-subject', 'Payment reminder: CT-INV-0301, CT-INV-0302')
        ->assertNoJavaScriptErrors();
});
