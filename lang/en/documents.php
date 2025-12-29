<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for document-related terms that are
    | shared between invoices and estimates throughout the application.
    |
    */

    'headers' => [
        'invoice' => 'Invoice',
        'estimate' => 'Estimate',
        'invoice_upper' => 'INVOICE',
        'estimate_upper' => 'ESTIMATE',
        'from' => 'From:',
        'to' => 'To:',
        'bill_to' => 'Bill To:',
        'ship_to' => 'Ship To:',
        'billing_address' => 'BILLING ADDRESS',
        'shipping_address' => 'SHIPPING ADDRESS',
        'organization_location' => 'Organization Location',
        'invoice_details' => 'Invoice Details',
        'customer_notes' => 'Customer Notes',
        'attachments' => 'Attachments',
        'existing_attachments' => 'Existing Attachments',
        'email_to' => 'Email To :customer',
        'edit_customer' => 'Edit Customer',
        'add_new_customer' => 'Add New Customer',
        'edit_organization' => 'Edit Organization',
        'add_new_organization' => 'Add New Organization',
        'edit_location' => 'Edit Location',
        'add_new_location' => 'Add New Location',
        'manage_your_business' => 'Manage Your Business',
        'locations' => 'Locations',
        'numbering_series_management' => 'Numbering Series Management',
        'numbering_series' => 'Numbering Series',
        'edit_numbering_series' => 'Edit Numbering Series',
        'create_new_numbering_series' => 'Create New Numbering Series',
        'organization_setup' => 'Organization Setup',
        'company_information' => 'Company Information',
        'configuration' => 'Configuration',
        'contact_details' => 'Contact Details',
        'estimate_details' => 'Estimate Details',
    ],

    'fields' => [
        'invoice_number' => 'Invoice Number',
        'estimate_number' => 'Estimate Number',
        'document_number' => 'Document Number',
        'issue_date' => 'Issue Date',
        'issued_at' => 'Issue Date',
        'due_date' => 'Due Date',
        'due_at' => 'Due Date',
        'valid_until' => 'Valid Until',
        'status' => 'Status',
        'document_type' => 'Document Type',
        'organization' => 'Organization',
        'customer' => 'Customer',
        'organization_location' => 'Organization Location',
        'customer_location' => 'Customer Location',
        'numbering_series' => 'Numbering Series',
        'numbering_series_optional' => 'Numbering Series (Optional)',
        'gstin' => 'GSTIN:',
        'tax_id' => 'Tax ID:',
        'sac' => 'SAC:',
        'email' => 'Email:',
        'phone' => 'Phone:',
        'website' => 'Website:',
        'address' => 'Address',
        'notes' => 'Notes',
        'terms' => 'Terms',
        'currency' => 'Currency',
    ],

    'financial' => [
        'subtotal' => 'Subtotal:',
        'tax' => 'Tax:',
        'total' => 'Total:',
        'total_amount' => 'Total Amount',
        'estimated_total' => 'Estimated Total',
        'grand_total' => 'Grand Total',
        'amount_due' => 'Amount Due',
        'balance_due' => 'Balance Due',
    ],

    'table' => [
        'line_items' => 'Line Items',
        'items' => 'Items',
        'description' => 'Description',
        'qty' => 'Qty',
        'quantity' => 'Quantity',
        'unit_price' => 'Unit Price',
        'price' => 'Price',
        'rate' => 'Rate',
        'tax_rate' => 'Tax Rate',
        'tax_percent' => 'Tax %',
        'amount' => 'Amount',
        'line_total' => 'Line Total',
        'item_total' => 'Item Total',
    ],

    'status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'viewed' => 'Viewed',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'types' => [
        'invoice' => 'Invoice',
        'estimate' => 'Estimate',
        'quote' => 'Quote',
        'proforma' => 'Proforma Invoice',
        'credit_note' => 'Credit Note',
        'debit_note' => 'Debit Note',
    ],

    'placeholders' => [
        'select_customer' => 'Select a customer...',
        'select_organization' => 'Select an organization...',
        'select_location' => 'Select a location...',
        'enter_description' => 'Enter item description...',
        'enter_notes' => 'Enter additional notes...',
        'enter_terms' => 'Enter payment terms...',
    ],
];
