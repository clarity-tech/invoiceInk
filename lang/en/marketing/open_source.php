<?php

return [
    'meta' => [
        'title' => 'Open-source invoicing software, MIT licensed | :app',
        'description' => 'InvoiceInk is free, open-source invoicing under the MIT licence. Use the hosted app free, or run it yourself with Laravel, MySQL and Docker.',
    ],

    'hero' => [
        'badge' => 'MIT licensed',
        'title' => 'Open source,',
        'accent' => 'top to bottom.',
        'intro' => 'All of InvoiceInk is on GitHub under the MIT licence. Use the hosted app free, or run your own copy on your own servers.',
        'github' => 'View on GitHub',
        'terminal_comment' => 'install the PHP packages, as the README shows',
    ],

    'why' => [
        'heading' => 'Why open source',
        'body' => 'Invoices are business records. With the code open, you can read exactly how every rupee of tax is worked out, see what’s stored, and run it yourself if you’d rather keep everything in-house.',
    ],

    'stack' => [
        'heading' => 'Built on',
        'items' => [
            ['title' => 'Laravel 13 and PHP 8.5', 'body' => 'The application and its API.'],
            ['title' => 'Vue 3, Inertia and TypeScript', 'body' => 'The interface, styled with Tailwind CSS.'],
            ['title' => 'MySQL 8.4', 'body' => 'Every amount stored as whole paise or cents, never floats.'],
            ['title' => 'Gotenberg', 'body' => 'Turns each invoice into an A4 PDF.'],
            ['title' => 'S3-compatible storage', 'body' => 'Logos and attachments.'],
            ['title' => 'FrankenPHP and Octane', 'body' => 'The production web server.'],
        ],
    ],

    'self_host' => [
        'heading' => 'Run it yourself',
        'intro' => 'Every push to main publishes a container image, ghcr.io/clarity-tech/invoiceink (linux/amd64). It runs the web app. Alongside it you need:',
        'requirements' => [
            'MySQL',
            'Gotenberg 8, for PDFs',
            'S3-compatible storage, for logos and attachments',
            'SMTP, for email',
            'A queue worker, which sends the email',
            'The scheduler, which fetches exchange rates daily (needs internet access)',
            'Optional: an Anthropic API key, for the AI features',
            'Optional: Passport keys and MCP_WEB_ENABLED=true, for the Claude connector',
        ],
        'local' => 'To try it on your own machine, the README walks through running it with Docker and Laravel Sail.',
        'readme' => 'Read the README',
    ],

    'faq' => [
        'heading' => 'Questions',
        'items' => [
            [
                'question' => 'Is the hosted version really free?',
                'answer' => 'Yes. There’s no paid plan.',
            ],
            [
                'question' => 'Can I use it commercially?',
                'answer' => 'Yes. The MIT licence lets you use, change and share it, including commercially, as long as you keep the copyright and licence notice.',
            ],
            [
                'question' => 'Who maintains it?',
                'answer' => 'Clarity Technologies, which also runs the hosted app.',
            ],
            [
                'question' => 'How do I report a bug or ask for a feature?',
                'answer' => 'Open an issue on GitHub.',
            ],
        ],
    ],
];
