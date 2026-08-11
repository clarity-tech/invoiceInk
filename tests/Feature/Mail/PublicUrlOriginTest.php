<?php

use App\Enums\EmailTemplateType;
use App\Mail\DocumentMailer;
use App\Mail\Templates\EmailTemplateService;
use App\ValueObjects\ContactCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Absolute URLs that reach customers must be rooted at the public domain.
 *
 * UrlGenerator takes its root from the incoming request, and APP_URL is only
 * consulted for console requests. In production the mailable is rendered on the
 * Cloud Run *worker*, a separate service that receives queued jobs as an HTTP
 * POST to /handle-task on its own *.run.app hostname — so an emailed link built
 * there used to point at an internal host no customer can reach.
 *
 * These assertions are host-explicit on purpose. Asserting on the '/invoices/
 * view/' path alone passes under any hostname, which is why the defect survived
 * the existing coverage.
 */

/** The host Cloud Tasks delivers jobs to in production. */
const WORKER_HOST = 'invoicing-worker-abc123xyz0-el.a.run.app';

/** Put the URL generator in the state a queued job sees on the worker. */
$actAsQueueWorker = function (): void {
    URL::setRequest(Request::create('https://'.WORKER_HOST.'/handle-task', 'POST'));
};

$renderEmail = function (string $type, string $number): string {
    $invoice = createInvoiceWithItems([
        'type' => $type,
        'invoice_number' => $number,
        'status' => $type === 'invoice' ? 'sent' : 'draft',
    ]);

    return (new DocumentMailer(
        $invoice,
        new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]),
    ))->renderHtml();
};

test('the invoice CTA uses the public app url when rendered on the queue worker', function () use ($renderEmail, $actAsQueueWorker) {
    $actAsQueueWorker();

    $html = $renderEmail('invoice', 'INV-URL-ORIGIN-001');

    expect($html)->not->toContain(WORKER_HOST)
        ->and($html)->toContain(rtrim(config('app.url'), '/').'/invoices/view/');
});

test('the estimate CTA uses the public app url when rendered on the queue worker', function () use ($renderEmail, $actAsQueueWorker) {
    $actAsQueueWorker();

    $html = $renderEmail('estimate', 'EST-URL-ORIGIN-001');

    expect($html)->not->toContain(WORKER_HOST)
        ->and($html)->toContain(rtrim(config('app.url'), '/').'/estimates/view/');
});

test('view_url in a customized template uses the public app url on the worker', function () use ($actAsQueueWorker) {
    // {{view_url}} stays available to organizations that want the link inline,
    // so it has to survive the worker's hostname too.
    $invoice = createInvoiceWithItems([
        'type' => 'invoice',
        'invoice_number' => 'INV-URL-ORIGIN-002',
        'status' => 'sent',
    ]);

    app(EmailTemplateService::class)->save(
        $invoice->organization,
        EmailTemplateType::InvoiceInitial,
        'Invoice {{invoice_number}}',
        '<p><a href="{{view_url}}">View Invoice Online</a></p>',
    );

    $actAsQueueWorker();

    $body = app(EmailTemplateService::class)
        ->render($invoice, EmailTemplateType::InvoiceInitial)['body'];

    expect($body)->not->toContain(WORKER_HOST)
        ->and($body)->toContain(rtrim(config('app.url'), '/').'/invoices/view/');
});

test('signed team invitation links use the public app url on the worker', function () use ($actAsQueueWorker) {
    // Same failure mode, worse consequence: the signature covers the host, so a
    // recipient cannot repair the link by editing the domain by hand.
    $actAsQueueWorker();

    $url = URL::signedRoute('team-invitations.accept', ['invitation' => 1]);

    expect($url)->not->toContain(WORKER_HOST)
        ->and($url)->toStartWith(rtrim(config('app.url'), '/'));
});
