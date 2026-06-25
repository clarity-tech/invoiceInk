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

    // Public path (relative to public/) of the combined logo lockup (ink-drop
    // mark + "InvoiceInk" wordmark). Emails reference it by absolute URL via
    // asset() so the image is fetched remotely instead of being attached to
    // the message — CID-embedded images get listed in the client's attachment
    // area. Use PNG: Gmail strips inline SVG and Outlook never supported it.
    'logo' => 'images/email-logo-lockup.png',

    // Optional absolute URL override for the email logo (e.g. a CDN/asset
    // host). When null, emails fall back to asset(config('branding.logo')),
    // which resolves against APP_URL per environment (localhost or production).
    'logo_url' => env('BRAND_LOGO_URL'),

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

];
