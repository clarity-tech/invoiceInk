<?php

use App\Ai\Agents\EmailWriter;
use App\Enums\InvoiceStatus;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    config(['ai.providers.anthropic.key' => 'test-key']);
    $this->travelTo('2026-10-16 10:00:00');
});

function emailDraftInvoice(array $organizationAttributes = []): Invoice
{
    $organization = createOrganizationWithLocation(array_merge(['ai_drafting_enabled' => true], $organizationAttributes));

    return createInvoiceWithItems([
        'invoice_number' => 'INV-ED-001',
        'status' => InvoiceStatus::PARTIALLY_PAID,
        'currency' => 'INR',
        'total' => 118000,
        'amount_paid' => 18000,
        'issued_at' => '2026-09-01',
        'due_at' => '2026-10-11',
    ], null, $organization, createCustomerWithLocation(['name' => 'Acme Industries'], [], $organization));
}

function postEmailDraft(User $user, Invoice $invoice, array $payload = []): TestResponse
{
    return test()->actingAs($user)->postJson("/invoices/{$invoice->id}/email-draft", array_merge([
        'tone' => 'firm',
        'template_type' => 'invoice_overdue',
    ], $payload));
}

it('writes the email from the invoice and returns it as safe HTML', function () {
    $invoice = emailDraftInvoice();
    EmailDelivery::factory()->sent()->create([
        'organization_id' => $invoice->organization_id,
        'invoice_id' => $invoice->id,
        'sent_at' => '2026-10-12 09:00:00',
    ]);
    EmailWriter::fake([[
        'subject' => 'Payment overdue: INV-ED-001',
        'paragraphs' => ['Dear Acme Industries,', "The balance is overdue.\nPlease pay this week.", '<script>alert(1)</script>'],
    ]]);

    postEmailDraft($invoice->organization->owner, $invoice, ['note' => 'Mention we can split the payment'])
        ->assertOk()
        ->assertExactJson([
            'subject' => 'Payment overdue: INV-ED-001',
            'body' => '<p>Dear Acme Industries,</p><p>The balance is overdue.<br>'."\n".'Please pay this week.</p><p>&lt;script&gt;alert(1)&lt;/script&gt;</p>',
        ]);

    EmailWriter::assertPrompted(fn (AgentPrompt $prompt) => $prompt->contains('Purpose: Sent when payment is past due.')
        && $prompt->contains('Balance due: ₹1,000.00')
        && $prompt->contains('Overdue by 5 days')
        && $prompt->contains('Emails already sent about this Invoice: 1, the last on 12 Oct 2026')
        && $prompt->contains('Tone: firm')
        && $prompt->contains("The sender's note: Mention we can split the payment"));
});

it('writes for an estimate as an estimate', function () {
    $invoice = emailDraftInvoice();
    $estimate = createInvoiceWithItems(['type' => 'estimate', 'invoice_number' => 'EST-ED-001', 'due_at' => '2026-11-01'], null, $invoice->organization);
    EmailWriter::fake([['subject' => 'Your estimate', 'paragraphs' => ['Hello,']]]);

    postEmailDraft($invoice->organization->owner, $estimate, ['tone' => 'friendly', 'template_type' => 'estimate_initial'])->assertOk();

    EmailWriter::assertPrompted(fn (AgentPrompt $prompt) => $prompt->contains('Estimate number: EST-ED-001')
        && $prompt->contains('Valid until: 1 Nov 2026')
        && ! $prompt->contains('Balance due'));
});

it('is offered only to organizations that opted in, once a key is set', function (array $attributes, ?string $key) {
    config(['ai.providers.anthropic.key' => $key]);
    $invoice = emailDraftInvoice($attributes);
    EmailWriter::fake()->preventStrayPrompts();

    test()->actingAs($invoice->organization->owner)->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('auth.currentTeam.ai', false));
    postEmailDraft($invoice->organization->owner, $invoice)->assertForbidden();
})->with([
    'not opted in' => [['ai_drafting_enabled' => false], 'test-key'],
    'no key' => [[], null],
]);

it('refuses a member who cannot send the invoice', function () {
    $invoice = emailDraftInvoice();
    $viewer = User::factory()->create();
    $invoice->organization->users()->attach($viewer, ['role' => 'viewer']);
    $viewer->switchTeam($invoice->organization);
    EmailWriter::fake()->preventStrayPrompts();

    postEmailDraft($viewer, $invoice)->assertForbidden();
});

it("does not write about another organization's invoice", function () {
    $invoice = emailDraftInvoice();
    $outsider = createOrganizationWithLocation(['ai_drafting_enabled' => true]);
    EmailWriter::fake()->preventStrayPrompts();

    postEmailDraft($outsider->owner, $invoice)->assertNotFound();
});

it('needs a tone it knows', function () {
    $invoice = emailDraftInvoice();
    EmailWriter::fake()->preventStrayPrompts();

    postEmailDraft($invoice->organization->owner, $invoice, ['tone' => 'angry'])->assertJsonValidationErrors('tone');
});

it('says so when the email cannot be written', function () {
    $invoice = emailDraftInvoice();
    EmailWriter::fake(fn () => throw new ProviderOverloadedException('Overloaded'));

    postEmailDraft($invoice->organization->owner, $invoice)
        ->assertJsonValidationErrors(['draft' => 'The email could not be written just now. Try again, or write it yourself.']);
});

it('limits how many emails a person writes in an hour', function () {
    config(['services.email_drafts.per_hour' => 1]);
    $invoice = emailDraftInvoice();
    EmailWriter::fake([['subject' => 'Reminder', 'paragraphs' => ['Hello,']]]);

    postEmailDraft($invoice->organization->owner, $invoice)->assertOk();
    postEmailDraft($invoice->organization->owner, $invoice)
        ->assertJsonValidationErrors(['draft' => 'You have written a lot of emails with AI in the last hour. Try again later.']);
});

it('shares that AI is available with every page', function () {
    $invoice = emailDraftInvoice();

    test()->actingAs($invoice->organization->owner)->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('auth.currentTeam.ai', true));
});
