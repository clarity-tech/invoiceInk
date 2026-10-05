<?php

use App\Http\Controllers\AiConnectionController;
use App\Mcp\Servers\InvoicingServer;
use App\Mcp\Tools\ListInvoicesTool;

test('the settings page lists the endpoint and existing connections', function () {
    $organization = createOrganizationWithLocation();
    $owner = $organization->owner;
    $owner->createToken('Claude on my laptop', [AiConnectionController::ABILITY]);

    $this->actingAs($owner)
        ->get('/settings/ai-connection')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/AiConnection')
            ->where('endpoint', url('/mcp'))
            ->where('organization', $organization->name)
            ->has('tokens', 1)
            ->where('tokens.0.name', 'Claude on my laptop')
            ->etc());
});

test('creating a connection returns the plaintext token exactly once', function () {
    $organization = createOrganizationWithLocation();
    $owner = $organization->owner;

    $this->actingAs($owner)
        ->post('/settings/ai-connection', ['name' => 'Desktop'])
        ->assertRedirect();

    // Sanctum stores only a hash, so a value the user cannot copy now is lost.
    $plain = session('mcpToken');
    expect($plain)->toBeString()->toContain('|');

    // The first render after the redirect is the one chance to show it...
    $this->actingAs($owner)
        ->get('/settings/ai-connection')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('newToken', $plain)->etc());

    // ...and it is gone on the next, so a reload cannot re-expose it.
    $this->actingAs($owner)
        ->get('/settings/ai-connection')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('newToken', null)->etc());
});

test('connections are scoped to the mcp ability, not general purpose', function () {
    $organization = createOrganizationWithLocation();
    $owner = $organization->owner;

    $this->actingAs($owner)->post('/settings/ai-connection', ['name' => 'Desktop']);

    expect($owner->fresh()->tokens()->first())
        ->can(AiConnectionController::ABILITY)->toBeTrue()
        ->can('*')->toBeFalse();
});

test('the page only lists mcp connections, not the user\'s other tokens', function () {
    $organization = createOrganizationWithLocation();
    $owner = $organization->owner;

    $owner->createToken('Some other integration', ['read']);
    $owner->createToken('AI assistant', [AiConnectionController::ABILITY]);

    $this->actingAs($owner)
        ->get('/settings/ai-connection')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('tokens', 1)
            ->where('tokens.0.name', 'AI assistant')
            ->etc());
});

test('revoking a connection deletes it', function () {
    $organization = createOrganizationWithLocation();
    $owner = $organization->owner;
    $token = $owner->createToken('Desktop', [AiConnectionController::ABILITY]);

    $this->actingAs($owner)
        ->delete('/settings/ai-connection/'.$token->accessToken->id)
        ->assertRedirect();

    expect($owner->fresh()->tokens()->count())->toBe(0);
});

test('a user cannot revoke another user\'s connection', function () {
    $mine = createOrganizationWithLocation();
    $theirs = createOrganizationWithLocation();
    $theirToken = $theirs->owner->createToken('Theirs', [AiConnectionController::ABILITY]);

    $this->actingAs($mine->owner)
        ->delete('/settings/ai-connection/'.$theirToken->accessToken->id)
        ->assertRedirect();

    expect($theirs->owner->fresh()->tokens()->count())->toBe(1);
});

test('the settings page requires authentication', function () {
    $this->get('/settings/ai-connection')->assertRedirect('/login');
});

/*
|--------------------------------------------------------------------------
| The ability gate
|--------------------------------------------------------------------------
|
| Without it, any Sanctum token the user holds for an unrelated purpose would
| unlock their entire invoicing history over MCP.
|
*/

test('a token without the mcp ability is refused by the tools', function () {
    $organization = createOrganizationWithLocation();
    createInvoiceWithItems(['invoice_number' => 'INV-GATED'], null, $organization);

    $plain = $organization->owner->createToken('Unrelated integration', ['read'])->plainTextToken;

    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'list-invoices', 'arguments' => []],
    ], ['Authorization' => 'Bearer '.$plain])
        ->assertOk()
        ->assertSee('not authorised')
        ->assertDontSee('INV-GATED');
});

test('a token with the mcp ability is accepted by the tools', function () {
    $organization = createOrganizationWithLocation();
    createInvoiceWithItems(['invoice_number' => 'INV-ALLOWED'], null, $organization);

    $plain = $organization->owner
        ->createToken('AI assistant', [AiConnectionController::ABILITY])
        ->plainTextToken;

    $this->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'list-invoices', 'arguments' => []],
    ], ['Authorization' => 'Bearer '.$plain])
        ->assertOk()
        ->assertSee('INV-ALLOWED');
});

test('the stdio path still works, since it carries no token to check', function () {
    $organization = createOrganizationWithLocation();
    createInvoiceWithItems(['invoice_number' => 'INV-STDIO'], null, $organization);

    config()->set('mcp.local_user_email', $organization->owner->email);

    InvoicingServer::tool(ListInvoicesTool::class)
        ->assertOk()
        ->assertSee('INV-STDIO');
});
