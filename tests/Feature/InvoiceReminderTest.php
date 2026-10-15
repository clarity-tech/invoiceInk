<?php

use App\Enums\EmailTemplateType;
use App\Enums\InvoiceStatus;
use App\Mail\Templates\EmailTemplateService;
use App\Mail\Templates\TemplateVariableResolver;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\TestResponse;

function sentInvoiceOwing(array $attributes = []): Invoice
{
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);

    return createInvoiceWithItems(array_merge([
        'status' => InvoiceStatus::SENT,
        'due_at' => now()->subDays(10),
    ], $attributes), null, $organization, $customer);
}

function postReminder(Invoice $invoice, ?string $templateType): TestResponse
{
    return test()->actingAs($invoice->organization->owner)
        ->post("/invoices/{$invoice->id}/send-email", [
            'recipients' => ['billing@customer.test'],
            'subject' => 'Reminder',
            'body' => '<p>Please pay.</p>',
            'template_type' => $templateType,
        ]);
}

it('records which template a send used', function () {
    Bus::fake();
    $invoice = sentInvoiceOwing();

    postReminder($invoice, 'invoice_overdue')->assertSessionHasNoErrors();

    expect(EmailDelivery::where('invoice_id', $invoice->id)->sole()->template_type)
        ->toBe(EmailTemplateType::InvoiceOverdue);
});

it('refuses a template that is not for this kind of document', function (string $templateType) {
    Bus::fake();
    $invoice = sentInvoiceOwing();

    postReminder($invoice, $templateType)->assertSessionHasErrors('template_type');

    expect(EmailDelivery::where('invoice_id', $invoice->id)->exists())->toBeFalse();
})->with(['estimate_reminder', 'not_a_template']);

it('labels each send in the history by its template', function () {
    $invoice = sentInvoiceOwing();

    // Oldest first, so the history (newest first) reads them in reverse.
    foreach ([EmailTemplateType::InvoiceReminder, null] as $hoursAgo => $templateType) {
        EmailDelivery::create([
            'organization_id' => $invoice->organization_id,
            'invoice_id' => $invoice->id,
            'mailable_type' => 'test',
            'template_type' => $templateType,
            'status' => 'sent',
            'recipients' => ['billing@customer.test'],
        ])->forceFill(['created_at' => now()->subHours(2 - $hoursAgo)])->save();
    }

    $this->actingAs($invoice->organization->owner)
        ->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page
            ->where('invoice.email_deliveries.0.template_label', 'Email')
            ->where('invoice.email_deliveries.1.template_label', 'Invoice Reminder')
            ->etc());
});

it('offers a reminder only on a sent invoice that is still owed', function (array $attributes, bool $expected) {
    $invoice = sentInvoiceOwing($attributes);

    expect($invoice->canBeReminded())->toBe($expected);

    $this->actingAs($invoice->organization->owner)
        ->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page->where('invoice.can_remind', $expected)->etc());
})->with([
    'sent and unpaid' => [[], true],
    'partially paid' => [['status' => InvoiceStatus::PARTIALLY_PAID, 'amount_paid' => 5000], true],
    'draft' => [['status' => InvoiceStatus::DRAFT], false],
    'paid' => [['status' => InvoiceStatus::PAID, 'amount_paid' => 11800], false],
    'void' => [['status' => InvoiceStatus::VOID], false],
    'estimate' => [['type' => 'estimate'], false],
]);

it('asks for the balance still owed, not the invoice total', function () {
    $invoice = sentInvoiceOwing(['status' => InvoiceStatus::PARTIALLY_PAID, 'amount_paid' => 5000]);

    expect(TemplateVariableResolver::resolve($invoice)['{{balance_due}}'])->toContain('68.00');

    foreach ([EmailTemplateType::InvoiceReminder, EmailTemplateType::InvoiceOverdue] as $type) {
        $body = app(EmailTemplateService::class)->render($invoice, $type)['body'];

        expect($body)->toContain('68.00')->not->toContain('118.00');
    }
});

it('offers the reminder on the edit page only when the invoice can be reminded about', function () {
    $owed = sentInvoiceOwing();
    $draft = createInvoiceWithItems(['status' => InvoiceStatus::DRAFT], null, $owed->organization, $owed->customer);

    $this->actingAs($owed->organization->owner)
        ->get("/invoices/{$owed->id}/edit")
        ->assertInertia(fn ($page) => $page
            ->where('reminder.template_type', 'invoice_overdue')
            ->where('reminder.customer', null)
            ->etc());

    $this->get("/invoices/{$draft->id}/edit")
        ->assertInertia(fn ($page) => $page->where('reminder', null)->etc());
});
