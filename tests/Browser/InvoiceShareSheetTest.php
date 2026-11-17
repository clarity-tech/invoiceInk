<?php

use App\Models\Invoice;
use App\Models\User;

/**
 * On a phone the document's link goes out through the system share sheet
 * rather than the clipboard; see DocumentOpenActions.
 *
 * Neither headless Chromium nor WebKit has navigator.share, so the test puts
 * in a stand-in that records what it was handed. The button decides between
 * Share and Copy link once, as it is set up, so the stand-in has to exist
 * before the document renders: it goes in on the list, and the document is
 * opened from there with a client-side visit -- the way it is usually
 * reached anyway. A full page load would throw the stand-in away.
 */
const NATIVE_SHARE_STUB = <<<'JS'
function () {
    window.__shared = [];
    Object.defineProperty(navigator, 'share', {
        configurable: true,
        value: function (data) {
            window.__shared.push(JSON.parse(JSON.stringify(data)));

            return Promise.resolve();
        },
    });
    Object.defineProperty(navigator, 'canShare', {
        configurable: true,
        value: function () {
            return true;
        },
    });

    return true;
}
JS;

/**
 * What the share sheet was handed, and what the sharer was told. The date the
 * link expires is read from the signed URL itself, so the message is checked
 * against the link rather than against the component's own arithmetic.
 */
const NATIVE_SHARE_RESULT = <<<'JS'
new Promise(function (resolve) {
    const started = Date.now();
    (function check() {
        const status = document.querySelector('[role="status"]');
        const message = status ? status.textContent.trim() : '';
        if (!message && Date.now() - started < 4000) {
            setTimeout(check, 50);

            return;
        }

        const viewPublic = Array.from(document.querySelectorAll('a')).find(function (a) {
            return a.textContent.trim() === 'View public';
        });
        const publicUrl = viewPublic.getAttribute('href');
        const expires = new URL(publicUrl).searchParams.get('expires');
        const rect = status.getBoundingClientRect();
        const root = document.documentElement;

        resolve({
            shared: window.__shared,
            publicUrl: publicUrl,
            expiresOn: new Date(expires * 1000).toLocaleDateString('en-IN', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            }),
            message: message,
            messageFits: rect.left >= 0 && rect.right <= window.innerWidth,
            overflow: root.scrollWidth - root.clientWidth,
        });
    })();
})
JS;

/** Waits for a client-side visit to reach the page, then lists its controls. */
function nativeShareControlsOn(string $path): string
{
    return sprintf(<<<'JS'
        new Promise(function (resolve) {
            const started = Date.now();
            (function check() {
                const labels = Array.from(document.querySelectorAll('button, a')).map(function (el) {
                    return el.textContent.trim().replace(/\s+/g, ' ');
                });
                const arrived = location.pathname === %s && labels.indexOf('View public') !== -1;
                if (arrived || Date.now() - started > 4000) {
                    resolve({ path: location.pathname, labels: labels });
                } else {
                    setTimeout(check, 50);
                }
            })();
        })
        JS, json_encode($path));
}

/**
 * @return array{0: User, 1: Invoice}
 */
