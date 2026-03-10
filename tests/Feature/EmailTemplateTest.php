<?php

use App\Enums\EmailTemplateType;
use App\Mail\Templates\DefaultEmailTemplates;
use App\Mail\Templates\EmailTemplateService;
use App\Mail\Templates\TemplateVariableResolver;
use App\Models\EmailTemplate;
use App\Models\User;

// --- Service tests ---

test('resolve returns default when no override exists', function () {
    $org = createOrganizationWithLocation();
    $service = new EmailTemplateService;

    $result = $service->resolve($org, EmailTemplateType::InvoiceInitial);

    expect($result['is_customized'])->toBeFalse()
        ->and($result['subject'])->toContain('{{invoice_number}}')
        ->and($result['body'])->toContain('{{customer_name}}');
});

test('resolve returns override when it exists', function () {
    $org = createOrganizationWithLocation();
    $service = new EmailTemplateService;

    $service->save($org, EmailTemplateType::InvoiceInitial, 'Custom subject', '<p>Custom body</p>');

    $result = $service->resolve($org, EmailTemplateType::InvoiceInitial);

    expect($result['is_customized'])->toBeTrue()
        ->and($result['subject'])->toBe('Custom subject')
        ->and($result['body'])->toBe('<p>Custom body</p>');
});

test('resetToDefault deletes override and returns default', function () {
    $org = createOrganizationWithLocation();
    $service = new EmailTemplateService;

    $service->save($org, EmailTemplateType::InvoiceInitial, 'Custom', '<p>Custom</p>');
    expect(EmailTemplate::count())->toBe(1);

    $service->resetToDefault($org, EmailTemplateType::InvoiceInitial);
    expect(EmailTemplate::count())->toBe(0);

    $result = $service->resolve($org, EmailTemplateType::InvoiceInitial);
    expect($result['is_customized'])->toBeFalse();
});

test('save creates or updates override', function () {
    $org = createOrganizationWithLocation();
    $service = new EmailTemplateService;

    $service->save($org, EmailTemplateType::InvoiceReminder, 'Subject v1', '<p>v1</p>');
    expect(EmailTemplate::count())->toBe(1);

    $service->save($org, EmailTemplateType::InvoiceReminder, 'Subject v2', '<p>v2</p>');
    expect(EmailTemplate::count())->toBe(1);
    expect(EmailTemplate::first()->subject)->toBe('Subject v2');
});

test('listForOrganization shows all 8 types with customization status', function () {
    $org = createOrganizationWithLocation();
    $service = new EmailTemplateService;

    $service->save($org, EmailTemplateType::InvoiceInitial, 'Custom', '<p>Custom</p>');

    $list = $service->listForOrganization($org);

    expect($list)->toHaveCount(8);

    $initial = collect($list)->firstWhere('type', 'invoice_initial');
    expect($initial['is_customized'])->toBeTrue();

    $reminder = collect($list)->firstWhere('type', 'invoice_reminder');
    expect($reminder['is_customized'])->toBeFalse();
});

// --- Variable resolver tests ---

test('resolves template variables from invoice', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-VAR-001',
        'status' => 'sent',
    ]);

    $variables = TemplateVariableResolver::resolve($invoice);

    expect($variables['{{invoice_number}}'])->toBe('INV-VAR-001')
        ->and($variables['{{customer_name}}'])->not->toBeEmpty()
        ->and($variables['{{view_url}}'])->toContain('/invoices/view/');
});

test('renders template with variables substituted', function () {
    $template = 'Invoice #{{invoice_number}} for {{customer_name}}';
    $variables = [
        '{{invoice_number}}' => 'INV-001',
        '{{customer_name}}' => 'Acme Corp',
    ];

    $rendered = TemplateVariableResolver::render($template, $variables);

    expect($rendered)->toBe('Invoice #INV-001 for Acme Corp');
});

