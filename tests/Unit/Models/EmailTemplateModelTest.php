<?php

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;
use Illuminate\Database\QueryException;

test('email template has correct fillable fields', function () {
    $template = new EmailTemplate;

    expect($template->getFillable())->toContain('organization_id')
        ->and($template->getFillable())->toContain('template_type')
        ->and($template->getFillable())->toContain('subject')
        ->and($template->getFillable())->toContain('body');
});

test('template_type is cast to enum', function () {
    $org = createOrganizationWithLocation();

    $template = EmailTemplate::create([
        'organization_id' => $org->id,
        'template_type' => 'invoice_initial',
        'subject' => 'Test',
        'body' => '<p>Test</p>',
    ]);

    expect($template->template_type)->toBeInstanceOf(EmailTemplateType::class)
        ->and($template->template_type)->toBe(EmailTemplateType::InvoiceInitial);
});

test('template belongs to organization', function () {
    $org = createOrganizationWithLocation();

    $template = EmailTemplate::create([
        'organization_id' => $org->id,
        'template_type' => 'invoice_initial',
        'subject' => 'Test',
        'body' => '<p>Test</p>',
    ]);

    expect($template->organization->id)->toBe($org->id);
});

test('organization has email templates relationship', function () {
    $org = createOrganizationWithLocation();

    EmailTemplate::create([
        'organization_id' => $org->id,
        'template_type' => 'invoice_initial',
        'subject' => 'Subject 1',
        'body' => '<p>Body 1</p>',
    ]);

    EmailTemplate::create([
        'organization_id' => $org->id,
        'template_type' => 'invoice_reminder',
        'subject' => 'Subject 2',
        'body' => '<p>Body 2</p>',
    ]);

    expect($org->emailTemplates)->toHaveCount(2);
});

test('unique constraint prevents duplicate org+type combinations', function () {
    $org = createOrganizationWithLocation();

    EmailTemplate::create([
        'organization_id' => $org->id,
        'template_type' => 'invoice_initial',
        'subject' => 'First',
        'body' => '<p>First</p>',
    ]);

    expect(fn () => EmailTemplate::create([
        'organization_id' => $org->id,
        'template_type' => 'invoice_initial',
        'subject' => 'Duplicate',
        'body' => '<p>Duplicate</p>',
    ]))->toThrow(QueryException::class);
});

test('different orgs can have same template type', function () {
    $org1 = createOrganizationWithLocation();
    $org2 = createOrganizationWithLocation();

    EmailTemplate::create([
        'organization_id' => $org1->id,
        'template_type' => 'invoice_initial',
        'subject' => 'Org 1',
        'body' => '<p>Org 1</p>',
    ]);

    $template2 = EmailTemplate::create([
        'organization_id' => $org2->id,
        'template_type' => 'invoice_initial',
        'subject' => 'Org 2',
        'body' => '<p>Org 2</p>',
    ]);

    expect(EmailTemplate::count())->toBe(2)
        ->and($template2->subject)->toBe('Org 2');
});

test('deleting organization cascades to email templates', function () {
    $org = createOrganizationWithLocation();

    EmailTemplate::create([
        'organization_id' => $org->id,
        'template_type' => 'invoice_initial',
        'subject' => 'Test',
        'body' => '<p>Test</p>',
    ]);

    expect(EmailTemplate::count())->toBe(1);

    $org->delete();

    expect(EmailTemplate::count())->toBe(0);
});
