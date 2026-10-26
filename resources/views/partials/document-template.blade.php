{{--
    Shared document template for invoices and estimates (public + PDF).

    Expected variables:
        $document       - Invoice model instance
        $isInvoice      - bool (true = invoice, false = estimate)
        $isPdf          - bool (true = PDF render, false = public web view)
        $pdfUrl         - signed link to the PDF, web view only; it expires with
                          the link the page was opened by
        $linkExpiresAt  - when that link stops working, web view only

    In dark mode the web view darkens the page around the document, never the
    document itself; the PDF and a printed web view are the same light sheet.
    See the colour tokens below.
--}}
@php
    // Number of pages to stretch the PDF card across so the footer can be
    // pinned to the bottom of the last page. Null on the web view and on the
    // first (measuring) PDF render; set to the page count on the final render.
    $fillPages = $fillPages ?? null;

    // The sheet the document is laid out on. `@page` below wins over the
    // margins PdfService sends to Gotenberg, so these -- not the API's 0.39in
    // -- are what decide where content lands on the paper. The web card is
    // built from the same three values so the preview is the same sheet.
    $pageWidth = '210mm';
    $pageHeight = '297mm';
    $pageMargin = '1mm 5mm';

    $organization = $document->organization;
    // The sender's name as the document states it. Named once because the page
    // chrome repeats it above the sheet, and a page showing two different
    // company names gives a recipient a reason to doubt the invoice.
    $senderName = $organization->company_name ?? $organization->name;
    $logoUrl = $isPdf ? $organization->logo_base64 : $organization->logo_url;

    $orgLocation = $document->organizationLocation;
    $custLocation = $document->customerLocation;
    $isGstInvoice = $document->isGstInvoice();
    $taxColumnHeader = $document->tax_type ?: __('documents.table.tax_rate');

    // Set the invoice relation on each item to avoid lazy-loading through OrganizationScope
    foreach ($document->items as $item) {
        $item->setRelation('invoice', $document);
    }

    if ($isInvoice) {
        $headerTitle = $isGstInvoice ? __('documents.headers.tax_invoice_upper') : __('documents.headers.invoice_upper');
        $balanceLabel = __('documents.financial.balance_due');
        $numberLabel = __('documents.fields.invoice_number');
        $footerMessage = __('messages.footer.computer_generated_invoice');
        $printLabel = __('actions.buttons.print_invoice');
    } else {
        $headerTitle = __('documents.headers.estimate_upper');
        $balanceLabel = __('documents.financial.estimated_total');
        $numberLabel = __('documents.fields.estimate_number');
        $footerMessage = __('messages.footer.computer_generated_estimate');
        $printLabel = __('actions.buttons.print_estimate');
    }

    // Payment info (invoices only)
    $showPaymentInfo = $isInvoice && $document->amount_paid > 0;
    $remainingBalance = $isInvoice ? $document->remaining_balance : $document->total;
    $paymentTerms = $document->paymentTermsLabel();
    $placeOfSupply = $document->placeOfSupplyLabel();
    // Show Ship To only when a separate shipping location is set (different from billing).
    $hasShipping = $document->hasShippingAddress();
    $shipLocation = $document->customerShippingLocation;

    // Organization contact info
    $orgEmails = $organization->emails;
    $orgPhone = $organization->formatted_phone;

    // Platform brand logo (InvoiceInk lockup) shown in the footer. Embedded as
    // base64 for the PDF so Gotenberg need not fetch it over the network (as
    // with the org logo above); the web view links the hosted asset directly.
    $brandLogoFile = public_path(config('branding.logo'));
    $brandLogo = $isPdf && is_file($brandLogoFile)
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($brandLogoFile))
        : (config('branding.logo_url') ?: asset(config('branding.logo')));
    // Brand link with UTM attribution for the placement (PDF or public web).
    $brandUrl = \App\Support\Branding::poweredByUrl($isPdf ? 'pdf' : 'web');
    // The public page carries a second lockup along the bottom of the window.
    // Its own placement, so its referrals stay distinguishable from the one
    // printed inside the document.
    $pageBrandUrl = \App\Support\Branding::attributedUrl('web', 'page_footer');

    // Display amounts as-is, including the currency symbol (renders fine in the PDF).
    $amt = fn(string $formatted): string => $formatted;
    // Line-item amounts are shown without the currency symbol (kept on totals only).
    $amtPlain = fn(string $formatted): string => ltrim(preg_replace('/[^\d,\.]+/', '', $formatted));
    $formattedBalance = $amt($isInvoice ? $document->formatted_remaining_balance : $document->formatted_total);
