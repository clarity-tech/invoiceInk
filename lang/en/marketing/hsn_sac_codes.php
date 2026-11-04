<?php

return [
    'meta' => [
        'title' => 'HSN and SAC codes on GST invoices | :app',
        'description' => 'What HSN and SAC codes are, how many digits your GST invoices need, and how InvoiceInk puts a code on every line, with AI suggestions.',
    ],

    'hero' => [
        'badge' => 'HSN & SAC codes',
        'title' => 'The right code',
        'accent' => 'on every line.',
        'intro' => 'GST invoices need an HSN code for goods or a SAC code for services. InvoiceInk keeps one on each line, and its AI can suggest the code and the rate from your description.',
    ],

    'mock' => [
        'description' => 'Website design and development',
        'code' => 'HSN/SAC 998314',
        'rate' => 'GST 18%',
        'suggestion' => 'Suggested by AI: IT design and development services. Check it before saving.',
    ],

    'kinds' => [
        'heading' => 'HSN or SAC?',
        'hsn' => [
            'title' => 'HSN, for goods',
            'body' => 'The Harmonised System of Nomenclature: an international code for goods, from 4 to 8 digits. Laptops and computers are 8471, for example.',
        ],
        'sac' => [
            'title' => 'SAC, for services',
            'body' => 'The Services Accounting Code: India’s code for services, 6 digits starting with 99. IT design and development is 998314.',
        ],
    ],

    'digits' => [
        'heading' => 'How many digits?',
        'intro' => 'It depends on your aggregate turnover in the previous financial year. This is Notification 78/2020-Central Tax, in force since 1 April 2021.',
        'columns' => [
            'turnover' => 'Turnover last year',
            'digits' => 'Digits on your tax invoices',
        ],
        'rows' => [
            ['turnover' => 'Up to ₹5 crore', 'digits' => '4 digits on invoices to registered customers (B2B). Optional on invoices to unregistered customers (B2C).'],
            ['turnover' => 'Above ₹5 crore', 'digits' => '6 digits on every tax invoice.'],
        ],
        'note' => 'A separately notified list of chemicals needs 8 digits whatever your turnover.',
    ],

    'examples' => [
        'heading' => 'Common SAC codes',
        'columns' => [
            'code' => 'SAC',
            'service' => 'Service',
        ],
        'rows' => [
            ['code' => '998311', 'service' => 'Management consulting and management services'],
            ['code' => '998313', 'service' => 'IT consulting and support services'],
            ['code' => '998314', 'service' => 'IT design and development services'],
            ['code' => '998315', 'service' => 'Hosting and IT infrastructure provisioning services'],
            ['code' => '998316', 'service' => 'IT infrastructure and network management services'],
        ],
        'note' => 'Check the official list before relying on a code.',
    ],

    'in_app' => [
        'heading' => 'How InvoiceInk handles codes',
        'field' => [
            'title' => 'One field per line',
            'body' => 'Add the HSN or SAC code to any line. It prints under the description on the PDF and on the invoice you share.',
        ],
        'ai' => [
            'title' => 'AI suggestions',
            'body' => 'Ask for a suggestion and AI reads the line’s description, then proposes a code, a GST rate from your country’s rates and a one-line reason. Nothing saves until you do.',
        ],
        'control' => [
            'title' => 'You switch it on',
            'body' => 'AI is off until an owner or admin turns it on in Settings. The description is sent to Anthropic, our AI provider, to make the suggestion.',
            'link' => 'Read the privacy policy',
        ],
    ],

    'disclaimer' => 'A summary written in November 2026. It isn’t tax advice: check the current rules or ask your CA.',

    'faq' => [
        'heading' => 'Questions',
        'items' => [
            [
                'question' => 'Do I need HSN codes on invoices to unregistered customers?',
                'answer' => 'If your turnover last year was up to ₹5 crore, it’s optional on those (B2C) invoices and required on B2B ones. Above ₹5 crore, every tax invoice needs 6 digits.',
            ],
            [
                'question' => 'Can the AI suggestion be wrong?',
                'answer' => 'Yes. It can misread a description or pick a code that’s close but not right. Treat it as a first draft and check it, especially for goods.',
            ],
            [
                'question' => 'Does InvoiceInk check that a code is valid?',
                'answer' => 'No. It keeps the code you type as it is. AI suggestions are limited to codes of 4 to 8 digits.',
            ],
        ],
    ],
];
