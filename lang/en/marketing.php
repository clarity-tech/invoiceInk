<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Marketing Language Lines
    |--------------------------------------------------------------------------
    |
    | Copy for the public pages a visitor sees before signing in.
    |
    */

    'meta' => [
        'title' => ':app — Free, open-source invoicing with GST and VAT',
        'description' => 'Draft, send and track invoices and estimates in nine currencies, with GST and VAT built in, PDFs, payments, reminders and AI drafting. Free and open source.',
        'image_alt' => ':app: open-source invoicing with GST and VAT built in',
    ],

    'nav' => [
        'home' => ':app home',
        'dashboard' => 'Dashboard →',
        'log_in' => 'Log in',
        'start_free' => 'Start Free',
    ],

    'hero' => [
        'badge' => 'Invoicing that doesn’t suck',
        'headline' => 'Create invoices',
        'headline_accent' => 'at the speed of thought.',
        'intro' => 'Stop wrestling with spreadsheets. InvoiceInk lets you draft, send, and track invoices in seconds — multi-currency, tax-ready, beautifully formatted.',
        'get_started' => 'Get Started Free',
        'sign_in' => 'Sign in',
    ],

    'mock' => [
        'invoice' => 'Invoice',
        'paid' => 'Paid',
        'total' => 'Total',
        'web_development' => 'Web Application Development',
        'design' => 'UI/UX Design — 40 hrs',
        'cloud_setup' => 'Cloud Infrastructure Setup',
    ],

    // A sample tax invoice drawn on the GST pages. Labels match the real
    // document template; the businesses and GSTINs are made up.
    'gst_mock' => [
        'supplier' => 'Kestrel Design Studio',
        'supplier_address' => 'Indiranagar, Bengaluru, Karnataka',
        'supplier_gstin' => 'GSTIN 29AAAAA0000A1Z5',
        'title' => 'TAX INVOICE',
        'number' => '# INV-26014',
        'bill_to' => 'Bill To',
        'customer' => 'Acme Bakers Pvt Ltd',
        'customer_gstin' => 'GSTIN 29BBBBB1111B1Z6',
        'place_of_supply' => 'Place of Supply: Karnataka (29)',
        'date_label' => 'Invoice Date',
        'date' => '14/10/2026',
        'item' => 'Item & Description',
        'qty' => 'Qty',
        'gst' => 'GST',
        'amount' => 'Amount',
        'line_one' => 'Website design and development',
        'line_one_code' => 'HSN/SAC: 998314',
        'line_two' => 'Hosting, 12 months',
        'line_two_code' => 'HSN/SAC: 998315',
        'sub_total' => 'Sub Total',
        'cgst' => 'CGST @ (9%)',
        'sgst' => 'SGST @ (9%)',
        'total' => 'Total',
    ],

    'features' => [
        'heading' => 'Everything you need.',
        'heading_accent' => 'Nothing you don’t.',
        'learn_more' => 'Learn more',
        'instant' => [
            'title' => 'Instant Invoices',
            'body' => 'One form walks you through it. Line items, taxes, attachments — done in under a minute.',
        ],
        'currencies' => [
            'title' => 'Multi-Currency',
            'body' => 'INR, USD, EUR, AED, GBP and more. Each organization gets its own currency with matching tax rates.',
        ],
        'pdf' => [
            'title' => 'PDF & Email',
            'body' => 'Generate pixel-perfect PDFs and email them directly. Public shareable links for your clients, no login needed.',
        ],
        'organizations' => [
            'title' => 'Multi-Organization',
            'body' => 'Run multiple businesses from one account. Each org gets its own customers, numbering series, and tax setup.',
        ],
        'tax' => [
            'title' => 'GST & Tax Ready',
            'body' => 'India GST, UAE VAT, US Sales Tax, EU VAT — tax rates that follow each organization’s country.',
        ],
        'estimates' => [
            'title' => 'Estimates → Invoices',
            'body' => 'Send estimates, get approval, convert to invoice with one click. The whole quote-to-cash flow, sorted.',
        ],
        'ai' => [
            'title' => 'Draft with AI',
            'body' => 'Paste a message or drop in a PDF or photo, and AI drafts the invoice, suggests SAC/HSN codes and writes the email. Switch it on in Settings.',
        ],
        'payments' => [
            'title' => 'Payments & Reminders',
            'body' => 'Record full or part payments, spot what’s overdue, and nudge a customer about one invoice or everything they owe.',
        ],
        'numbering' => [
            'title' => 'Smart Numbering',
            'body' => 'Your prefix, your format. Numbers reset by financial year, calendar year or month, or never.',
        ],
        'templates' => [
            'title' => 'Email Templates',
            'body' => 'Make the invoice, reminder, overdue and thank-you emails sound like you, for each organization.',
        ],
        'claude' => [
            'title' => 'Ask Claude',
            'body' => 'Connect Claude and ask what’s overdue, who owes you, or how revenue is trending. It reads your invoices but can’t change them.',
        ],
        'open_source' => [
            'title' => 'Open Source',
            'body' => 'MIT licensed. Use it free here, or run it on your own server.',
        ],
    ],

    'cta' => [
        'heading' => 'Ready to get paid',
        'heading_accent' => 'faster?',
        'body' => 'Create your first invoice in under 60 seconds. No credit card required.',
        'button' => 'Start for Free',
    ],

    'footer' => [
        'label' => 'Legal',
        'pages_label' => 'Product',
        'pages' => [
            'gst_invoicing' => 'GST invoicing',
            'gst_invoice_format' => 'GST invoice format',
            'hsn_sac_codes' => 'HSN & SAC codes',
            'claude' => 'Works with Claude',
            'open_source' => 'Open source',
        ],
        'terms' => 'Terms',
        'privacy' => 'Privacy',
        'made_by' => 'Made with precision by',
        'company' => 'Clarity Technologies',
    ],
];