@endphp
<!DOCTYPE html>
<html lang="en" @class(['dark' => ! $isPdf && ($appearance ?? null) === \App\Enums\Appearance::DARK])>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @unless($isPdf)
        @include('partials.appearance')
    @endunless
    <title>{{ $headerTitle }} {{ $document->invoice_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..700;1,300..700&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ─────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Colour tokens ────────────────────────────────────
           Named after the app's tokens in resources/css/app.css, but defined
           here: the PDF is rendered from this one file, with no stylesheet to
           fetch. The light values are the document's own colours, so the PDF
           is unchanged by them; the `document-*` roles are the ones only a
           document has.

           In dark mode only the page around the document goes dark. The card
           takes these light values back, so the sheet is the one that prints
           in both themes. */
        :root,
        .dark .document-card {
            color-scheme: light;

            --canvas: #f3f4f6;
            --card: #fff;
            --foreground: #111827;
            --soft-foreground: #374151;
            --muted-foreground: #6b7280;
            --faint-foreground: #9ca3af;
            --muted: #f9fafb;
            --border: #e5e7eb;
            --input: #d1d5db;

            /* The sender's violet: the brand bar, the table head, the title. */
            --brand: #7A5A9E;
            --brand-foreground: #fff;
            --warning: #b45309;
            --success: #059669;

            --document-ink: #000;           /* figures */
            --document-text: #333333;       /* everything else on the sheet */
            --document-label: #333319;      /* field labels */
            --document-rule: #f3f4f6;       /* between line items */
            --document-stripe: #fafafa;     /* every other line item */
            --document-total: #f3f4f6;      /* the balance due box */
        }

@if(! $isPdf)
        /* The dark theme for the page around the document: the same Tailwind
           gray steps as the app's, so the shared page reads as the same
           product. Screen only, so printing from the browser is unaffected. */
        @media screen {
            .dark {
                color-scheme: dark;

                --canvas: oklch(13% 0.028 261.692);                 /* gray-950 */
                --card: oklch(21% 0.034 264.665);                   /* gray-900 */
                --foreground: oklch(96.7% 0.003 264.542);           /* gray-100 */
                --soft-foreground: oklch(87.2% 0.01 258.338);       /* gray-300 */
                --muted-foreground: oklch(70.7% 0.022 261.325);     /* gray-400 */
                --faint-foreground: color-mix(in oklab, var(--muted-foreground), oklch(55.1% 0.027 264.364));
                --muted: color-mix(in oklab, var(--card) 55%, var(--border));
                --border: oklch(27.8% 0.033 256.848);               /* gray-800 */
                --input: oklch(37.3% 0.034 259.733);                /* gray-700 */
                --warning: oklch(82.8% 0.189 84.429);               /* amber-400 */
            }

            /* The InvoiceInk lockup in the bar along the bottom of the window
               is drawn in dark navy for a white page. Inverting it and turning
               the hue back round keeps the violet and lightens the navy,
               without a second image to keep in step. The one printed inside
               the document is left alone. */
            .dark .doc-pagefoot img {
                filter: invert(1) hue-rotate(180deg);
            }
        }
@endif

        @page {
            size: A4;
            margin: {{ $pageMargin }};
        }

        @media print {
            .no-print { display: none !important; }
            body { background: var(--card) !important; }
            /* Printing the preview from the browser goes through @page like the
               PDF does, so the card drops the inset it draws on screen rather
               than adding a second one on top of the page margin. */
            .document-card { box-shadow: none !important; padding: 0 !important; }
        }

        body {
            font-family: 'Open Sans', 'Segoe UI', system-ui, sans-serif;
            font-size: 13px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            background: {{ $isPdf ? 'var(--card)' : 'var(--canvas)' }};
        }

        /* ── Utility ──────────────────────────────────────── */
        .nums { font-variant-numeric: tabular-nums; color: var(--document-ink); }
        .text-inv-gray { color: var(--document-text); }
        .text-inv-label { color: var(--document-label); }
        .text-brand-theme { color: var(--brand); }
        .bg-brand-theme { background-color: var(--brand); }

        /* ── Page Wrapper ─────────────────────────────────── */
        .page-wrapper {
            /* One page wide, plus its own 16px gutters: anything narrower would
               squeeze the card below A4 and the document would wrap its text
               differently from the PDF. */
            max-width: calc({{ $pageWidth }} + 32px);
            margin: 0 auto;
            {{ $isPdf ? '' : 'padding: 32px 16px;' }}
        }

@if(! $isPdf)
        /* ── Page chrome (web only) ───────────────────────────
           The card below is a facsimile of the PDF, so everything that
           belongs to the page rather than to the document sits up here:
           who sent it, what is outstanding, and what you can do with it.
           Sticky, so the actions stay reachable down a long invoice. */
        .doc-chrome {
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .doc-chrome-inner {
            /* The same column as the sheet, so the chrome lines up with the
               document rather than with the window. */
            max-width: calc({{ $pageWidth }} + 32px);
            margin: 0 auto;
            padding: 0 16px;
        }
        .doc-brandbar {
            background: var(--brand);
            color: var(--brand-foreground);
        }
        .doc-brandbar .doc-chrome-inner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            column-gap: 16px;
            min-height: 56px;
            font-size: 17px;
            font-weight: 700;
        }
        /* How long the recipient can come back to this page. Chrome rather
           than document, and set beside the sender rather than among the
           figures, so it is not read as a second due date. */
        .doc-link-expiry {
            margin-left: auto;
            font-size: 12px;
            font-weight: 400;
            opacity: 0.85;
        }
        .doc-summary {
            background: var(--card);
            border-bottom: 1px solid var(--border);
        }
        .doc-summary .doc-chrome-inner {
            /* One row: the figure, what it refers to, and what you can do
               about it, read left to right without the eye travelling down. */
            display: flex;
            align-items: flex-end;
            gap: 28px;
            padding-top: 16px;
            padding-bottom: 16px;
        }
        .doc-summary-amount {
            font-size: 27px;
            font-weight: 700;
            line-height: 1.15;
            color: var(--foreground);
        }
        .doc-summary-label {
            margin-top: 2px;
            font-size: 13px;
            font-weight: 600;
            color: var(--warning);
        }
        .doc-summary-meta { display: flex; }
        .doc-summary-meta > div + div {
            margin-left: 24px;
            padding-left: 24px;
            border-left: 1px solid var(--border);
        }
        .doc-summary-meta dt { font-size: 12px; color: var(--muted-foreground); }
        .doc-summary-meta dd {
            margin-top: 2px;
            font-size: 14px;
            font-weight: 600;
            color: var(--foreground);
        }
        .doc-summary-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        .icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 36px;
            border: 1px solid var(--input);
            border-radius: 6px;
            background: var(--card);
            color: var(--soft-foreground);
            cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s;
        }
        .icon-btn:hover { background: var(--muted); box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        /* The theme switch shows what it gives you: a moon on the light page,
           a sun on the dark one. */
        .theme-switch-sun { display: none; }
        .dark .theme-switch-sun { display: block; }
        .dark .theme-switch-moon { display: none; }

        /* The platform lockup, along the bottom of the window. The document
           prints its own in the footer; this one belongs to the page, so it
           stays in view the way the chrome above does. */
        .doc-pagefoot {
            position: sticky;
            bottom: 0;
            z-index: 18;
            background: var(--card);
            border-top: 1px solid var(--border);
        }
        .doc-pagefoot .doc-chrome-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 36px;
            font-size: 11px;
            color: var(--muted-foreground);
        }
        .doc-pagefoot a { display: inline-flex; align-items: center; }
        .doc-pagefoot img { display: block; height: 15px; width: auto; }
        .doc-pagefoot .powered-tagline { color: var(--faint-foreground); }
@endif

        /* ── Document Card ────────────────────────────────── */
        /*
           The footer block (notes, bank, terms, branding) is pinned to the
           bottom of the (last) page so any spare vertical space sits above it.

           Web view: a flex column with min-height fills the viewport and
           margin-top:auto pushes the footer down — flexbox is reliable here.

           PDF: flexbox does not fragment cleanly across print pages in
           Chromium, so we use a block card whose min-height is set to the
           exact page count ($fillPages × 100vh, where 100vh == one printable
           page) and absolutely position the footer at its bottom. $fillPages
           is resolved by PdfService via a first measuring render.
        */
        .document-card {
            background: var(--card);
@if($isPdf)
            {{ $fillPages ? 'position: relative; min-height: '.((int) $fillPages * 100).'vh;' : '' }}
            padding: 0;
@else
            display: flex;
            flex-direction: column;
            /* The card is the sheet of paper, so it carries the page's own
               margin and no more: content then starts where the PDF starts it
               and has the same width to wrap in. */
            padding: {{ $pageMargin }};
            /* Positioning context for the status ribbon, which the PDF has no
               use for -- leaving the PDF card exactly as it was. */
            position: relative;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 6px 16px rgba(0,0,0,0.04);
@endif
        }

@if($isPdf)
@if($fillPages)
        .doc-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
        }
