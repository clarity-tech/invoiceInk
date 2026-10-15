<?php

use App\Ai\Agents\EmailWriter;
use App\Models\User;

it('writes a reminder with AI in the email dialog, for review before sending', function () {
    config(['ai.providers.anthropic.key' => 'test-key']);
    $user = User::factory()->withPersonalTeam()->create(['email' => 'ai-email@example.test']);
    $org = createOrganizationWithLocation(['setup_completed_at' => now(), 'ai_drafting_enabled' => true], [], $user);
    $customer = createCustomerWithLocation([
        'name' => 'Sandline Pharmacy LLC',
        'emails' => [['name' => 'Accounts', 'email' => 'accounts@sandline.test']],
    ], [], $org);
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-0301',
        'status' => 'sent',
        'issued_at' => now()->subDays(40),
        'due_at' => now()->subDays(10),
    ], null, $org, $customer);

    EmailWriter::fake([[
        'subject' => 'Overdue: invoice CT-INV-0301',
        'paragraphs' => ['Dear Sandline Pharmacy LLC,', 'Please settle the balance of CT-INV-0301 this week.'],
    ]]);

    $this->actingAs($user);

    $this->visit("/invoices/{$invoice->id}")
        ->click('button:has-text("Remind")')
        ->waitForText('Write with AI')
        ->click('[role="radio"]:has-text("Firm")')
        ->click('[role="dialog"] button:has-text("Write")')
        ->waitForText('Written by AI')
        ->assertValue('#email-subject', 'Overdue: invoice CT-INV-0301')
        ->assertSee('Please settle the balance of CT-INV-0301 this week.')
        ->click('button:has-text("Undo")')
        ->assertDontSee('Please settle the balance of CT-INV-0301 this week.')
        ->assertValue('#email-subject', 'Overdue: Invoice #CT-INV-0301 — 10 days past due')
        ->assertNoJavaScriptErrors();
});
