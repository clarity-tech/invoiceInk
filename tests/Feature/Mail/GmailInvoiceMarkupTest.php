<?php

use App\Enums\InvoiceStatus;
use App\Mail\CustomerReminderMail;
use App\Mail\DocumentMailer;
use App\Models\Invoice;
use App\ValueObjects\ContactCollection;
use Illuminate\Database\Eloquent\Collection;

/**
 * @return array<mixed>|null
 */
function gmailMarkupIn(string $html): ?array
{
    $found = preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    expect($found)->toBeLessThanOrEqual(1);

    return $found ? json_decode($matches[1][0], true, flags: JSON_THROW_ON_ERROR) : null;
}

function renderDocumentEmailFor(Invoice $invoice, bool $includeViewOnlineButton = true): string
{
    return (new DocumentMailer(
        $invoice,
        new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]),
        includeViewOnlineButton: $includeViewOnlineButton,
    ))->renderHtml();
}

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 11, 1)->setTime(10, 0));
});

it('tells Gmail what an invoice email still asks to be paid', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation(['name' => 'Globex Corp'], [], $organization);
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-GM-001',
        'order_number' => 'PO-77',
        'status' => InvoiceStatus::PARTIALLY_PAID,
        'currency' => 'USD',
        'total' => 11800,
        'amount_paid' => 1800,
        'due_at' => '2026-11-15',
    ], null, $organization, $customer);

    $html = renderDocumentEmailFor($invoice);
    $markup = gmailMarkupIn($html);
    $amountDue = ['@type' => 'PriceSpecification', 'price' => '100.00', 'priceCurrency' => 'USD'];

    expect($markup)->toMatchArray([
        '@context' => 'http://schema.org',
        '@type' => 'Invoice',
        'accountId' => 'INV-GM-001',
        'provider' => ['@type' => 'Organization', 'name' => 'Test Organization Inc.'],
        'customer' => ['@type' => 'Organization', 'name' => 'Globex Corp'],
        'paymentDue' => '2026-11-15T00:00:00+00:00',
        'paymentStatus' => 'PaymentDue',
        'totalPaymentDue' => $amountDue,
        'minimumPaymentDue' => $amountDue,
        'referencesOrder' => ['@type' => 'Order', 'orderNumber' => 'PO-77'],
    ])
        ->and($markup['potentialAction'])->toBe(['@type' => 'ViewAction', 'url' => $markup['url'], 'name' => 'View invoice'])
        ->and($html)->toContain('href="'.e($markup['url']).'"');
});

it('reports where the invoice stands on payment', function (array $attributes, string $paymentStatus, string $price) {
    $invoice = createInvoiceWithItems(array_merge(['currency' => 'USD', 'total' => 11800], $attributes));

    expect(gmailMarkupIn(renderDocumentEmailFor($invoice)))
        ->paymentStatus->toBe($paymentStatus)
        ->totalPaymentDue->price->toBe($price);
})->with([
    'a draft on its first send' => [['status' => InvoiceStatus::DRAFT, 'due_at' => '2026-11-15'], 'PaymentDue', '118.00'],
    'past its due date' => [['status' => InvoiceStatus::SENT, 'due_at' => '2026-10-20'], 'PaymentPastDue', '118.00'],
    'paid' => [['status' => InvoiceStatus::PAID, 'amount_paid' => 11800, 'due_at' => '2026-10-20'], 'PaymentComplete', '0.00'],
]);

it('leaves out what is not a bill', function (array $attributes) {
    expect(gmailMarkupIn(renderDocumentEmailFor(createInvoiceWithItems($attributes))))->toBeNull();
})->with([
    'an estimate' => [['type' => 'estimate']],
    'a void invoice' => [['status' => InvoiceStatus::VOID]],
]);

it('offers no View button when the email carries no link to the invoice', function () {
    $html = renderDocumentEmailFor(createInvoiceWithItems(['status' => InvoiceStatus::SENT]), includeViewOnlineButton: false);

    expect(gmailMarkupIn($html))->not->toHaveKeys(['url', 'potentialAction'])
        ->and($html)->not->toContain('invoices\/view');
});

it('describes every invoice in a combined reminder', function () {
    $organization = createOrganizationWithLocation();
    $customer = createCustomerWithLocation([], [], $organization);
    $later = createInvoiceWithItems(['invoice_number' => 'REM-GM-LATER', 'status' => InvoiceStatus::SENT, 'due_at' => '2026-11-20'], null, $organization, $customer);
    $earlier = createInvoiceWithItems(['invoice_number' => 'REM-GM-EARLIER', 'status' => InvoiceStatus::SENT, 'due_at' => '2026-10-20'], null, $organization, $customer);

    $html = (new CustomerReminderMail(
        $customer,
        new Collection([$later, $earlier]),
        new ContactCollection([['name' => '', 'email' => 'accounts@customer.test']]),
        'Payment reminder',
        '<p>Please pay.</p>',
    ))->renderHtml();
    $markup = gmailMarkupIn($html);

    expect(array_column($markup, 'accountId'))->toBe(['REM-GM-EARLIER', 'REM-GM-LATER'])
        ->and(array_column($markup, 'paymentStatus'))->toBe(['PaymentPastDue', 'PaymentDue'])
        ->and($html)->toContain('href="'.e($markup[0]['url']).'"', 'href="'.e($markup[1]['url']).'"');
});

it('keeps a name typed into the app from breaking out of the markup', function () {
    $name = 'Acme</script><script>alert(1)</script>';
    $organization = createOrganizationWithLocation();
    $invoice = createInvoiceWithItems(
        ['status' => InvoiceStatus::SENT],
        null,
        $organization,
        createCustomerWithLocation(['name' => $name], [], $organization),
    );

    $html = renderDocumentEmailFor($invoice);

    expect(substr_count($html, '</script>'))->toBe(1)
        ->and(gmailMarkupIn($html)['customer']['name'])->toBe($name);
});
