<?php

use App\Mail\DocumentMailer;
use App\ValueObjects\ContactCollection;

/**
 * The public-view link must appear exactly once in a document email.
 *
 * It used to be emitted twice: once from an anchor baked into every default
 * template body, and once from the CTA button emails.custom-document renders
 * beneath the body. Only the button is wired to the view-online toggle, so
 * switching the CTA off still left a link in the email.
 *
 * The existing URL tests assert with toContain(), which a duplicate satisfies —
 * these count occurrences instead.
 */
$renderEmail = function (string $type, string $number, bool $includeButton = true): string {
    $invoice = createInvoiceWithItems([
        'type' => $type,
        'invoice_number' => $number,
        'status' => $type === 'invoice' ? 'sent' : 'draft',
    ]);

    return (new DocumentMailer(
        $invoice,
        new ContactCollection([['name' => 'Test', 'email' => 'test@example.test']]),
        includeViewOnlineButton: $includeButton,
    ))->renderHtml();
};

test('invoice email renders the view online CTA exactly once', function () use ($renderEmail) {
    $html = $renderEmail('invoice', 'INV-CTA-001');

    expect(substr_count($html, 'View Invoice Online'))->toBe(1)
        ->and(substr_count($html, '/invoices/view/'))->toBe(1);
});

test('estimate email renders the view online CTA exactly once', function () use ($renderEmail) {
    $html = $renderEmail('estimate', 'EST-CTA-001');

    expect(substr_count($html, 'View Estimate Online'))->toBe(1)
        ->and(substr_count($html, '/estimates/view/'))->toBe(1);
});

test('disabling the view online button removes every document link', function () use ($renderEmail) {
    $html = $renderEmail('invoice', 'INV-CTA-002', includeButton: false);

    expect(substr_count($html, 'View Invoice Online'))->toBe(0)
        ->and(substr_count($html, '/invoices/view/'))->toBe(0);
});
