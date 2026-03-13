<?php

use App\Enums\EmailTemplateType;
use App\Mail\Templates\DefaultEmailTemplates;
use App\Mail\Templates\TemplateVariableResolver;

// --- Enum tests ---

test('enum has 8 cases', function () {
    expect(EmailTemplateType::cases())->toHaveCount(8);
});

test('enum values are snake_case strings', function () {
    foreach (EmailTemplateType::cases() as $case) {
        expect($case->value)->toMatch('/^[a-z]+(_[a-z]+)+$/');
    }
});

test('each case has a label', function () {
    foreach (EmailTemplateType::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
});

test('each case has a description', function () {
    foreach (EmailTemplateType::cases() as $case) {
        expect($case->description())->toBeString()->not->toBeEmpty();
    }
});

test('document type is invoice or estimate', function () {
    foreach (EmailTemplateType::cases() as $case) {
        expect($case->documentType())->toBeIn(['invoice', 'estimate']);
    }
});

test('4 invoice types and 4 estimate types', function () {
    $invoice = EmailTemplateType::forDocumentType('invoice');
    $estimate = EmailTemplateType::forDocumentType('estimate');

    expect($invoice)->toHaveCount(4)
        ->and($estimate)->toHaveCount(4);
});

test('can create from string value', function () {
    $type = EmailTemplateType::from('invoice_initial');
    expect($type)->toBe(EmailTemplateType::InvoiceInitial);
});

test('tryFrom returns null for invalid value', function () {
    $type = EmailTemplateType::tryFrom('nonexistent');
    expect($type)->toBeNull();
});

// --- Default templates ---

test('all defaults contain subject and body keys', function () {
    foreach (EmailTemplateType::cases() as $type) {
        $default = DefaultEmailTemplates::get($type);

        expect($default)->toBeArray()
            ->and($default)->toHaveKeys(['subject', 'body']);
    }
});

test('all default subjects contain at least one variable', function () {
    foreach (EmailTemplateType::cases() as $type) {
        $default = DefaultEmailTemplates::get($type);

        expect($default['subject'])->toContain('{{');
    }
});

test('all default bodies contain view_url variable', function () {
    foreach (EmailTemplateType::cases() as $type) {
        $default = DefaultEmailTemplates::get($type);

        expect($default['body'])->toContain('{{view_url}}');
    }
});

test('invoice defaults contain invoice-specific language', function () {
    $initial = DefaultEmailTemplates::get(EmailTemplateType::InvoiceInitial);
    expect($initial['body'])->toContain('invoice');

    $overdue = DefaultEmailTemplates::get(EmailTemplateType::InvoiceOverdue);
    expect($overdue['body'])->toContain('overdue');

    $thankYou = DefaultEmailTemplates::get(EmailTemplateType::InvoiceThankYou);
    expect($thankYou['body'])->toContain('payment');
});

test('estimate defaults contain estimate-specific language', function () {
    $initial = DefaultEmailTemplates::get(EmailTemplateType::EstimateInitial);
    expect($initial['body'])->toContain('estimate');

    $expired = DefaultEmailTemplates::get(EmailTemplateType::EstimateExpired);
    expect($expired['body'])->toContain('expired');
});

test('available variables returns expected keys', function () {
    $vars = DefaultEmailTemplates::availableVariables();

    expect($vars)->toBeArray()
        ->and(array_keys($vars))->each->toMatch('/^\{\{[a-z_]+\}\}$/');
});

// --- Variable resolver (static methods) ---

test('render replaces all variables in template', function () {
    $template = 'Hello {{name}}, your order #{{order_id}} is {{status}}.';
    $variables = [
        '{{name}}' => 'John',
        '{{order_id}}' => '42',
        '{{status}}' => 'shipped',
    ];

    $result = TemplateVariableResolver::render($template, $variables);

    expect($result)->toBe('Hello John, your order #42 is shipped.');
});

test('render handles missing variables gracefully', function () {
    $template = 'Hello {{name}}, your {{missing}} is ready.';
    $variables = ['{{name}}' => 'John'];

    $result = TemplateVariableResolver::render($template, $variables);

    expect($result)->toBe('Hello John, your {{missing}} is ready.');
});

test('render handles empty template', function () {
    $result = TemplateVariableResolver::render('', ['{{x}}' => 'y']);
    expect($result)->toBe('');
});

test('render handles template with no variables', function () {
    $result = TemplateVariableResolver::render('Plain text', ['{{x}}' => 'y']);
    expect($result)->toBe('Plain text');
});

test('resolve returns all expected variable keys', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-UNIT-001']);

    $variables = TemplateVariableResolver::resolve($invoice);

    expect($variables)->toHaveKeys([
        '{{customer_name}}',
        '{{invoice_number}}',
        '{{amount_due}}',
        '{{subtotal}}',
        '{{tax_amount}}',
        '{{currency}}',
        '{{due_date}}',
        '{{issue_date}}',
        '{{organization_name}}',
        '{{organization_email}}',
        '{{view_url}}',
        '{{days_overdue}}',
        '{{status}}',
    ]);
});

test('resolve escapes HTML in variable values', function () {
    $invoice = createInvoiceWithItems(['invoice_number' => 'INV-XSS-001']);

    // Customer name with HTML
    $invoice->customer->update(['name' => '<script>alert("xss")</script>']);
    $invoice->load('customer');

    $variables = TemplateVariableResolver::resolve($invoice);

    expect($variables['{{customer_name}}'])->not->toContain('<script>');
});

test('resolve generates correct view_url for invoices', function () {
    $invoice = createInvoiceWithItems(['type' => 'invoice']);
    $variables = TemplateVariableResolver::resolve($invoice);

    expect($variables['{{view_url}}'])->toContain('/invoices/view/');
});

test('resolve generates correct view_url for estimates', function () {
    $estimate = createInvoiceWithItems(['type' => 'estimate']);
    $variables = TemplateVariableResolver::resolve($estimate);

    expect($variables['{{view_url}}'])->toContain('/estimates/view/');
});

test('days_overdue is 0 for future due dates', function () {
    $invoice = createInvoiceWithItems([
        'due_at' => now()->addDays(30),
    ]);

    $variables = TemplateVariableResolver::resolve($invoice);

    expect($variables['{{days_overdue}}'])->toBe('0');
});

test('days_overdue is calculated for past due dates', function () {
    $invoice = createInvoiceWithItems([
        'due_at' => now()->subDays(5),
    ]);

    $variables = TemplateVariableResolver::resolve($invoice);

    expect((int) $variables['{{days_overdue}}'])->toBeGreaterThanOrEqual(4);
});
