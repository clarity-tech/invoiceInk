<?php

return [
    'meta' => [
        'title' => 'Ask Claude about your invoices | :app',
        'description' => 'Connect Claude to InvoiceInk and ask what’s overdue, who owes you and how revenue is trending. Read-only, by signing in. Plus AI drafting inside the app.',
    ],

    'hero' => [
        'badge' => 'Works with Claude',
        'title' => 'Ask Claude',
        'accent' => 'about your invoices.',
        'intro' => 'Connect InvoiceInk to Claude and ask in plain English: what’s overdue, who owes you most, how this quarter compares. Claude can read your invoices. It can’t change them.',
    ],

    'mock' => [
        'question' => 'Which invoices are overdue, and who owes the most?',
        'answer' => 'Three invoices are overdue, ₹1,42,300 in all. Acme Bakers owes the most: ₹86,400 across two invoices, the oldest 34 days late.',
        'tool' => 'Looked up: outstanding report',
    ],

    'tools' => [
        'heading' => 'What Claude can look up',
        'invoices' => [
            'title' => 'Invoices and estimates',
            'body' => 'Filter by status, customer, dates or overdue, and sort by date or amount.',
        ],
        'invoice' => [
            'title' => 'Any invoice in full',
            'body' => 'Line items, taxes, payments, notes and terms, by invoice number.',
        ],
        'customers' => [
            'title' => 'Customers',
            'body' => 'Search by name or email, with how many invoices each one has.',
        ],
        'outstanding' => [
            'title' => 'Who owes you',
            'body' => 'What’s owed by age: not yet due, 1–30, 31–60, 61–90 and over 90 days, per currency.',
        ],
        'revenue' => [
            'title' => 'Revenue',
            'body' => 'Invoiced against collected for this month, last month, this quarter, this year, the last 12 months or any dates.',
        ],
        'organizations' => [
            'title' => 'Your organizations',
            'body' => 'Which ones you belong to, and which one Claude is looking at.',
        ],
    ],

    'safety' => [
        'heading' => 'Read-only, and only the organization you’re in',
        'body' => 'Claude sees only the organization you have open in InvoiceInk. It can’t create, send, change or delete anything, and you can disconnect it at any time under Settings → Connect AI tools.',
    ],

    'connect' => [
        'heading' => 'Connect in a minute',
        'url_label' => 'Server URL',
        'web' => [
            'title' => 'Claude on the web, desktop and mobile',
            'steps' => [
                'In Claude, open Customize → Connectors and choose Add custom connector.',
                'Paste the server URL and leave the OAuth fields empty.',
                'Click Connect, sign in to InvoiceInk and approve.',
            ],
            'team' => 'On Team and Enterprise plans, an owner adds the connector once under Organization settings → Connectors, then each person clicks Connect.',
        ],
        'code' => [
            'title' => 'Claude Code',
            'then' => 'Then run /mcp, pick invoicing and choose Authenticate.',
        ],
        'other' => 'Any other MCP client that supports OAuth sign-in connects with the same URL.',
        'disabled' => 'The connector is switched off on this server. If you run InvoiceInk yourself, set MCP_WEB_ENABLED=true to turn it on.',
    ],

    'in_app' => [
        'heading' => 'AI inside InvoiceInk',
        'draft' => [
            'title' => 'Draft from a message or document',
            'body' => 'Paste a message, or drop in a PDF of up to 10 MB or an image of up to 7 MB, and get a draft invoice or estimate to check.',
        ],
        'email' => [
            'title' => 'Write the email',
            'body' => 'A friendly, formal or firm email for any invoice or estimate, ready to edit before you send it.',
        ],
        'reminder' => [
            'title' => 'Chase payment',
            'body' => 'One reminder that covers all of a customer’s unpaid invoices, written for you.',
        ],
        'codes' => [
            'title' => 'Suggest HSN/SAC codes',
            'body' => 'A code and a tax rate for each line, from its description.',
        ],
        'privacy' => 'These are off until an owner or admin turns them on in Settings. What you use them on is sent to Anthropic, our AI provider, which doesn’t train its models on it. Nothing is saved or sent until someone reviews it.',
        'privacy_link' => 'Read the privacy policy',
    ],

    'faq' => [
        'heading' => 'Questions',
        'items' => [
            [
                'question' => 'Can Claude create or send invoices?',
                'answer' => 'No. The connector only reads. It can’t create, send, change or delete anything in InvoiceInk.',
            ],
            [
                'question' => 'Which organization does Claude see?',
                'answer' => 'The one you have open in InvoiceInk. Switch organizations in the app and Claude follows.',
            ],
            [
                'question' => 'How do I disconnect Claude?',
                'answer' => 'Go to Settings → Connect AI tools and click Disconnect. Its access ends straight away.',
            ],
            [
                'question' => 'Is InvoiceInk made by Anthropic?',
                'answer' => 'No. InvoiceInk is built by Clarity Technologies. It works with Claude through the Model Context Protocol (MCP), an open standard.',
            ],
        ],
    ],
];
