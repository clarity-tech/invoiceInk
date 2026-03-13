<?php

use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;
use App\Models\User;

it('loads the email templates index page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'tpl-index@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);
    $this->actingAs($user);

    $page = $this->visit('/email-templates');

    $page->assertPathIs('/email-templates')
        ->assertNoJavascriptErrors()
        ->assertSee('Email Templates')
        ->assertSee('Invoice Templates')
        ->assertSee('Estimate Templates')
        ->assertSee('Initial Invoice')
        ->assertSee('Invoice Reminder')
        ->assertSee('Initial Estimate');
});

it('shows Default badge for non-customized templates', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'tpl-default@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);
    $this->actingAs($user);

    $page = $this->visit('/email-templates');

    $page->assertSee('Default');
});

it('shows Customized badge when template is overridden', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'tpl-custom@example.test',
    ]);

    $org = createOrganizationWithLocation([], [], $user);

    EmailTemplate::create([
        'organization_id' => $org->id,
        'template_type' => EmailTemplateType::InvoiceInitial,
        'subject' => 'Custom Subject',
        'body' => '<p>Custom body</p>',
    ]);

    $this->actingAs($user);

    $page = $this->visit('/email-templates');

    $page->assertSee('Customized');
});

it('loads the edit page for a template type', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'tpl-edit@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);
    $this->actingAs($user);

    $page = $this->visit('/email-templates/invoice_initial');

    $page->assertPathIs('/email-templates/invoice_initial')
        ->assertNoJavascriptErrors()
        ->assertSee('Initial Invoice')
        ->assertSee('Save Template')
        ->assertSee('Insert Variable')
        ->assertSee('{{customer_name}}')
        ->assertSee('{{invoice_number}}');
});

it('navigates from index to edit page', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'tpl-nav@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);
    $this->actingAs($user);

    $page = $this->visit('/email-templates');

    $page->click('a:has-text("Customize") >> nth=0')
        ->assertPathBeginsWith('/email-templates/')
        ->assertNoJavascriptErrors()
        ->assertSee('Save Template');
});

it('shows available variables in the sidebar', function () {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'tpl-vars@example.test',
    ]);

    createOrganizationWithLocation([], [], $user);
    $this->actingAs($user);

    $page = $this->visit('/email-templates/invoice_initial');

    $page->assertSee('Insert Variable')
        ->assertSee('{{customer_name}}')
        ->assertSee('{{amount_due}}')
        ->assertSee('{{view_url}}')
        ->assertSee('{{due_date}}');
});

it('redirects guests to login', function () {
    $page = $this->visit('/email-templates');
    $page->assertPathIs('/login');
});
