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

    // Public path resolved via asset(). Use the PNG mark for email: Gmail
    // strips inline SVG and Outlook never supported it.
    'logo' => 'images/email-logo-mark.png',

    // Primary brand color (violet-600). Mirrored in default.css.
    'color' => env('BRAND_COLOR', '#7c3aed'),

    // Long-form tagline shown in the footer "powered by" lockup.
    'tagline' => env('BRAND_TAGLINE', 'Smart Invoicing for Modern Businesses'),

    // Short slogan shown under the header wordmark and as the hidden inbox
    // preview (preheader) text.
    'slogan' => env('BRAND_SLOGAN', 'Invoices that get paid faster.'),

];