test('render method substitutes variables in resolved template', function () {
    $invoice = createInvoiceWithItems([
        'invoice_number' => 'INV-RENDER-001',
    ]);

    $service = new EmailTemplateService;
    $result = $service->render($invoice, EmailTemplateType::InvoiceInitial);

    expect($result['subject'])->toContain('INV-RENDER-001')
        ->and($result['body'])->toContain('INV-RENDER-001')
        ->and($result['body'])->not->toContain('{{invoice_number}}');
});

// --- Default templates ---

test('all 8 template types have defaults', function () {
    foreach (EmailTemplateType::cases() as $type) {
        $default = DefaultEmailTemplates::get($type);

        expect($default)->toHaveKeys(['subject', 'body'])
            ->and($default['subject'])->not->toBeEmpty()
            ->and($default['body'])->not->toBeEmpty();
    }
});

test('available variables lists all expected variables', function () {
    $vars = DefaultEmailTemplates::availableVariables();

    expect($vars)->toHaveKey('{{customer_name}}')
        ->and($vars)->toHaveKey('{{invoice_number}}')
        ->and($vars)->toHaveKey('{{amount_due}}')
        ->and($vars)->toHaveKey('{{view_url}}');
});

// --- Controller tests ---

test('index page renders with all templates', function () {
    $user = User::factory()->withPersonalTeam()->create();
    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user)
        ->get('/email-templates')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('EmailTemplates/Index')
            ->has('templates', 8)
            ->has('variables')
        );
});

test('edit page renders with template data', function () {
    $user = User::factory()->withPersonalTeam()->create();
    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user)
        ->get('/email-templates/invoice_initial')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('EmailTemplates/Edit')
            ->where('templateType', 'invoice_initial')
            ->has('template.subject')
            ->has('template.body')
            ->has('defaultTemplate')
            ->has('variables')
        );
});

test('update saves template override', function () {
    $user = User::factory()->withPersonalTeam()->create();
    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user)
        ->put('/email-templates/invoice_initial', [
            'subject' => 'My custom subject',
            'body' => '<p>My custom body</p>',
        ])
        ->assertRedirect();

    expect(EmailTemplate::where('template_type', 'invoice_initial')->exists())->toBeTrue();
});

test('destroy resets template to default', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);

    EmailTemplate::create([
        'organization_id' => $org->id,
        'template_type' => 'invoice_initial',
        'subject' => 'Custom',
        'body' => '<p>Custom</p>',
    ]);

    $this->actingAs($user)
        ->delete('/email-templates/invoice_initial')
        ->assertRedirect();

    expect(EmailTemplate::count())->toBe(0);
});

test('resolve endpoint returns rendered template for invoice', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $org = createOrganizationWithLocation([], [], $user);
    $invoice = createInvoiceWithItems(
        ['invoice_number' => 'INV-RESOLVE-001'],
        null,
        $org,
    );

    $this->actingAs($user)
        ->get('/api/email-templates/resolve?template_type=invoice_initial&invoice_id='.$invoice->id)
        ->assertSuccessful()
        ->assertJsonFragment(['is_customized' => false])
        ->assertJsonMissing(['{{invoice_number}}']);
});

test('preview endpoint renders with sample data', function () {
    $user = User::factory()->withPersonalTeam()->create();
    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user)
        ->postJson('/api/email-templates/preview', [
            'subject' => 'Invoice #{{invoice_number}}',
            'body' => '<p>Dear {{customer_name}}</p>',
        ])
        ->assertSuccessful()
        ->assertJsonMissing(['{{invoice_number}}'])
        ->assertJsonMissing(['{{customer_name}}']);
});

test('unauthenticated users cannot access email templates', function () {
    $this->get('/email-templates')->assertRedirect('/login');
    $this->put('/email-templates/invoice_initial', ['subject' => 'x', 'body' => 'x'])->assertRedirect('/login');
});

test('update validates required fields', function () {
    $user = User::factory()->withPersonalTeam()->create();
    createOrganizationWithLocation([], [], $user);

    $this->actingAs($user)
        ->put('/email-templates/invoice_initial', [
            'subject' => '',
            'body' => '',
        ])
        ->assertSessionHasErrors(['subject', 'body']);
});
