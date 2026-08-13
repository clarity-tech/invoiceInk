<?php

use App\Enums\EmailDeliveryStatus;
use App\Enums\InvoiceStatus;
use App\Jobs\SendDocumentEmail;
use App\Mail\DocumentMailer;
use App\Models\EmailDelivery;
use App\ValueObjects\ContactCollection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

/**
 * The behaviour this replaces: the controller queued a mailable and immediately
 * marked the invoice SENT with a "sent successfully" flash. Nothing had actually
 * been sent, and a later failure left the invoice still claiming it had.
 */
function sendInvoiceEmail($invoice, array $overrides = []): TestResponse
{
    return test()->post("/invoices/{$invoice->id}/send-email", array_merge([
        'recipients' => ['billing@customer.test'],
        'subject' => 'Invoice',
        'body' => 'Please find attached.',
    ], $overrides));
}

it('records a queued delivery instead of sending inline', function () {
    Bus::fake();

    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems(['status' => 'draft'], null, $organization, $customer);

    $this->actingAs($organization->owner);

    sendInvoiceEmail($invoice)->assertRedirect();

    $delivery = EmailDelivery::where('invoice_id', $invoice->id)->sole();

    expect($delivery->status)->toBe(EmailDeliveryStatus::QUEUED)
        ->and($delivery->recipients)->toBe(['billing@customer.test'])
        ->and($delivery->queued_at)->not->toBeNull();

    Bus::assertDispatched(SendDocumentEmail::class);
});

it('leaves the invoice a draft until the mail is actually away', function () {
    Bus::fake();

    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems(['status' => 'draft'], null, $organization, $customer);

    $this->actingAs($organization->owner);

    sendInvoiceEmail($invoice);

    // The regression: this used to flip to SENT the instant the job was queued.
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::DRAFT);
});

it('promotes a draft to sent once the job succeeds', function () {
    Mail::fake();

    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems(['status' => 'draft'], null, $organization, $customer);

    $delivery = EmailDelivery::create([
        'organization_id' => $organization->id,
        'invoice_id' => $invoice->id,
        'mailable_type' => DocumentMailer::class,
        'mode' => 'platform',
        'status' => EmailDeliveryStatus::QUEUED,
        'recipients' => ['billing@customer.test'],
        'queued_at' => now(),
    ]);

    $mailable = new DocumentMailer($invoice, new ContactCollection([
        ['name' => '', 'email' => 'billing@customer.test'],
    ]));

    (new SendDocumentEmail($delivery, $mailable))->handle();

    expect($delivery->fresh()->status)->toBe(EmailDeliveryStatus::SENT)
        ->and($delivery->fresh()->sent_at)->not->toBeNull()
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::SENT);
});

it('marks the delivery failed and does not promote the invoice', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems(['status' => 'draft'], null, $organization, $customer);

    $delivery = EmailDelivery::factory()->create([
        'organization_id' => $organization->id,
        'invoice_id' => $invoice->id,
    ]);

    $mailable = new DocumentMailer($invoice, new ContactCollection([
        ['name' => '', 'email' => 'billing@customer.test'],
    ]));

    // failed() is the queue's terminal hook, called once retries are exhausted.
    (new SendDocumentEmail($delivery, $mailable))
        ->failed(new RuntimeException('SMTP authentication failed'));

    expect($delivery->fresh()->status)->toBe(EmailDeliveryStatus::FAILED)
        ->and($delivery->fresh()->error)->toContain('SMTP authentication failed')
        ->and($delivery->fresh()->failed_at)->not->toBeNull()
        // The whole point: a failed send must not leave the invoice reading SENT.
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::DRAFT);
});

it('does not regress a paid invoice to sent on a resend', function () {
    Mail::fake();

    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems(['status' => 'paid'], null, $organization, $customer);

    $delivery = EmailDelivery::factory()->create([
        'organization_id' => $organization->id,
        'invoice_id' => $invoice->id,
    ]);

    $mailable = new DocumentMailer($invoice, new ContactCollection([
        ['name' => '', 'email' => 'billing@customer.test'],
    ]));

    (new SendDocumentEmail($delivery, $mailable))->handle();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);
});

it('truncates a very long driver error rather than storing it whole', function () {
    $delivery = EmailDelivery::factory()->create();

    $delivery->markFailed(str_repeat('x', 5000));

    expect(mb_strlen($delivery->fresh()->error))->toBe(1000);
});

it('exposes the latest delivery to the invoice listing', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $invoice = createInvoiceWithItems(['status' => 'draft'], null, $organization, $customer);

    EmailDelivery::factory()->failed()->create([
        'organization_id' => $organization->id,
        'invoice_id' => $invoice->id,
        'created_at' => now()->subHour(),
    ]);
    EmailDelivery::factory()->sent()->create([
        'organization_id' => $organization->id,
        'invoice_id' => $invoice->id,
        'created_at' => now(),
    ]);

    $this->actingAs($organization->owner);

    $this->get('/invoices')
        ->assertInertia(fn ($page) => $page
            // latestOfMany(), so the newer success wins over the older failure.
            ->where('invoices.data.0.latest_email_delivery.status', 'sent')
        );
});

it('does not leak deliveries across organizations', function () {
    $organization = createOrganizationWithLocation();
    $other = createOrganizationWithLocation();

    EmailDelivery::factory()->create(['organization_id' => $other->id]);

    $this->actingAs($organization->owner);

    expect(EmailDelivery::count())->toBe(0);
});
