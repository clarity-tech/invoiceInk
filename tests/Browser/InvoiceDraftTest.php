<?php

use App\Ai\Agents\InvoiceDrafter;
use App\Models\Invoice;
use App\Models\User;

it('drafts an invoice from the list, from a pasted message, for review before saving', function () {
    config(['ai.providers.anthropic.key' => 'test-key']);
    $user = User::factory()->withPersonalTeam()->create(['email' => 'ai-draft@example.test']);
    $org = createOrganizationWithLocation(['setup_completed_at' => now(), 'ai_drafting_enabled' => true], [], $user);
    createCustomerWithLocation([
        'name' => 'Northwind Traders Pvt Ltd',
        'emails' => [['name' => 'Accounts', 'email' => 'accounts@northwind.test']],
    ], [], $org);

    InvoiceDrafter::fake([[
        'customer' => ['name' => 'Northwind Traders', 'tax_number' => null, 'email' => null],
        'subject' => 'October development',
        'order_number' => 'PO-4512',
        'due_date' => null,
        'payment_terms_days' => 15,
        'currency' => 'INR',
        'items' => [['description' => 'Web application development', 'sac_code' => '998314', 'quantity' => 7.5, 'unit_price' => '2000.00', 'tax_rate_percent' => 18]],
        'stated_total' => '17700.00',
    ]]);

    $this->actingAs($user);

    $this->visit('/invoices')
        ->click('Draft with AI')
        ->waitForText('Draft from a document')
        ->fill('#draft-text', 'Please invoice us 7.5 hours of development at 2,000 an hour. PO-4512.')
        ->click('[role="dialog"] button:has-text("Draft invoice")')
        ->waitForText('Drafted from pasted text')
        ->assertSee('Quantities are whole numbers here')
        ->assertValue('input[placeholder="Customer\'s purchase order number"]', 'PO-4512')
        ->assertSee('accounts@northwind.test')
        ->assertNoJavaScriptErrors();

    expect(Invoice::count())->toBe(0);
});

it('drafts an estimate from a client enquiry on the estimate form', function () {
    config(['ai.providers.anthropic.key' => 'test-key']);
    $user = User::factory()->withPersonalTeam()->create(['email' => 'ai-estimate@example.test']);
    createOrganizationWithLocation(['setup_completed_at' => now(), 'ai_drafting_enabled' => true], [], $user);

    InvoiceDrafter::fake([[
        'customer' => ['name' => null, 'tax_number' => null, 'email' => null],
        'subject' => 'Website redesign',
        'order_number' => null,
        'due_date' => null,
        'payment_terms_days' => 15,
        'currency' => null,
        'items' => [['description' => 'Website redesign, five pages', 'sac_code' => null, 'quantity' => 1, 'unit_price' => '0', 'tax_rate_percent' => null]],
        'stated_total' => null,
    ]]);

    $this->actingAs($user);

    $this->visit('/estimates/create')
        ->click('Draft with AI')
        ->waitForText('AI fills in the estimate')
        ->fill('#draft-text', 'Could you quote us for redesigning our five-page website? Quote valid for 15 days please.')
        ->click('[role="dialog"] button:has-text("Draft estimate")')
        ->waitForText('Drafted from pasted text')
        ->assertPathIs('/estimates/create')
        ->assertSee('has no price yet')
        ->assertNoJavaScriptErrors();

    expect(Invoice::count())->toBe(0);
});