@endif
@else
        .doc-bottom {
            margin-top: auto;
        }

        /* A page tall, not a viewport tall: the spare room above the footer is
           then the same spare room the PDF has, instead of however deep the
           window happens to be. Screen only -- in print the page box already
           decides the height, and a card taller than it would spill onto a
           second sheet. */
        @media screen {
            .document-card { min-height: {{ $pageHeight }}; }
        }
@endif

        /* ── Header ───────────────────────────────────────── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 14px;
        }
        .doc-header .company-logo {
            max-height: 52px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .doc-header .company-name {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .doc-header .company-meta {
            font-size: 12px;
            line-height: 1.3;
        }
        .doc-header .company-meta .tax_id {
            font-weight: 500;
            margin-top: 2px;
        }

        .doc-title-block { text-align: right; flex-shrink: 0; padding-left: 16px; }
        .doc-title {
            font-size: 20px;
            font-weight: 500;
            letter-spacing: 0.01em;
            text-transform: uppercase;
        }
        .doc-number {
            font-size: 12px;
            font-weight: 700;
            margin-top: 2px;
        }

@if(! $isPdf)
        /* ── Status ribbon ────────────────────────────────
           A band across the top-left of the preview. It belongs to the preview
           rather than to the document: the PDF carries no status, and neither
           does a printed preview -- hence `no-print` on the band itself.

           It clips itself rather than relying on `overflow` on .document-card:
           that card is a block that has to stay printable, and a clipped box
           that fragments across pages is what Chromium gets wrong. */
        .doc-ribbon {
            position: absolute;
            top: 0;
            left: 0;
            width: 130px;
            height: 130px;
            overflow: hidden;
            pointer-events: none;
        }
        .doc-ribbon span {
            /* Geometry, since it is not guessable: the band covers the strip
               where x + y falls within half its thickness of (left + width/2 +
               top + height/2). Closer to the corner means less to clear but a
               shorter band -- and it still has to hold "Partially Paid". */
            position: absolute;
            top: 18px;
            left: -42px;
            width: 150px;
            padding: 5px 0;
            transform: rotate(-45deg);
            text-align: center;
            /* Sentence case, like the reference: set at 45 degrees, uppercase
               with tracking reads cramped. */
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #fff;
            background: #6b7280;
        }
        /* Keyed to InvoiceStatus::color() rather than to each status, so the
           enum stays the one place that decides what a status means and this
           only decides what that looks like on a dark band. */
        .doc-ribbon-brand span { background: var(--brand); }
        .doc-ribbon-yellow span { background: #a16207; }
        .doc-ribbon-amber span { background: #d97706; }
        .doc-ribbon-green span { background: #059669; }
        .doc-ribbon-red span { background: #b91c1c; }

        /* The band is an overlay that crosses the logo, as the reference does,
           but it must not cross the sender's name. On screen the logo sits in
           a slot as tall as a full-height logo's line (52px, its 10px margin
           and 5px of line), so the name starts below the band whether the logo
           is full height, short and wide, or missing -- and with a full-height
           logo nothing moves. Print drops the band, so the slot goes too. */
        @media screen {
            .doc-from {
                display: grid;
                grid-template-rows: minmax(67px, auto) auto auto;
            }
            .doc-from .company-logo { grid-row: 1; align-self: start; justify-self: start; }
            .doc-from .company-name { grid-row: 2; }
            .doc-from .company-meta { grid-row: 3; }
        }
@endif

        /* ── Bill To + Details ────────────────────────────── */
        .meta-row {
            display: flex;
            gap: 32px;
            padding: 14px 0;
        }
        .meta-col { flex: 1; }

        .section-label {
            font-size: 10px;
            color: var(--document-label);
        }
        .customer-name {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .address-text {
            font-size: 12px;
            line-height: 1.3;
        }
        .address-text .tax_id {
            font-weight: 500;
            margin-top: 2px;
        }

        .detail-table {
            margin-left: auto;
            border-collapse: collapse;
        }
        .detail-table td {
            padding: 3px 0;
            font-size: 12px;
            vertical-align: top;
        }
        .detail-table .dt-label {
            color: var(--document-label);
            padding-right: 60px;
            white-space: nowrap;
        }
        .detail-table .dt-label::after { content: ' :'; }
        .detail-table .dt-value {
            font-weight: 500;
        }

        /* ── Items Table ──────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 10px;
        }
        .items-table thead th {
            padding: 6px 10px;
            font-size: 14px;
            font-weight: 500;
            color: var(--brand-foreground);
            border-top: none;
            border-bottom: none;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .items-table tbody td {
            padding: 7px 10px;
            font-size: 13px;
            border-bottom: 1px solid var(--document-rule);
            vertical-align: top;
        }
        .items-table tbody tr:nth-child(even) td {
            background: var(--document-stripe);
        }
        .items-table .item-sac {
            font-size: 11px;
            margin-top: 2px;
        }
        .items-table .tax-detail {
            font-size: 11px;
        }
        .items-table .col-num { width: 35px; text-align: center; }
        .items-table .col-qty { width: 50px; text-align: center; }
        .items-table .col-rate { width: 110px; text-align: right; }
        .items-table .col-tax { width: 90px; text-align: right; }
        .items-table .col-amount { width: 110px; text-align: right; }

        /* ── Totals ───────────────────────────────────────── */
        .totals-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }
        .totals-box { width: 335px; display: flex; flex-direction: column; gap: 10px; }
        .totals-row {
            display: flex;
            justify-content: space-between;
            gap: 50px;
            font-size: 12px;
            padding: 0 10px;
        }
        .totals-row .t-label { color: var(--document-label); text-align: right; flex: 1; }
        .totals-row .t-value { text-align: right; white-space: nowrap; width: 120px; flex-shrink: 0; }
        .totals-row.total-line {
        }
        .totals-row.total-line .t-label,
        .totals-row.total-line .t-value {
            font-weight: 700;
        }
        .totals-row.payment-row .t-label { color: var(--success); }
        .totals-row.payment-row .t-value { color: var(--success); }

        .balance-due-box {
            padding: 5px 10px;
            background: var(--document-total);
            border-radius: 2px;
        }
        .balance-due-box .t-label,
        .balance-due-box .t-value {
            font-weight: 700;
        }

        .words-row {
            margin-top: 6px;
            font-size: 12px;
            color: var(--muted-foreground);
        }
        .words-row .words-value {
            font-style: italic;
            font-weight: 600;
            color: var(--document-label);
        }

        /* ── Notes / Terms / Bank ─────────────────────────── */
        .info-section {
            margin-bottom: 10px;
        }
        .info-section .info-title {
            font-size: 11px;
            margin-bottom: 4px;
        }
        .info-section .info-body {
            font-size: 12px;
            white-space: pre-wrap;
            line-height: 1.3;
        }

        /* ── Footer ───────────────────────────────────────── */
        .doc-footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid var(--border);
            text-align: center;
        }
        .doc-footer .brand-logo {
            height: 20px;
            width: auto;
            display: inline-block;
            margin-top: 2px;
        }
        .doc-footer .powered-by {
            margin-top: 4px;
            color: var(--faint-foreground);
        }
        .doc-footer .powered-tagline {
            margin-top: 5px;
            font-size: 10px;
        }
        .doc-footer .powered-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .doc-footer .powered-brand {
            color: var(--brand);
            font-weight: 700;
            text-decoration: none;
        }
@if(! $isPdf)
        /* ── Mobile (web view only) ───────────────────────── */
        .items-scroll { width: 100%; }
        @media screen and (max-width: 640px) {
            .page-wrapper { padding: 16px 12px; }
            .document-card { padding: 22px 18px; }

            /* Scrolls away on a phone. Pinned it would hold around a fifth of
               the screen for the whole document, and a recipient opening the
               link on a phone has little enough room as it is. */
            .doc-chrome { position: static; }
            .doc-chrome-inner { padding: 0 12px; }
            .doc-brandbar .doc-chrome-inner { min-height: 48px; font-size: 15px; }
            .doc-link-expiry { flex-basis: 100%; margin: -6px 0 0; padding-bottom: 10px; }
            /* Too little width for one row, so it breaks where it reads best:
               the figure and the two actions keep the first line, and the
               number and date drop underneath them together. */
            .doc-summary .doc-chrome-inner { flex-wrap: wrap; gap: 12px 16px; }
            .doc-summary-meta { order: 3; flex-basis: 100%; }

            /* The tagline is the first thing to go: it is the longest part of
               the lockup and the least load-bearing, and keeping it would wrap
               the bar onto two ragged lines. */
            .doc-pagefoot .powered-tagline { display: none; }

            /* Stack the logo/title header */
            .doc-header { flex-direction: column; }
            .doc-title-block { text-align: left; padding-left: 0; margin-top: 14px; }

            /* Stack Bill To / details */
            .meta-row { flex-direction: column; gap: 18px; }
            .detail-table { margin-left: 0; width: 100%; }
            .detail-table .dt-label { padding-right: 16px; }

            /* Let the items table scroll horizontally instead of overflowing the page */
            .items-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .items-table { min-width: 460px; }

            /* Totals span the full width */
            .totals-wrapper { justify-content: stretch; }
            .totals-box { width: 100%; }
        }
@endif
    </style>
</head>
<body class="text-inv-gray">
@if(!$isPdf)
    {{-- Page chrome: the sender, the one figure that matters, and the two
         things a recipient does with an invoice. Full-bleed, so it frames the
         sheet instead of sitting on it. --}}
    <div class="doc-chrome no-print">
        <div class="doc-brandbar">
            <div class="doc-chrome-inner">
                <span class="doc-sender">{{ $senderName }}</span>
                <span class="doc-link-expiry">{{ __('messages.share_link.works_until', ['date' => $linkExpiresAt->format('j M Y')]) }}</span>
            </div>
        </div>
        <div class="doc-summary">
            <div class="doc-chrome-inner">
                <div class="doc-summary-balance">
                    <div class="doc-summary-amount">{{ $formattedBalance }}</div>
                    <div class="doc-summary-label">{{ $balanceLabel }}</div>
                </div>
                <dl class="doc-summary-meta">
                    <div>
                        <dt>{{ $numberLabel }}</dt>
                        <dd>{{ $document->invoice_number }}</dd>
                    </div>
                    @if($document->due_at)
                        <div>
                            <dt>{{ __('documents.fields.due_date') }}</dt>
                            <dd>{{ $document->due_at->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                </dl>
                <div class="doc-summary-actions">
                    {{-- The reader's own choice of theme, which the page now
                         follows; aria-pressed and the title are kept current by
                         the script at the end of the page. --}}
                    <button type="button" class="icon-btn" data-appearance-switch aria-label="{{ __('actions.buttons.dark_theme') }}" aria-pressed="false" title="{{ __('actions.tooltips.switch_to_dark_theme') }}">
                        <svg class="theme-switch-moon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                        <svg class="theme-switch-sun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    </button>
                    <button type="button" onclick="window.print()" class="icon-btn" title="{{ $printLabel }}" aria-label="{{ $printLabel }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    </button>
                    <a href="{{ $pdfUrl }}" class="icon-btn" title="{{ __('actions.buttons.download_pdf') }}" aria-label="{{ __('actions.buttons.download_pdf') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif

    <div class="page-wrapper">
        <div class="document-card">
            @unless($isPdf)
                {{-- Not aria-hidden: the band is the only place the status is
                     stated now, and it reads as text in document order. --}}
                <div class="doc-ribbon no-print doc-ribbon-{{ $document->status->color() }}"><span>{{ $document->status->label() }}</span></div>
            @endunless

            {{-- A. Header ──────────────────────────────────── --}}
            <div class="doc-header">
                <div class="doc-from">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $organization->name }}" class="company-logo">
                    @endif
                    <div class="company-name">{{ $senderName }}</div>
                    <div class="company-meta">
                        {{ $orgLocation->address_line_1 }}@if($orgLocation->address_line_2), {{ $orgLocation->address_line_2 }}@endif<br>
                        {{ $orgLocation->city }} {{ $orgLocation->state }} {{ $orgLocation->postal_code }}<br>
                        {{ $orgLocation->countryName() }}
                        @if($orgLocation->tax_id)
                            <div class="tax_id">{{ $orgLocation->taxIdLabel() }} {{ $orgLocation->tax_id }}</div>
                        @endif
                        @if($orgPhone && !$isPdf)
                            <div>{{ $orgPhone }}</div>
                        @endif
                    </div>
                </div>

                <div class="doc-title-block">
                    <div class="doc-title text-brand-theme">{{ $headerTitle }}</div>
                    <div class="doc-number"># {{ $document->invoice_number }}</div>

                    <div style="margin-top: 12px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--soft-foreground); letter-spacing: 0.04em;">{{ $balanceLabel }}</div>
                        <div style="font-weight: 700;" class="nums">{{ $formattedBalance }}</div>
                    </div>
                </div>
            </div>

            {{-- B. Bill To + Details ───────────────────────── --}}
            <div class="meta-row">
                <div class="meta-col">
                    <div class="section-label">{{ __('documents.headers.bill_to') }}</div>
                    <div class="customer-name">{{ $document->customer?->name ?? $custLocation->locatable?->name ?? '' }}</div>
                    <div class="address-text">
                        {{ $custLocation->address_line_1 }}<br>
                        @if($custLocation->address_line_2)
                            {{ $custLocation->address_line_2 }}<br>
                        @endif
                        {{ $custLocation->city }}<br>
                        {{ $custLocation->postal_code }} {{ $custLocation->state }}<br>
                        {{ $custLocation->countryName() }}
                        @if($custLocation->tax_id)
                            <div class="tax_id">{{ $custLocation->taxIdLabel() }} {{ $custLocation->tax_id }}</div>
                        @endif
                        @if($placeOfSupply)
                            <br>
                            <div>{{ __('documents.fields.place_of_supply') }}: {{ $placeOfSupply }}</div>
                        @endif
                    </div>
                </div>

                <div class="meta-col ">
                    <table class="detail-table">
                        @if($document->issued_at)
                            <tr>
                                <td class="dt-label text-right">
                                    {{ __('documents.fields.issue_date') }}
                                </td>
                                <td class="dt-value nums text-right">
                                    {{ $document->issued_at->format('d/m/Y') }}
                                </td>
                            </tr>
                        @endif
                        @if($document->order_number)
                            <tr>
                                <td class="dt-label text-right">
                                    {{ __('documents.fields.order_number') }}
                                </td>
                                <td class="dt-value text-right">
                                    {{ $document->order_number }}
                                </td>
                            </tr>
                        @endif
                        @if($paymentTerms)
                            <tr>
                                <td class="dt-label text-right">
                                    {{ __('documents.fields.terms') }}
                                </td>
                                <td class="dt-value text-right">{{ $paymentTerms }}</td>
                            </tr>
                        @endif
                        @if($document->due_at)
                            <tr>
                                <td class="dt-label text-right">
                                    {{ __('documents.fields.due_date') }}
                                </td>
                                <td class="dt-value nums text-right">
                                    {{ $document->due_at->format('d/m/Y') }}
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- B1. Subject Line --}}
            @if($document->subject)
                <div style="font-size: 13px;">
                    <span>Subject:</span>
                    <span>{{ $document->subject }}</span>
                </div>
            @endif

            {{-- B2. Ship To (only when different from Bill To) ────── --}}
            @if($hasShipping)
                <div class="meta-row" style="margin-top: 12px; padding-top: 0;">
                    <div class="meta-col">
                        <div class="section-label">Ship To</div>
                        <div class="customer-name">{{ $shipLocation->name ?? '' }}</div>
                        <div class="address-text">
                            {{ $shipLocation->address_line_1 }}<br>
                            @if($shipLocation->address_line_2)
                                {{ $shipLocation->address_line_2 }}<br>
                            @endif
                            {{ $shipLocation->city }}<br>
                            {{ $shipLocation->postal_code }} {{ $shipLocation->state }}<br>
                            {{ $shipLocation->country }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- C. Line Items ──────────────────────────────── --}}
            <div class="items-scroll">
            <table class="items-table" style="margin-top: 15px;">
                <thead class="bg-brand-theme">
                    <tr>
                        <th class="text-center" style="width:36px">{{ __('documents.table.row_number') }}</th>
                        <th class="text-left">{{ __('documents.table.item_and_description') }}</th>
                        <th class="text-center">{{ __('documents.table.qty') }}</th>
                        <th class="text-right">{{ __('documents.table.rate') }}</th>
                        <th class="text-right">{{ $taxColumnHeader }}</th>
                        <th class="text-right">{{ __('documents.table.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($document->items as $index => $item)
                        <tr>
                            <td class="col-num">{{ $index + 1 }}</td>
                            <td>
                                <div>{{ $item->description }}</div>
                                @if($item->sac_code)
                                    <div class="item-sac">{{ __('documents.fields.sac') }}: {{ $item->sac_code }}</div>
                                @endif
                            </td>
                            <td class="col-qty nums">{{ number_format($item->quantity, 2) }}</td>
                            <td class="col-rate nums">{{ $amtPlain($item->formatted_unit_price) }}</td>
                            <td class="col-tax nums">
                                {{ $amtPlain($item->formatted_tax_amount) }}
                                @if($item->tax_rate)
                                    <div class="tax-detail">{{ $item->formatted_tax_rate }}</div>
                                @endif
                            </td>
                            <td class="col-amount nums">{{ $amtPlain($item->formatted_pre_tax_line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            {{-- D. Totals ──────────────────────────────────── --}}
            <div class="totals-wrapper">
                <div class="totals-box">
                    <div class="totals-row">
                        <span class="t-label">{{ __('documents.financial.subtotal') }}</span>
                        <span class="t-value nums">{{ $amt($document->formatted_subtotal) }}</span>
                    </div>

                    @if(!empty($document->tax_breakdown) && is_array($document->tax_breakdown))
                        @foreach($document->tax_breakdown as $taxName => $taxAmount)
                            @if(is_numeric($taxAmount))
                                <div class="totals-row">
                                    <span class="t-label">{{ $taxName }}</span>
                                    <span class="t-value nums">{{ $amt($document->formatMoney($taxAmount)) }}</span>
                                </div>
                            @endif
                        @endforeach
                    @else
                        @if($document->tax > 0)
                            <div class="totals-row">
                                <span class="t-label">{{ $document->tax_type ?: __('documents.financial.tax') }}</span>
                                <span class="t-value nums">{{ $amt($document->formatted_tax) }}</span>
                            </div>
                        @endif
                    @endif

                    @if($document->hasTcs())
                        <div class="totals-row">
                            <span class="t-label">{{ $document->tcsLabel() }}</span>
                            <span class="t-value nums">+ {{ $amt($document->formatted_tcs_amount) }}</span>
                        </div>
                    @endif

                    @if($document->hasTds())
                        <div class="totals-row">
                            <span class="t-label">{{ $document->tdsLabel() }}</span>
                            <span class="t-value nums">&minus; {{ $amt($document->formatted_tds_amount) }}</span>
                        </div>
                    @endif

                    <div class="totals-row total-line">
                        <span class="t-label">{{ __('documents.financial.total') }}</span>
                        <span class="t-value nums">{{ $amt($document->formatted_total) }}</span>
                    </div>

                    @if($showPaymentInfo)
                        <div class="totals-row payment-row">
                            <span class="t-label">Amount Paid</span>
                            <span class="t-value nums">&minus; {{ $amt($document->formatted_amount_paid) }}</span>
                        </div>
                    @endif

                    <div class="totals-row balance-due-box">
                        <span class="t-label">{{ $balanceLabel }}</span>
                        <span class="t-value nums">{{ $formattedBalance }}</span>
                    </div>

                    <div class="words-row">
                        {{ __('documents.financial.total_in_words') }}:
                        <span class="words-value">{{ $document->currency->amountToWords($document->total) }}</span>
                    </div>
                </div>
            </div>

            <div class="doc-bottom">
            {{-- E. Notes ───────────────────────────────────── --}}
            @if($document->notes)
                <div class="info-section">
                    <div class="info-title">{{ __('documents.fields.notes') }}</div>
                    <div class="info-body">{{ $document->notes }}</div>
                </div>
            @endif

            {{-- F. Bank Details (invoices only) ────────────── --}}
            @if($isInvoice && $organization->hasBankDetails())
                @php $bank = $organization->bank_details; @endphp
                <div style="margin-bottom: 10px; font-size: 12px; line-height: 1.3;">
                    <div style="font-weight: 600;">{{ $bank->accountName ?: ($organization->company_name ?? $organization->name) }}</div>
                    @if($bank->accountNumber)
                        <div>Account No: {{ $bank->accountNumber }}</div>
                    @endif
                    @if($bank->bankName)
                        <div>{{ $bank->bankName }}</div>
                    @endif
                    @if($bank->ifsc || $bank->branch)
                        <div>{{ $bank->formattedBranchLine() }}</div>
                    @endif
                    @if($bank->swift)
                        <div>SWIFT Code {{ $bank->swift }}</div>
                    @endif
                    @if($bank->pan)
                        <div>PAN {{ $bank->pan }}</div>
                    @endif
                </div>
            @endif

            {{-- G. Terms ───────────────────────────────────── --}}
            @if($document->terms)
                <div class="info-section">
                    <div class="info-title text-inv-label">
                        {{ __('documents.fields.terms_and_conditions') }}
                    </div>
                    <div class="info-body text-inv-gray" style="white-space: pre-line;">{{ trim($document->terms) }}</div>
                </div>
            @endif

            {{-- H. Footer ──────────────────────────────────── --}}
            <div class="doc-footer">
                <div class="powered-by">
                    <span class="powered-label">{{ __('messages.email.powered_by') }}</span><br>
                    <a href="{{ $brandUrl }}" class="powered-brand">
                        <img src="{{ $brandLogo }}" alt="{{ config('app.name', 'InvoiceInk') }}" class="brand-logo">
                    </a>
                    <div class="powered-tagline">{{ config('branding.tagline', 'Smart Invoicing for Modern Businesses') }}</div>
                </div>
            </div>
            </div>{{-- /.doc-bottom --}}
        </div>
    </div>
@if(!$isPdf)
    {{-- The same lockup the document prints, kept in view along the bottom of
         the window rather than only at the end of a long invoice. --}}
    <div class="doc-pagefoot no-print">
        <div class="doc-chrome-inner">
            <span>{{ __('messages.email.powered_by') }}</span>
            <a href="{{ $pageBrandUrl }}">
                <img src="{{ $brandLogo }}" alt="{{ config('app.name', 'InvoiceInk') }}">
            </a>
            <span class="powered-tagline">{{ config('branding.tagline') }}</span>
        </div>
    </div>
    <script>
        // The theme switch in the chrome. It stores the choice in the same
        // cookie as the app's switch, so the head script stops following the
        // operating system, and it flips what the page shows now.
        (function () {
            var root = document.documentElement;
            var button = document.querySelector('[data-appearance-switch]');
            var titles = { dark: @json(__('actions.tooltips.switch_to_light_theme')), light: @json(__('actions.tooltips.switch_to_dark_theme')) };

            function sync() {
                var isDark = root.classList.contains('dark');
                button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
                button.title = isDark ? titles.dark : titles.light;
            }

            button.addEventListener('click', function () {
                var choice = root.classList.contains('dark') ? 'light' : 'dark';
                var secure = location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = '{{ \App\Enums\Appearance::COOKIE }}=' + choice + '; path=/; max-age=31536000; SameSite=Lax' + secure;
                root.classList.toggle('dark', choice === 'dark');
            });

            new MutationObserver(sync).observe(root, { attributes: true, attributeFilter: ['class'] });
            sync();
        })();
    </script>
@endif
</body>
</html>
