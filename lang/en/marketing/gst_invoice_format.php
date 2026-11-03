<?php

return [
    'meta' => [
        'title' => 'GST invoice format: what Rule 46 requires | :app',
        'description' => 'Every particular a GST tax invoice must show under Rule 46 of the CGST Rules, in plain English, and which ones InvoiceInk fills in for you.',
    ],

    'hero' => [
        'badge' => 'GST invoice format',
        'title' => 'What goes on',
        'accent' => 'a GST invoice.',
        'intro' => 'Rule 46 of the CGST Rules lists what a tax invoice must show. There’s no set layout, only a list of particulars. Here it is in plain English, with what InvoiceInk fills in for you.',
    ],

    'checklist' => [
        'heading' => 'The Rule 46 checklist',
        'intro' => 'The letters match the clauses of Rule 46. Some only apply in certain cases, such as exports or e-invoices.',
        'columns' => [
            'clause' => 'Clause',
            'particular' => 'What the invoice must show',
            'invoiceink' => 'In InvoiceInk',
        ],
        'status' => [
            'yes' => 'Yes',
            'partly' => 'Partly',
            'no' => 'Not yet',
        ],
        'rows' => [
            ['clause' => 'a', 'status' => 'yes', 'particular' => 'Your name, address and GSTIN.', 'note' => 'From your organization’s settings.'],
            ['clause' => 'b', 'status' => 'partly', 'particular' => 'A consecutive serial number of up to 16 characters, using letters, numbers, hyphens and slashes, unique for the financial year.', 'note' => 'Generated for you; the default, INV-26001, is 9 characters. Custom patterns aren’t checked against the 16-character limit, so keep yours short.'],
            ['clause' => 'c', 'status' => 'yes', 'particular' => 'The date of issue.', 'note' => 'Printed as the invoice date.'],
            ['clause' => 'd', 'status' => 'yes', 'particular' => 'The recipient’s name, address and GSTIN, if they’re registered.', 'note' => 'Printed under Bill To.'],
            ['clause' => 'e–f', 'status' => 'yes', 'particular' => 'For an unregistered recipient: name, address and delivery address with the state name and code, if the supply is ₹50,000 or more or the recipient asks.', 'note' => 'Name and address under Bill To, a separate Ship To when it differs, and the place of supply with its state code.'],
            ['clause' => 'g', 'status' => 'yes', 'particular' => 'The HSN code for goods or SAC code for services.', 'note' => 'One code per line, with optional AI suggestions.'],
            ['clause' => 'h', 'status' => 'yes', 'particular' => 'A description of the goods or services.', 'note' => 'Each line’s description.'],
            ['clause' => 'i', 'status' => 'partly', 'particular' => 'For goods, the quantity and its unit or Unique Quantity Code (UQC).', 'note' => 'Whole-number quantities only, and no unit field yet. Put the unit in the description.'],
            ['clause' => 'j', 'status' => 'yes', 'particular' => 'The total value of the supply.', 'note' => 'The invoice total, also in words.'],
            ['clause' => 'k', 'status' => 'partly', 'particular' => 'The taxable value, after any discount or abatement.', 'note' => 'Printed per line and as the sub-total. Discounts aren’t supported yet.'],
            ['clause' => 'l–m', 'status' => 'partly', 'particular' => 'The rate and amount of central, State, integrated and Union territory tax, and cess.', 'note' => 'CGST, SGST, UTGST and IGST, with a line for each rate. Cess isn’t supported yet.'],
            ['clause' => 'n', 'status' => 'yes', 'particular' => 'For an inter-State supply, the place of supply with the name of the State.', 'note' => 'Printed with its code, such as Karnataka (29).'],
            ['clause' => 'o', 'status' => 'yes', 'particular' => 'The delivery address, if it differs from the place of supply.', 'note' => 'Printed as Ship To.'],
            ['clause' => 'p', 'status' => 'no', 'particular' => 'Whether tax is payable on reverse charge.', 'note' => 'No reverse-charge marker yet. Add a line in the notes if it applies.'],
            ['clause' => 'q', 'status' => 'no', 'particular' => 'Your signature or digital signature. A proviso waives this for electronic invoices issued under the Information Technology Act, 2000.', 'note' => 'No signature field yet.'],
            ['clause' => 'r', 'status' => 'no', 'particular' => 'For an e-invoice, a QR code with the Invoice Reference Number (IRN).', 'note' => 'InvoiceInk doesn’t generate e-invoices yet.'],
            ['clause' => 's', 'status' => 'no', 'particular' => 'If your turnover is above the e-invoicing limit but an invoice isn’t an e-invoice, a declaration saying why.', 'note' => 'Not supported yet.'],
            ['clause' => 'proviso', 'status' => 'no', 'particular' => 'For exports, an endorsement such as “Supply meant for export under bond or letter of undertaking without payment of integrated tax”, and the country of destination.', 'note' => 'Put the endorsement in the invoice notes.'],
        ],
    ],

    'other_documents' => [
        'heading' => 'Other GST documents',
        'body' => 'InvoiceInk issues tax invoices and estimates. It doesn’t issue bills of supply for composition dealers, credit or debit notes, or e-invoices yet.',
    ],

    'disclaimer' => 'A plain-English summary of Rule 46 of the CGST Rules, 2017, as amended up to November 2024, written in November 2026. It isn’t tax advice: check the current rules or ask your CA.',

    'faq' => [
        'heading' => 'Questions',
        'items' => [
            [
                'question' => 'Is there an official GST invoice format?',
                'answer' => 'No. Rule 46 lists what a tax invoice must contain, not how it looks. Any layout works as long as the particulars are there.',
            ],
            [
                'question' => 'How long can a GST invoice number be?',
                'answer' => 'Up to 16 characters: letters, numbers, hyphens and slashes, in one or more series, and unique within the financial year.',
            ],
            [
                'question' => 'Do I need my customer’s GSTIN?',
                'answer' => 'If they’re registered, yes. For an unregistered customer, show their name and address, with the delivery address and state, when the supply is ₹50,000 or more or whenever they ask for it.',
            ],
            [
                'question' => 'Does a GST invoice need a signature?',
                'answer' => 'Rule 46 asks for your signature or digital signature, but a proviso waives it for electronic invoices issued under the Information Technology Act, 2000.',
            ],
        ],
    ],
];
