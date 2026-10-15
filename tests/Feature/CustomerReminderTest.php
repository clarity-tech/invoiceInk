<?php

use App\Currency;
use App\Enums\EmailDeliveryStatus;
use App\Enums\EmailTemplateType;
use App\Enums\InvoiceStatus;
use App\Jobs\SendCustomerReminder;
use App\Mail\CustomerReminderMail;
use App\Mail\Templates\EmailTemplateService;
use App\Models\Customer;
use App\Models\EmailDelivery;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\ValueObjects\ContactCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

/**
 * @return array{0: Organization, 1: Customer}
 */
function customerWithOrganization(array $customerAttributes = []): array
{
    $organization = createOrganizationWithLocation();

    return [$organization, createCustomerWithLocation($customerAttributes, [], $organization)];
}

function owedInvoice(Organization $organization, Customer $customer, array $attributes = []): Invoice
{
    return createInvoiceWithItems(array_merge([
        'status' => InvoiceStatus::SENT,
        'due_at' => now()->subDays(5),
    ], $attributes), null, $organization, $customer);
}

/**
 * @param  array<int, int>  $invoiceIds
 */
function postCustomerReminder(User $user, Customer $customer, array $invoiceIds, array $overrides = []): TestResponse
{
    return test()->actingAs($user)
        ->from("/customers/{$customer->id}")
        ->post("/customers/{$customer->id}/send-reminder", array_merge([
            'recipients' => ['accounts@customer.test'],
            'subject' => 'Payment reminder',
            'body' => '<p>Please pay.</p>',
            'invoice_ids' => $invoiceIds,
        ], $overrides));
}

it('queues one reminder for the selected invoices and records it on each', function () {
    Bus::fake();
    [$organization, $customer] = customerWithOrganization();
    $first = owedInvoice($organization, $customer);
    $second = owedInvoice($organization, $customer);
    owedInvoice($organization, $customer);

    postCustomerReminder($organization->owner, $customer, [$first->id, $second->id])
        ->assertRedirect("/customers/{$customer->id}")
        ->assertSessionHas('success', 'Reminder queued for sending.');

    $deliveries = EmailDelivery::orderBy('invoice_id')->get();

    expect($deliveries->pluck('invoice_id')->all())->toBe([$first->id, $second->id])
        ->and($deliveries->pluck('batch_id')->unique())->toHaveCount(1)
        ->and($deliveries->first()->batch_id)->not->toBeNull()
        ->and($deliveries->pluck('template_type')->unique()->all())->toBe([EmailTemplateType::CustomerReminder])
        ->and($deliveries->first()->recipients)->toBe(['accounts@customer.test']);

    Bus::assertDispatched(SendCustomerReminder::class, fn (SendCustomerReminder $job) => $job->deliveries->count() === 2);
});

it('lists only the invoices sent and totals them per currency', function () {
    Mail::fake();
    [$organization, $customer] = customerWithOrganization();
    $unpaid = owedInvoice($organization, $customer, ['invoice_number' => 'REM-A', 'total' => 11800]);
    $partial = owedInvoice($organization, $customer, [
        'invoice_number' => 'REM-B',
        'status' => InvoiceStatus::PARTIALLY_PAID,
        'total' => 20000,
        'amount_paid' => 5000,
    ]);
    $dollars = owedInvoice($organization, $customer, ['invoice_number' => 'REM-C', 'currency' => 'USD', 'total' => 11800]);
    owedInvoice($organization, $customer, ['invoice_number' => 'REM-NOT-SELECTED']);

    postCustomerReminder($organization->owner, $customer, [$unpaid->id, $partial->id, $dollars->id])
        ->assertSessionHasNoErrors();

    Mail::assertSent(CustomerReminderMail::class, function (CustomerReminderMail $mail) {
        $html = $mail->renderHtml();

        expect($html)
            ->toContain('REM-A', 'REM-B', 'REM-C')
            ->not->toContain('REM-NOT-SELECTED')
            ->toContain('Total due (INR)', Currency::INR->formatAmount(11800 + 15000))
            ->toContain('Total due (USD)', Currency::USD->formatAmount(11800));

        return true;
    });

    expect(EmailDelivery::pluck('status')->unique()->all())->toBe([EmailDeliveryStatus::SENT]);
});

