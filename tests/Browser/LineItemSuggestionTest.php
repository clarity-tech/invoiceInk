<?php

use App\Ai\Agents\LineItemClassifier;
use App\Models\User;

it('suggests the SAC code and GST rate for a line on the invoice form', function () {
    config(['ai.providers.anthropic.key' => 'test-key']);
    $user = User::factory()->withPersonalTeam()->create(['email' => 'ai-suggest@example.test']);
    createOrganizationWithLocation(['setup_completed_at' => now(), 'ai_drafting_enabled' => true], [], $user);

    LineItemClassifier::fake([['code' => '998314', 'tax_rate_percent' => 18, 'reason' => 'A website design and development service.']]);

    $this->actingAs($user);

    $this->visit('/invoices/create')
        ->fill('#item-0-description', 'Website design and development')
        ->click('button:has-text("Suggest code & tax")')
        ->waitForText('Suggested by AI')
        ->assertSee('SAC 998314')
        ->assertSelected('#item-0-tax_rate', 'gst-18')
        ->assertNoJavaScriptErrors();
});
