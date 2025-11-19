<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Form Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for form labels, steps, and
    | validation messages throughout the application.
    |
    */

    'labels' => [
        'customer' => 'Customer',
        'customer_required' => 'Customer *',
        'select_customer' => 'Select Customer',
        'organization' => 'Organization',
        'organization_required' => 'Organization *',
        'select_organization' => 'Select Organization',
        'location' => 'Location',
        'location_required' => 'Location *',
        'select_location' => 'Select Location',
        'organization_location' => 'Organization Location',
        'customer_location' => 'Customer Location',
        'description' => 'Description',
        'description_required' => 'Description *',
        'quantity' => 'Quantity',
        'qty' => 'Qty',
        'qty_required' => 'Qty *',
        'price' => 'Price',
        'price_required' => 'Price (:currency) *',
        'unit_price' => 'Unit Price',
        'unit_price_required' => 'Unit Price *',
        'tax_rate' => 'Tax Rate',
        'tax_percent' => 'Tax %',
        'amount' => 'Amount',
        'total' => 'Total',
        'subtotal' => 'Subtotal',
        'tax' => 'Tax',
        'invoice_number' => 'Invoice Number',
        'estimate_number' => 'Estimate Number',
        'issue_date' => 'Issue Date',
        'due_date' => 'Due Date',
        'status' => 'Status',
        'type' => 'Type',
        'currency' => 'Currency',
        'notes' => 'Notes',
        'terms' => 'Terms',
        'email_recipients' => 'Email Recipients',
        'numbering_series' => 'Numbering Series',
        'auto_select_series' => 'Auto-select best series',
        'organization_wide' => '(Organization-wide)',
    ],

    'steps' => [
        'details' => 'Details',
        'items' => 'Items',
        'review' => 'Review',
        'step_1' => 'Step 1: Basic Details',
        'step_2' => 'Step 2: Items',
        'step_3' => 'Step 3: Review',
        'step_1_details' => 'Step 1: Basic Details',
        'step_2_line_items' => 'Step 2: Line Items',
        'step_3_review' => 'Step 3: Review',
    ],

    'options' => [
        'select_option' => 'Select an option...',
        'auto_select' => 'Auto-select',
        'manual_select' => 'Manual select',
        'yes' => 'Yes',
        'no' => 'No',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'placeholders' => [
        'enter_description' => 'Enter item description...',
        'enter_quantity' => 'Enter quantity...',
        'enter_price' => 'Enter price...',
        'enter_notes' => 'Enter additional notes...',
        'enter_terms' => 'Enter payment terms...',
        'select_customer' => 'Search and select a customer...',
        'select_organization' => 'Search and select an organization...',
        'search_customers' => 'Search customers...',
        'search_organizations' => 'Search organizations...',
    ],

    'validation' => [
        'required_field' => 'This field is required',
        'invalid_email' => 'Please enter a valid email address',
        'invalid_number' => 'Please enter a valid number',
        'invalid_date' => 'Please enter a valid date',
        'min_value' => 'Value must be at least :min',
        'max_value' => 'Value must not exceed :max',
        'positive_number' => 'Value must be a positive number',
        'select_required' => 'Please make a selection',
    ],

    'hints' => [
        'next_invoice_number' => 'Next invoice number: :number',
        'next_estimate_number' => 'Next estimate number: :number',
        'required_fields' => 'Fields marked with * are required',
        'auto_calculate' => 'Totals will be calculated automatically',
        'tax_inclusive' => 'Tax inclusive pricing',
        'tax_exclusive' => 'Tax exclusive pricing',
    ],

    'messages' => [
        'organization_location_required' => 'Organization Location Required',
        'customer_location_required' => 'Customer Location Required',
        'organization_needs_location' => 'The selected organization needs at least one location.',
        'customer_needs_location' => 'The selected customer needs at least one location.',
        'manage_locations' => 'Manage locations →',
        'use_team_switcher' => 'Use the team switcher in the navigation to change organizations',
        'no_documents_found' => 'No documents found. Create your first invoice or estimate using the buttons above.',
        'unsaved_changes' => 'You have unsaved changes. Are you sure you want to leave?',
        'confirm_delete' => 'Are you sure you want to delete this :type?',
    ],
];