it('names the invoices sent, oldest due first, wherever the message asks for them', function () {
    Mail::fake();
    [$organization, $customer] = customerWithOrganization();
    $later = owedInvoice($organization, $customer, ['invoice_number' => 'REM-LATER', 'due_at' => now()->subDays(2)]);
    $earlier = owedInvoice($organization, $customer, ['invoice_number' => 'REM-EARLIER', 'due_at' => now()->subDays(20)]);
    owedInvoice($organization, $customer, ['invoice_number' => 'REM-LEFT-OUT']);

    postCustomerReminder($organization->owner, $customer, [$later->id, $earlier->id], [
        'subject' => 'Payment reminder: {{invoice_numbers}}',
        'body' => '<p>About {{invoice_numbers}}.</p>',
    ])->assertSessionHasNoErrors();

    Mail::assertSent(CustomerReminderMail::class, function (CustomerReminderMail $mail) {
        expect($mail->envelope()->subject)->toStartWith('Payment reminder: REM-EARLIER, REM-LATER')
            ->and($mail->renderHtml())->toContain('About REM-EARLIER, REM-LATER.')
            ->not->toContain('{{invoice_numbers}}', 'REM-LEFT-OUT');

        return true;
    });
});

it('refuses an invoice that cannot be reminded about', function (Closure $makeInvoice) {
    Bus::fake();
    [$organization, $customer] = customerWithOrganization();
    $owed = owedInvoice($organization, $customer);
    $refused = $makeInvoice($organization, $customer);

    postCustomerReminder($organization->owner, $customer, [$owed->id, $refused->id])
        ->assertSessionHasErrors(['invoice_ids' => 'Some of the selected invoices are no longer owed. Reload the page and try again.']);

    expect(EmailDelivery::count())->toBe(0);
    Bus::assertNotDispatched(SendCustomerReminder::class);
})->with([
    'paid' => fn ($org, $customer) => owedInvoice($org, $customer, ['status' => InvoiceStatus::PAID, 'amount_paid' => 11800]),
    'draft' => fn ($org, $customer) => owedInvoice($org, $customer, ['status' => InvoiceStatus::DRAFT]),
    'void' => fn ($org, $customer) => owedInvoice($org, $customer, ['status' => InvoiceStatus::VOID]),
    'estimate' => fn ($org, $customer) => owedInvoice($org, $customer, ['type' => 'estimate']),
    'another customer' => fn ($org) => owedInvoice($org, createCustomerWithLocation([], [], $org)),
    'another organization' => function () {
        [$otherOrganization, $otherCustomer] = customerWithOrganization();

        return owedInvoice($otherOrganization, $otherCustomer);
    },
]);

it('does not reveal another organization\'s customer', function () {
    [$organization, $customer] = customerWithOrganization();
    $invoice = owedInvoice($organization, $customer);
    $outsider = createOrganizationWithLocation();

    postCustomerReminder($outsider->owner, $customer, [$invoice->id])->assertNotFound();
});

it('refuses a member who cannot send invoices', function () {
    Bus::fake();
    [$organization, $customer] = customerWithOrganization();
    $invoice = owedInvoice($organization, $customer);
    $viewer = User::factory()->create();
    $organization->users()->attach($viewer, ['role' => 'viewer']);
    $viewer->switchTeam($organization);

    postCustomerReminder($viewer, $customer, [$invoice->id])->assertForbidden();

    expect(EmailDelivery::count())->toBe(0);
});

it('requires recipients, a subject, a body and at least one invoice', function () {
    [$organization, $customer] = customerWithOrganization();

    $this->actingAs($organization->owner)
        ->post("/customers/{$customer->id}/send-reminder", [])
        ->assertSessionHasErrors(['recipients', 'subject', 'body', 'invoice_ids']);
});

