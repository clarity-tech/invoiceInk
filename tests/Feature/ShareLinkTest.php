<?php

use App\Enums\EmailTemplateType;
use App\Mail\DocumentMailer;
use App\Mail\Templates\EmailTemplateService;
use App\Services\PdfService;
use App\ValueObjects\ContactCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Share links
|--------------------------------------------------------------------------
|
| A document opens for someone who is not signed in only through a signed
| link that expires. The ULID alone opens nothing, and neither does a link
| that has been altered.
|
*/

/** The link out of an email: the first href to the public view, as a browser reads it. */
function linkInEmail(string $html): string
{
    preg_match('#href="([^"]*/(?:invoices|estimates)/view/[^"]*)"#', $html, $match);

    return html_entity_decode($match[1] ?? '');
}

beforeEach(function () {
    $this->organization = createOrganizationWithLocation([
        'company_name' => 'Clarity Technologies',
        'emails' => new ContactCollection([['name' => 'Accounts', 'email' => 'accounts@clarity.test']]),
    ]);
    $this->customer = createCustomerWithLocation(['name' => 'Sandline Pharmacy LLC'], [], $this->organization);
    $this->invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-SHARE-001',
        'status' => 'sent',
    ], null, $this->organization, $this->customer);
});

test('a link keeps working for 30 days', function () {
    $this->freezeTime();
    $link = $this->invoice->shareUrl();

    $this->travel(30)->days();

    $this->get($link)->assertOk()->assertSee('INV-SHARE-001');
});

test('an expired link says who to ask for a new one, and shows nothing else', function () {
    $this->freezeTime();
    $link = $this->invoice->shareUrl();

    $this->travel(30)->days();
    $this->travel(1)->second();

    $this->get($link)
        ->assertForbidden()
        ->assertSee('This link has expired')
        ->assertSee('INV-SHARE-001')
        ->assertSee('Clarity Technologies')
        ->assertSee('mailto:accounts@clarity.test', escape: false)
        ->assertDontSee('Sandline Pharmacy LLC');
});

test('an altered link is refused without saying whose document it was', function (Closure $alter) {
    $other = createInvoiceWithItems(['invoice_number' => 'INV-SHARE-002'], null, $this->organization, $this->customer);

    $this->get($alter($this->invoice, $other))
        ->assertForbidden()
        ->assertDontSee('This link has expired')
        ->assertDontSee('Clarity Technologies');
})->with([
    'another document in the path' => fn ($invoice, $other) => str_replace($invoice->ulid, $other->ulid, $invoice->shareUrl()),
    'the expiry pushed back' => fn ($invoice) => preg_replace('/expires=\d+/', 'expires='.now()->addYear()->timestamp, $invoice->shareUrl()),
    'no expiry at all' => fn ($invoice) => URL::signedRoute('invoices.public', ['ulid' => $invoice->ulid], absolute: false),
    'no signature' => fn ($invoice) => preg_replace('/&signature=[^&]+/', '', $invoice->shareUrl()),
]);

test('a link still opens with tracking parameters added by whatever shared it', function () {
    $this->get($this->invoice->shareUrl().'&fbclid=IwAR0abc&utm_source=whatsapp')
        ->assertOk()
        ->assertSee('INV-SHARE-001');
});

test('the page tells the recipient when the link they opened stops working', function () {
    $this->travelTo(now()->setDate(2026, 10, 14));

    $this->get($this->invoice->shareUrl(now()->addDays(5)))
        ->assertOk()
        ->assertSee('This link works until 19 Oct 2026.');
});

test('the page offers a PDF that expires with the link it was opened by', function () {
    $this->freezeTime();
    $this->mock(PdfService::class)
        ->shouldReceive('downloadInvoicePdf')
        ->andReturn(response('%PDF-fake', 200, ['Content-Type' => 'application/pdf']));

    $link = $this->invoice->shareUrl(now()->addDays(5));
    $pdfUrl = $this->get($link)->assertOk()->viewData('pdfUrl');

    parse_str(parse_url($link, PHP_URL_QUERY), $linkQuery);
    parse_str(parse_url($pdfUrl, PHP_URL_QUERY), $pdfQuery);
    expect($pdfQuery['expires'])->toBe($linkQuery['expires']);

    $this->get($pdfUrl)->assertOk()->assertHeader('Content-Type', 'application/pdf');

    $this->travel(5)->days();
    $this->travel(1)->second();

    $this->get($pdfUrl)->assertForbidden();
});

test('the emailed link opens the document, and the email says until when', function (string $type) {
    $this->travelTo(now()->setDate(2026, 10, 14));
    $document = createInvoiceWithItems(['type' => $type, 'status' => 'sent'], null, $this->organization, $this->customer);

    $html = (new DocumentMailer(
        $document,
        new ContactCollection([['name' => 'Sandline', 'email' => 'billing@sandline.test']]),
    ))->renderHtml();

    expect($html)->toContain('This link works until 13 Nov 2026.');
    $this->get(linkInEmail($html))->assertOk()->assertSee($document->invoice_number);
})->with(['invoice', 'estimate']);

test('the view_url variable in a customized email opens the document', function () {
    app(EmailTemplateService::class)->save(
        $this->organization,
        EmailTemplateType::InvoiceInitial,
        'Invoice {{invoice_number}}',
        '<p><a href="{{view_url}}">View Invoice Online</a></p>',
    );

    $body = app(EmailTemplateService::class)
        ->render($this->invoice, EmailTemplateType::InvoiceInitial)['body'];

    $this->get(linkInEmail($body))->assertOk()->assertSee('INV-SHARE-001');
});

test('the detail and edit pages hand the signed-in user a working share link and its expiry', function (string $page) {
    $this->freezeTime();
    $this->actingAs($this->organization->owner);

    $shareLink = $this->get(str_replace('{id}', $this->invoice->id, $page))
        ->assertOk()
        ->inertiaProps('shareLink');

    parse_str(parse_url($shareLink['url'], PHP_URL_QUERY), $query);
    // The date the page shows is the one the link enforces, 30 days out.
    expect(Carbon::parse($shareLink['expires_at'])->timestamp)
        ->toBe((int) $query['expires'])
        ->toBe(now()->addDays(30)->timestamp);

    auth()->forgetGuards();

    $this->get($shareLink['url'])->assertOk()->assertSee('INV-SHARE-001');
})->with(['/invoices/{id}', '/invoices/{id}/edit']);

test('the email history says how long the links it sent last', function () {
    $this->actingAs($this->organization->owner);

    $this->get("/invoices/{$this->invoice->id}")
        ->assertInertia(fn ($page) => $page->where('shareLinkDays', 30));
});
