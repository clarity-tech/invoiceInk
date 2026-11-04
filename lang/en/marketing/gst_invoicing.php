<?php

return [
    'meta' => [
        'title' => 'Free GST invoicing software for small businesses | :app',
        'description' => 'Create GST invoices free: CGST and SGST or IGST from the place of supply, HSN/SAC codes, TDS and TCS, financial-year numbering, PDFs and payment tracking.',
    ],

    'hero' => [
        'badge' => 'Free GST invoicing',
        'title' => 'GST invoices,',
        'accent' => 'worked out for you.',
        'intro' => 'Add your GSTIN once. InvoiceInk picks CGST and SGST or IGST from the place of supply, prints an HSN or SAC code on every line, and numbers your invoices by financial year.',
    ],

    'split' => [
        'heading' => 'The right tax, every time',
        'intro' => 'InvoiceInk compares your state with the place of supply and splits the tax for you.',
        'same_state' => [
            'title' => 'Same state: CGST + SGST',
            'body' => 'The rate splits in half between CGST and SGST. In Chandigarh, Ladakh, Lakshadweep, Andaman and Nicobar, and Dadra and Nagar Haveli and Daman and Diu, the second half is UTGST.',
        ],
        'other_state' => [
            'title' => 'Another state: IGST',
            'body' => 'Bill a customer whose place of supply is in another state, and the full rate is charged as IGST.',
        ],
        'how' => [
            'title' => 'Read from the GSTINs',
            'body' => 'Your state comes from the first two digits of your GSTIN. The place of supply comes from the customer, and you can change it on any invoice.',
        ],
    ],

    'features' => [
        'heading' => 'Everything a small business bills with',
        'tax_invoice' => [
            'title' => 'Titled TAX INVOICE',
            'body' => 'With your GSTIN on file, invoices say TAX INVOICE and show both GSTINs and the place of supply with its state code.',
        ],
        'codes' => [
            'title' => 'HSN/SAC on every line',
            'body' => 'Type the code, or let AI suggest one with a tax rate from the line’s description. You check it before saving.',
            'link' => 'About HSN and SAC codes',
        ],
        'rates' => [
            'title' => 'GST rates built in',
            'body' => 'Pick 0%, 5%, 18% or 40%, or type any custom rate. Each rate gets its own CGST, SGST or IGST line in the totals.',
        ],
        'source_taxes' => [
            'title' => 'TDS and TCS',
            'body' => 'Add TDS or TCS with its section, such as 194J or 206C(1H). TDS comes off the total, TCS goes on, and both are printed.',
        ],
        'numbering' => [
            'title' => 'Financial-year numbering',
            'body' => 'The default series runs INV-26001, INV-26002 and so on, and starts again each April. Or set your own prefix and format.',
        ],
        'details' => [
            'title' => 'The details customers look for',
            'body' => 'The total in words, your bank details for payment, and a separate Ship To address when goods go somewhere else.',
        ],
    ],

    'limits' => [
        'heading' => 'What InvoiceInk doesn’t do yet',
        'body' => 'E-invoices with an IRN and QR code, credit and debit notes, bills of supply, cess, reverse-charge marking and GST return filing aren’t supported yet. If e-invoicing is mandatory for your turnover, those invoices have to go through the government’s invoice registration portal.',
        'link' => 'See the full GST invoice checklist',
    ],

    'faq' => [
        'heading' => 'Questions',
        'items' => [
            [
                'question' => 'Is InvoiceInk really free?',
                'answer' => 'Yes. The hosted app is free, with no paid plan, and the code is open source under the MIT licence.',
            ],
            [
                'question' => 'Do I need a GSTIN to use it?',
                'answer' => 'No. Without one, invoices are titled INVOICE and show a single tax line. Add your GSTIN in your organization’s settings to get TAX INVOICE and the CGST, SGST or IGST split.',
            ],
            [
                'question' => 'How does it choose between CGST and SGST or IGST?',
                'answer' => 'It compares your state, taken from your GSTIN, with the place of supply. If they match, it charges CGST and SGST (or UTGST); if not, IGST. The place of supply defaults from the customer and can be changed on each invoice.',
            ],
            [
                'question' => 'Can I file GST returns from InvoiceInk?',
                'answer' => 'No. InvoiceInk creates, sends and tracks invoices. It doesn’t file GSTR-1 or GSTR-3B or export them yet, so share your invoices with whoever files your returns.',
            ],
            [
                'question' => 'Can I invoice clients outside India?',
                'answer' => 'Yes, in nine currencies. Mark the customer as overseas and InvoiceInk leaves the place of supply and TDS/TCS off. It doesn’t add the export endorsement yet, so put that wording in the invoice notes.',
            ],
        ],
    ],
];