it('records the outcome on every invoice the email covered', function () {
    Mail::fake();
    [$organization, $customer] = customerWithOrganization();
    $invoices = collect([owedInvoice($organization, $customer), owedInvoice($organization, $customer)]);
    $deliveries = new Collection($invoices->map(fn (Invoice $invoice) => EmailDelivery::create([
        'organization_id' => $organization->id,
        'invoice_id' => $invoice->id,
        'mailable_type' => CustomerReminderMail::class,
        'status' => EmailDeliveryStatus::QUEUED,
        'recipients' => ['accounts@customer.test'],
    ]))->all());
    $job = new SendCustomerReminder($deliveries, new CustomerReminderMail(
        $customer,
        new Collection($invoices->all()),
        new ContactCollection([['name' => '', 'email' => 'accounts@customer.test']]),
        'Payment reminder',
        '<p>Please pay.</p>',
    ));

    $job->handle();
    expect(EmailDelivery::pluck('status')->unique()->all())->toBe([EmailDeliveryStatus::SENT]);

    $job->failed(new RuntimeException('Mailbox unavailable'));
    expect(EmailDelivery::pluck('status')->unique()->all())->toBe([EmailDeliveryStatus::FAILED])
        ->and(EmailDelivery::pluck('error')->unique()->all())->toBe(['Mailbox unavailable']);
});

it('escapes what customers and admins type into the email', function () {
    [$organization, $customer] = customerWithOrganization(['name' => 'Acme <script>alert(1)</script>']);
    $invoice = owedInvoice($organization, $customer, ['invoice_number' => 'INV-<b>1</b>']);

    $draft = app(EmailTemplateService::class)->renderForCustomer($customer, EmailTemplateType::CustomerReminder);
    $html = (new CustomerReminderMail(
        $customer,
        new Collection([$invoice]),
        new ContactCollection([['name' => '', 'email' => 'accounts@customer.test']]),
        $draft['subject'],
        $draft['body'],
    ))->renderHtml();

    expect($draft['body'])->toContain('&lt;script&gt;')->not->toContain('<script>alert(1)</script>')
        ->and($html)->toContain('INV-&lt;b&gt;1&lt;/b&gt;')->not->toContain('INV-<b>1</b>');
});

it('never sends a document variable as literal braces', function () {
    [$organization, $customer] = customerWithOrganization();
    app(EmailTemplateService::class)->save(
        $organization,
        EmailTemplateType::CustomerReminder,
        'About {{invoice_number}}',
        '<p>Hi {{customer_name}}, about {{amount_due}}.</p>',
    );

    $draft = app(EmailTemplateService::class)->renderForCustomer($customer, EmailTemplateType::CustomerReminder);

    expect($draft['subject'].$draft['body'])->not->toContain('{{')
        ->and($draft['body'])->toContain($customer->name);
});

it('shows a customer page what is still owed, oldest due first', function () {
    [$organization, $customer] = customerWithOrganization();
    $later = owedInvoice($organization, $customer, ['due_at' => now()->subDays(2)]);
    $earlier = owedInvoice($organization, $customer, ['due_at' => now()->subDays(20)]);
    owedInvoice($organization, $customer, ['status' => InvoiceStatus::PAID, 'amount_paid' => 11800]);

    $this->actingAs($organization->owner)
        ->get("/customers/{$customer->id}")
        ->assertInertia(fn ($page) => $page
            ->where('reminder.invoices.0.id', $earlier->id)
            ->where('reminder.invoices.1.id', $later->id)
            ->has('reminder.invoices', 2)
            ->where('reminder.subject', 'Payment reminder: {{invoice_numbers}}')
            ->etc());
});

it('offers no reminder for a customer who owes nothing', function () {
    [$organization, $customer] = customerWithOrganization();

    $this->actingAs($organization->owner)
        ->get("/customers/{$customer->id}")
        ->assertInertia(fn ($page) => $page->where('reminder', null)->etc());
});

it('offers a combined reminder on an invoice only when the customer owes on others too', function () {
    [$organization, $customer] = customerWithOrganization();
    $invoice = owedInvoice($organization, $customer);

    $this->actingAs($organization->owner)
        ->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page->where('customerReminder', null)->etc());

    owedInvoice($organization, $customer);

    $this->actingAs($organization->owner)
        ->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page->has('customerReminder.invoices', 2)->etc());
});
