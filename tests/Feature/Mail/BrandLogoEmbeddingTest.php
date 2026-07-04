<?php

use App\Mail\DocumentMailer;
use App\Models\User;
use App\ValueObjects\ContactCollection;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['mail.default' => 'array', 'queue.default' => 'sync']);
});

function lastSentMessage()
{
    return Mail::mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
}

test('framework notifications embed the brand mark inline via the global listener', function () {
    config(['branding.logo_external' => false]);

    // Email verification is a stock Laravel notification: it renders the shared
    // markdown header/footer (which reference cid:brandlogo) but never runs the
    // EmbedsBrandLogo mailable trait. The global listener is what makes the CID
    // resolve, so the dark-mode-safe wordmark shows instead of a broken image.
    User::factory()->create()->sendEmailVerificationNotification();

    $email = lastSentMessage();
    $attachmentTypes = collect($email->getAttachments())->map->getContentType()->all();

    expect($email->getHtmlBody())->toContain('cid:'.config('branding.logo_cid'))
        ->and($attachmentTypes)->toContain('image/png');
});

test('the logo listener is a no-op in external mode', function () {
    config(['branding.logo_external' => true]);

    User::factory()->create()->sendEmailVerificationNotification();

    $attachmentTypes = collect(lastSentMessage()->getAttachments())->map->getContentType()->all();

    // External mode references the hosted lockup by URL, so no image rides along.
    expect($attachmentTypes)->not->toContain('image/png');
});

test('the logo listener does not double-embed when a mailable already attached the mark', function () {
    config(['branding.logo_external' => false]);

    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-DEDUP-001']);

    Mail::send(new DocumentMailer($invoice, new ContactCollection([['name' => 'R', 'email' => 'r@test.com']])));

    // DocumentMailer embeds the mark itself; the listener must recognise the
    // existing CID part and leave it alone rather than attaching a second copy.
    $pngCount = collect(lastSentMessage()->getAttachments())
        ->filter(fn ($part) => $part->getContentType() === 'image/png')
        ->count();

    expect($pngCount)->toBe(1);
});