function nativeShareInvoice(): array
{
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'share-sheet@example.test',
    ]);

    $org = createOrganizationWithLocation([
        'company_name' => 'Clarity Technologies',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    $customer = createCustomerWithLocation(['name' => 'Sandline Pharmacy LLC'], [], $org);

    $invoice = createInvoiceWithItems([
        'invoice_number' => 'CT-INV-SHARE',
        'status' => 'sent',
        'issued_at' => '2026-12-16',
        'due_at' => '2027-01-15',
        'subtotal' => 10000000,
        'tax' => 1800000,
        'total' => 11800000,
    ], [
        // Basis points: 1800 is 18%.
        ['description' => 'Monthly retainer', 'quantity' => 1, 'unit_price' => 10000000, 'tax_rate' => 1800],
    ], $org, $customer);

    return [$user, $invoice];
}

it('shares the invoice from a phone with its number, sender, total and due date', function () {
    [$user, $invoice] = nativeShareInvoice();
    $this->actingAs($user);

    $page = visit('/invoices')->on()->mobile()->inLightMode();
    $page->script(NATIVE_SHARE_STUB);
    $page->click("[aria-label=\"Open invoice {$invoice->invoice_number}\"]");

    $controls = $page->script(nativeShareControlsOn("/invoices/{$invoice->id}"));
    expect($controls['path'])->toBe("/invoices/{$invoice->id}");
    expect($controls['labels'])->toContain('Share')->not->toContain('Copy link');

    $page->click('button:text-is("Share")');
    $result = $page->script(NATIVE_SHARE_RESULT);

    expect($result['shared'])->toHaveCount(1);
    expect($result['shared'][0])->toEqual([
        'title' => 'Invoice CT-INV-SHARE from Clarity Technologies',
        'text' => 'Invoice CT-INV-SHARE from Clarity Technologies for ₹1,18,000.00, due 15 Jan 2027.',
        // The same signed link View public opens; the page cannot mint its own.
        'url' => $result['publicUrl'],
    ]);
    expect($result['publicUrl'])->toContain("/invoices/view/{$invoice->ulid}?");

    // The expiry is the sharer's to pass on, so it is said to them here.
    expect($result['message'])->toBe("Link shared. It works until {$result['expiresOn']}.");

    // That message is the only place a phone shows the expiry, so it must be
    // on screen, not cut off at the edge.
    expect($result['messageFits'])->toBeTrue('the status message runs off the screen');
    expect($result['overflow'])->toBeLessThanOrEqual(1);
});

it('offers the same share on the edit page', function () {
    [$user, $invoice] = nativeShareInvoice();
    $this->actingAs($user);

    $page = visit('/invoices')->on()->mobile()->inLightMode();
    $page->script(NATIVE_SHARE_STUB);
    $page->click("[aria-label=\"Open invoice {$invoice->invoice_number}\"]");
    $page->script(nativeShareControlsOn("/invoices/{$invoice->id}"));

    $page->click("a[href=\"/invoices/{$invoice->id}/edit\"]");
    $controls = $page->script(nativeShareControlsOn("/invoices/{$invoice->id}/edit"));
    expect($controls['path'])->toBe("/invoices/{$invoice->id}/edit");
    expect($controls['labels'])->toContain('Share')->not->toContain('Copy link');

    $page->click('button:text-is("Share")');
    $result = $page->script(NATIVE_SHARE_RESULT);

    expect($result['shared'])->toHaveCount(1);
    expect($result['shared'][0]['url'])->toBe($result['publicUrl']);
    expect($result['shared'][0]['text'])->toBe(
        'Invoice CT-INV-SHARE from Clarity Technologies for ₹1,18,000.00, due 15 Jan 2027.',
    );
    expect($result['message'])->toBe("Link shared. It works until {$result['expiresOn']}.");
    expect($result['messageFits'])->toBeTrue('the status message runs off the screen');
    expect($result['overflow'])->toBeLessThanOrEqual(1);
});

// The same stand-in, so the browser can share: under a mouse the pointer is
// what keeps Copy link, not a missing API.
it('keeps Copy link at a desk even where the browser can share', function () {
    [$user, $invoice] = nativeShareInvoice();
    $this->actingAs($user);

    $page = visit('/invoices')->resize(1440, 900);
    $page->script(NATIVE_SHARE_STUB);
    $page->click("[aria-label=\"Open invoice {$invoice->invoice_number}\"]");

    $controls = $page->script(nativeShareControlsOn("/invoices/{$invoice->id}"));
    expect($controls['path'])->toBe("/invoices/{$invoice->id}");
    expect($controls['labels'])->toContain('Copy link')->not->toContain('Share');
});
