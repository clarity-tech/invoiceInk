<?php

use App\Ai\Agents\EmailWriter;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    config(['ai.providers.anthropic.key' => 'test-key']);
    $this->travelTo('2026-10-16 10:00:00');
});

/**
 * @return array{0: Customer, 1: Invoice, 2: Invoice}
 */
function customerOwingTwo(array $organizationAttributes = []): array
{
    $organization = createOrganizationWithLocation(array_merge(['ai_drafting_enabled' => true], $organizationAttributes));
    $customer = createCustomerWithLocation(['name' => 'Sandline Pharmacy LLC'], [], $organization);
    $owed = fn (string $number, string $dueAt, int $total) => createInvoiceWithItems([
        'invoice_number' => $number,
        'status' => InvoiceStatus::SENT,
        'currency' => 'INR',
        'total' => $total,
        'due_at' => $dueAt,
    ], null, $organization, $customer);

    return [$customer, $owed('REM-AI-LATER', '2026-10-30', 50000), $owed('REM-AI-EARLIER', '2026-10-06', 118000)];
}

function postReminderDraft(User $user, Customer $customer, array $invoiceIds, array $payload = []): TestResponse
{
    return test()->actingAs($user)->postJson("/customers/{$customer->id}/reminder-draft", array_merge([
        'tone' => 'formal',
        'invoice_ids' => $invoiceIds,
    ], $payload));
}

it('writes one reminder from the selected invoices', function () {
    [$customer, $later, $earlier] = customerOwingTwo();
    EmailWriter::fake([['subject' => 'Two invoices awaiting payment', 'paragraphs' => ['Dear Sandline Pharmacy LLC,']]]);

    postReminderDraft($customer->organization->owner, $customer, [$later->id, $earlier->id])
        ->assertOk()
        ->assertExactJson(['subject' => 'Two invoices awaiting payment', 'body' => '<p>Dear Sandline Pharmacy LLC,</p>']);

    EmailWriter::assertPrompted(fn (AgentPrompt $prompt) => $prompt->contains('To: Sandline Pharmacy LLC')
        && $prompt->contains('Invoices: REM-AI-EARLIER: balance ₹1,180.00, due 6 Oct 2026, overdue by 10 days; REM-AI-LATER: balance ₹500.00, due 30 Oct 2026')
        && $prompt->contains('Total due: ₹1,680.00')
        && $prompt->contains('Tone: formal'));
});

it('refuses an invoice the customer no longer owes', function () {
    [$customer, $later] = customerOwingTwo();
    $paid = createInvoiceWithItems(['status' => InvoiceStatus::PAID, 'amount_paid' => 11800], null, $customer->organization, $customer);
    EmailWriter::fake()->preventStrayPrompts();

    postReminderDraft($customer->organization->owner, $customer, [$later->id, $paid->id])->assertJsonValidationErrors('invoice_ids');
});

it('is offered only to organizations that opted in', function () {
    [$customer, $later] = customerOwingTwo(['ai_drafting_enabled' => false]);
    EmailWriter::fake()->preventStrayPrompts();

    postReminderDraft($customer->organization->owner, $customer, [$later->id])->assertForbidden();
});

it('refuses a member who cannot send invoices', function () {
    [$customer, $later] = customerOwingTwo();
    $viewer = User::factory()->create();
    $customer->organization->users()->attach($viewer, ['role' => 'viewer']);
    $viewer->switchTeam($customer->organization);
    EmailWriter::fake()->preventStrayPrompts();

    postReminderDraft($viewer, $customer, [$later->id])->assertForbidden();
});
