<?php

use App\Enums\EmailTemplateType;
use App\Mail\DocumentMailer;
use App\Mail\Templates\EmailTemplateService;
use App\Providers\AppServiceProvider;
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

const PUBLIC_URL = 'https://invoiceink.claritytech.io';

/**
 * Put the app in the state a queued job sees on the production worker: running
 * as a Cloud Run service (K_SERVICE is set there), the provider has forced the
 * origin, and the bound request is the Cloud Tasks POST.
 */
$asProductionWorker = function (): void {
    config([
        'app.cloud_run_service' => 'invoicing-worker',
        'app.url' => PUBLIC_URL,
    ]);

    (new AppServiceProvider(app()))->boot();

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

test('the invoice CTA uses the public app url when rendered on the queue worker', function () use ($renderEmail, $asProductionWorker) {
    $asProductionWorker();

    $html = $renderEmail('invoice', 'INV-URL-ORIGIN-001');

    expect($html)->not->toContain(WORKER_HOST)
        ->and($html)->toContain(PUBLIC_URL.'/invoices/view/');
});

test('the estimate CTA uses the public app url when rendered on the queue worker', function () use ($renderEmail, $asProductionWorker) {
    $asProductionWorker();

    $html = $renderEmail('estimate', 'EST-URL-ORIGIN-001');

    expect($html)->not->toContain(WORKER_HOST)
        ->and($html)->toContain(PUBLIC_URL.'/estimates/view/');
});

test('view_url in a customized template uses the public app url on the worker', function () use ($asProductionWorker) {
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

    $asProductionWorker();

    $body = app(EmailTemplateService::class)
        ->render($invoice, EmailTemplateType::InvoiceInitial)['body'];

    expect($body)->not->toContain(WORKER_HOST)
        ->and($body)->toContain(PUBLIC_URL.'/invoices/view/');
});

test('signed team invitation links use the public app url on the worker', function () use ($asProductionWorker) {
    // Same failure mode, worse consequence: the signature covers the host, so a
    // recipient cannot repair the link by editing the domain by hand.
    $asProductionWorker();

    $url = URL::signedRoute('team-invitations.accept', ['invitation' => 1]);

    expect($url)->not->toContain(WORKER_HOST)
        ->and($url)->toStartWith(PUBLIC_URL);
});

test('the origin is left alone when not running on cloud run', function () {
    // Load-bearing. Pest's browser plugin serves the app from its own HTTP
    // server on a random port and calls useOrigin() with it. Forcing the origin
    // unconditionally clobbered that on every app boot, so form posts went to
    // whatever APP_URL pointed at and login/registration bounced back to the
    // form. Off Cloud Run, whoever set the root keeps it.
    $harnessOrigin = 'http://127.0.0.1:9321';

    URL::setRequest(Request::create($harnessOrigin.'/login'));
    URL::useOrigin($harnessOrigin);

    // Deliberately different from the harness origin: without the gate, boot()
    // would overwrite the root with this and the assertion below would fail.
    config(['app.cloud_run_service' => null, 'app.url' => 'http://127.0.0.1:8001']);

    (new AppServiceProvider(app()))->boot();

    expect(route('invoices.public', 'abc'))->toStartWith($harnessOrigin);
});

test('a non-production cloud run service still gets the public origin', function () {
    // Keying on APP_ENV === 'production' would miss this: a staging service has
    // the same split web/worker architecture and so the same wrong-host defect.
    // Keying on "running on Cloud Run" covers every deployment.
    $stagingUrl = 'https://staging.invoiceink.claritytech.io';

    config([
        'app.env' => 'staging',
        'app.cloud_run_service' => 'invoicing-worker-staging',
        'app.url' => $stagingUrl,
    ]);

    (new AppServiceProvider(app()))->boot();

    URL::setRequest(Request::create('https://'.WORKER_HOST.'/handle-task', 'POST'));

    expect(route('invoices.public', 'abc'))->toStartWith($stagingUrl)
        ->and(route('invoices.public', 'abc'))->not->toContain(WORKER_HOST);
});
