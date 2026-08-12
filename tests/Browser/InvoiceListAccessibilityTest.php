<?php

/**
 * Accessibility regression guard for the invoice/estimate list.
 *
 * These pin properties that are invisible in a screenshot and silent when they
 * break: contrast ratios, accessible names, table semantics and focus order.
 * `assertSee` cannot catch any of them.
 *
 * Contrast is measured from resolved sRGB rather than asserted against class
 * names, because Tailwind v4 emits oklch() and the ratio is what the standard
 * actually requires.
 */

use App\Models\User;

/**
 * WCAG 2.1 AA: text below 18px (or 14px bold) needs 4.5:1 against its
 * background. Everything measured here is 14px regular.
 */
const AA_NORMAL_TEXT = 4.5;

/** Resolves any CSS colour syntax to sRGB via canvas, then computes contrast. */
const CONTRAST_JS = <<<'JS'
function () {
    const cv = document.createElement('canvas');
    cv.width = cv.height = 1;
    const ctx = cv.getContext('2d', { willReadFrequently: true });

    function toRgb(value) {
        if (!value || /rgba?\([^)]*,\s*0\s*\)$/.test(value)) return null;
        ctx.clearRect(0, 0, 1, 1);
        ctx.fillStyle = '#000';
        ctx.fillStyle = value;
        ctx.fillRect(0, 0, 1, 1);
        const d = ctx.getImageData(0, 0, 1, 1).data;
        return d[3] === 0 ? null : [d[0], d[1], d[2]];
    }

    function luminance(c) {
        const [r, g, b] = c.map(v => {
            v = v / 255;
            return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * r + 0.7152 * g + 0.0722 * b;
    }

    function contrast(fg, bg) {
        if (!fg || !bg) return null;
        const a = luminance(fg), b = luminance(bg);
        const [hi, lo] = a > b ? [a, b] : [b, a];
        return Math.round(((hi + 0.05) / (lo + 0.05)) * 100) / 100;
    }

    // Walk up for the first painted background — the element's own is often
    // transparent, and the effective backdrop is what the ratio is against.
    function backdrop(el) {
        for (let n = el; n; n = n.parentElement) {
            const bg = toRgb(getComputedStyle(n).backgroundColor);
            if (bg) return bg;
        }
        return [255, 255, 255];
    }

    const controls = Array.from(document.querySelectorAll('a, button'));
    const byText = t => controls.find(el => el.textContent.trim() === t);
    const out = {};

    const view = byText('View');
    if (view) out.view_link = contrast(toRgb(getComputedStyle(view).color), backdrop(view));

    const create = byText('Create Estimate');
    if (create) {
        const cs = getComputedStyle(create);
        out.create_estimate = contrast(toRgb(cs.color), toRgb(cs.backgroundColor));
    }

    const tab = byText('Estimates');
    if (tab) out.inactive_tab = contrast(toRgb(getComputedStyle(tab).color), backdrop(tab));

    return out;
}
JS;

/** Reports horizontal overflow of the document, ignoring intended scrollers. */
const DOC_OVERFLOW_JS = <<<'JS'
function () {
    return document.documentElement.scrollWidth - document.documentElement.clientWidth;
}
JS;

it('meets AA contrast on the listing controls', function () {
    [$user] = seedInvoiceListFixture();
    $this->actingAs($user);

    $ratios = visit('/invoices')->resize(1440, 900)->script(CONTRAST_JS);

    expect($ratios)->toHaveKeys(['view_link', 'create_estimate', 'inactive_tab']);

    foreach ($ratios as $element => $ratio) {
        expect($ratio)->toBeGreaterThanOrEqual(
            AA_NORMAL_TEXT,
            "{$element} is {$ratio}:1, below the ".AA_NORMAL_TEXT.':1 AA minimum for 14px text'
        );
    }
});

it('does not scroll the document sideways at desktop or mobile widths', function () {
    [$user] = seedInvoiceListFixture();
    $this->actingAs($user);

    $page = visit('/invoices');

    // The table is allowed to scroll inside its own overflow-x-auto wrapper.
    // What must not happen is the *page* scrolling sideways.
    //
    // 768px is deliberately excluded: the shared NavigationMenu shows its
    // desktop block from `sm:` (640px) while the hamburger is `sm:hidden`, so
    // the nav overflows on every page between ~640px and ~1000px. That is
    // tracked separately and is not this page's to fix.
    foreach ([[1440, 900], [375, 812]] as [$width, $height]) {
        $overflow = $page->resize($width, $height)->script(DOC_OVERFLOW_JS);

        expect($overflow)->toBeLessThanOrEqual(
            1,
            "document scrolls horizontally by {$overflow}px at {$width}px wide"
        );
    }
});

it('names every row action with the document it acts on', function () {
    [$user] = seedInvoiceListFixture();
    $this->actingAs($user);

    $labels = visit('/invoices')->resize(1440, 900)->script(<<<'JS'
    function () {
        const row = document.querySelector('tbody tr');
        const cell = row ? row.querySelector('td:last-child') : null;
        if (!cell) return null;

        return Array.from(cell.querySelectorAll('a, button')).map(el => ({
            text: el.textContent.trim(),
            label: el.getAttribute('aria-label'),
        }));
    }
    JS);

    // Without these, a list of 10 documents renders 10 identical "View" links
    // with nothing to tell them apart in a screen reader's element list.
    expect($labels)->toBe([
        ['text' => 'View', 'label' => 'View estimate CT-EST-001 (opens in a new tab)'],
        ['text' => 'Edit', 'label' => 'Edit estimate CT-EST-001'],
        ['text' => 'PDF', 'label' => 'Download PDF of estimate CT-EST-001'],
        ['text' => 'Duplicate', 'label' => 'Duplicate estimate CT-EST-001'],
        ['text' => 'Convert', 'label' => 'Convert estimate CT-EST-001 to an invoice'],
        ['text' => 'Delete', 'label' => 'Delete estimate CT-EST-001'],
    ]);
});

it('exposes filter state and labels the status control', function () {
    [$user] = seedInvoiceListFixture();
    $this->actingAs($user);

    $page = visit('/invoices')->resize(1440, 900);

    $state = $page->script(<<<'JS'
    function () {
        const tabs = Array.from(document.querySelectorAll('[role="group"] button'));
        const select = document.querySelector('#status-filter');
        const label = select ? document.querySelector('label[for="status-filter"]') : null;

        return {
            pressed: tabs.map(t => [t.textContent.trim(), t.getAttribute('aria-pressed')]),
            selectLabel: label ? label.textContent.trim() : null,
            labelIsScreenReaderOnly: label ? label.classList.contains('sr-only') : false,
        };
    }
    JS);

    expect($state['pressed'])->toBe([
        ['All', 'true'],
        ['Invoices', 'false'],
        ['Estimates', 'false'],
    ]);
    expect($state['selectLabel'])->toBe('Filter by status');
    expect($state['labelIsScreenReaderOnly'])->toBeTrue();
});

it('gives the table column scopes and a caption', function () {
    [$user] = seedInvoiceListFixture();
    $this->actingAs($user);

    $semantics = visit('/invoices')->resize(1440, 900)->script(<<<'JS'
    function () {
        const headers = Array.from(document.querySelectorAll('thead th'));
        const caption = document.querySelector('table caption');

        return {
            headerCount: headers.length,
            scopedCount: headers.filter(h => h.getAttribute('scope') === 'col').length,
            caption: caption ? caption.textContent.trim() : null,
            captionIsScreenReaderOnly: caption ? caption.classList.contains('sr-only') : false,
        };
    }
    JS);

    expect($semantics['headerCount'])->toBe(7);
    expect($semantics['scopedCount'])->toBe($semantics['headerCount']);
    expect($semantics['caption'])->toBe('Invoices and estimates');
    expect($semantics['captionIsScreenReaderOnly'])->toBeTrue();
});

it('marks the current page in a navigable pagination list', function () {
    // 10 per page, so 12 documents forces a second page.
    [$user] = seedInvoiceListFixture(12);
    $this->actingAs($user);

    $pagination = visit('/invoices')->resize(1440, 900)->script(<<<'JS'
    function () {
        const nav = document.querySelector('nav[aria-label="Pagination"]');
        if (!nav) return { present: false };

        const anchors = Array.from(nav.querySelectorAll('a'));

        return {
            present: true,
            // Buttons cannot be opened in a new tab or read as navigation.
            buttonCount: nav.querySelectorAll('button').length,
            anchorCount: anchors.length,
            current: anchors
                .filter(a => a.getAttribute('aria-current') === 'page')
                .map(a => a.textContent.trim()),
            labels: anchors.map(a => a.getAttribute('aria-label')),
        };
    }
    JS);

    expect($pagination['present'])->toBeTrue();
    expect($pagination['buttonCount'])->toBe(0);
    expect($pagination['anchorCount'])->toBeGreaterThan(0);
    expect($pagination['current'])->toBe(['1']);
    expect($pagination['labels'])->toContain('Go to page 2');
});

it('keeps focus in the document after an optimistic delete', function () {
    [$user] = seedInvoiceListFixture(3);
    $this->actingAs($user);

    $page = visit('/invoices')->resize(1440, 900);

    $page->click('button[aria-label="Delete invoice CT-INV-001"]')
        ->waitForText('Delete Document')
        ->click('[role="dialog"] button:has-text("Delete")')
        ->wait(2);

    $state = $page->script(<<<'JS'
    function () {
        const active = document.activeElement;

        return {
            rows: Array.from(document.querySelectorAll('tbody tr'))
                .map(r => (r.textContent.match(/CT-(INV|EST)-\d+/) || [])[0])
                .filter(Boolean),
            focusLabel: active ? active.getAttribute('aria-label') : null,
            focusIsBody: active === document.body,
            announcement: (document.querySelector('[role="status"]') || {}).textContent || '',
        };
    }
    JS);

    // The row really is gone, so the dialog's focus restore has nothing to
    // return to. Without an explicit hand-off, focus falls to <body> and a
    // keyboard or screen reader user is dumped at the top of the document —
    // which is what this asserts against.
    expect($state['rows'])->not->toContain('CT-INV-001');
    expect($state['focusIsBody'])->toBeFalse();
    expect($state['focusLabel'])->toBe('Invoices and estimates list');
    expect(trim($state['announcement']))->toBe('Invoice deleted successfully.');
});

it('hides the decorative empty-state icon from assistive technology', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'invoice-list-empty@example.test',
    ]);

    createOrganizationWithLocation([
        'name' => 'Empty Co',
        'currency' => 'INR',
        'country_code' => 'IN',
        'setup_completed_at' => now(),
    ], [], $user);

    $this->actingAs($user);

    $page = visit('/invoices')->resize(1440, 900);

    $page->assertSee('No documents yet')
        ->assertScript(
            "function () { const svg = document.querySelector('tbody svg'); return !!svg && svg.getAttribute('aria-hidden') === 'true'; }"
        );
});
