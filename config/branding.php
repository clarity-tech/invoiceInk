<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand Assets & Identity
    |--------------------------------------------------------------------------
    |
    | Single source of truth for the platform branding shown across every
    | outgoing email (transactional document emails and Markdown mailables
    | alike). Keep these in sync with the hard-coded brand color used in the
    | Markdown mail theme at resources/views/vendor/mail/html/themes/default.css
    | (that file is static CSS and cannot read config at runtime).
    |
    */

    // Email logo strategy. Two modes:
    //  • embedded (default): inline the ink-drop mark via CID + render the
    //    "InvoiceInk" wordmark as HTML text. Always displays (survives
    //    image-blocking) and — crucially — stays legible in Gmail dark mode:
    //    Gmail auto-lightens the HTML wordmark, whereas a flat lockup PNG keeps
    //    its dark "Invoice" pixels and vanishes on the darkened card. The small
    //    mark rides along as a CID attachment (embedded app-wide by the
    //    App\Listeners\EmbedBrandLogoInEmails listener, so even framework
    //    notifications get it).
    //  • external: reference the combined lockup as a hosted image URL. No
    //    attachment, but not dark-mode safe and some clients block external
    //    images by default.
    'logo_external' => (bool) env('BRAND_LOGO_EXTERNAL', false),

    // External mode: combined logo lockup (mark + wordmark), referenced by URL.
    // Use PNG: Gmail strips inline SVG and Outlook never supported it.
    'logo' => 'images/email-logo-lockup.png',

    // Optional absolute URL override for the external logo (e.g. a CDN/asset
    // host). When null, emails fall back to asset(config('branding.logo')),
    // which resolves against APP_URL per environment (localhost or production).
    'logo_url' => env('BRAND_LOGO_URL'),

    // Embedded mode: the square ink-drop mark, embedded inline under logo_cid
    // via the EmbedsBrandLogo trait and referenced from markup as
    // <img src="cid:{{ config('branding.logo_cid') }}">.
    'logo_mark' => 'images/email-logo-mark.png',
    'logo_cid' => 'brandlogo',

    // Primary brand color (violet-600). Mirrored in default.css.
    'color' => env('BRAND_COLOR', '#7c3aed'),

    // Long-form tagline shown in the footer "powered by" lockup.
    'tagline' => env('BRAND_TAGLINE', 'Smart Invoicing for Modern Businesses'),

    // Short slogan shown under the header wordmark and as the hidden inbox
    // preview (preheader) text.
    'slogan' => env('BRAND_SLOGAN', 'Invoices that get paid faster.'),

    // When enabled, every outgoing email subject is suffixed with an
    // attribution tag (e.g. "Invoice #INV-001 - Sent Using InvoiceInk").
    // Disable for a fully white-labelled, clean subject line.
    'subject_tag' => (bool) env('BRAND_SUBJECT_TAG', true),

    // Attribution text appended to the subject when subject_tag is on. When
    // null it defaults to "Sent Using <app name>".
    'subject_tag_text' => env('BRAND_SUBJECT_TAG_TEXT'),

    // UTM parameters appended to the platform brand links in generated
    // documents (PDF/web) and emails, so referral traffic back to the
    // marketing site can be attributed. The utm_source is set per channel at
    // the call site (pdf, web, email) and the utm_content per placement
    // (brand_header, powered_by); the medium and campaign below stay constant
    // across every placement. Set either to an empty string to omit it. See
    // App\Support\Branding::attributedUrl().
    'utm_medium' => env('BRAND_UTM_MEDIUM', 'referral'),
    'utm_campaign' => env('BRAND_UTM_CAMPAIGN', 'brand_attribution'),

];
