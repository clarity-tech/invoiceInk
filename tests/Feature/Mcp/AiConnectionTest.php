<?php

use App\Mcp\Servers\InvoicingServer;
use App\Mcp\Tools\ListInvoicesTool;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

test('the settings page shows the endpoint for the current organization', function () {
    $organization = createOrganizationWithLocation();

    $this->actingAs($organization->owner)
        ->get('/settings/ai-connection')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/AiConnection')
            ->where('endpoint', url('/mcp'))
            ->where('organization', $organization->name)
            ->etc());
});

test('the settings page requires authentication', function () {
    $this->get('/settings/ai-connection')->assertRedirect('/login');
});

/*
|--------------------------------------------------------------------------
| The scope gate
|--------------------------------------------------------------------------
|
| Without it, any other token the user holds would unlock their entire
| invoicing history over MCP.
|
*/

function listInvoicesOverHttp(array $headers = []): TestResponse
{
    return test()->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'list-invoices', 'arguments' => []],
    ], $headers);
}

test('a token without the mcp scope is refused by the tools', function () {
    $organization = createOrganizationWithLocation();
    createInvoiceWithItems(['invoice_number' => 'INV-GATED'], null, $organization);
    Passport::actingAs($organization->owner, ['other']);

    listInvoicesOverHttp()
        ->assertOk()
        ->assertSee('not authorised')
        ->assertDontSee('INV-GATED');
});

test('a token with the mcp scope is accepted by the tools', function () {
    $organization = createOrganizationWithLocation();
    createInvoiceWithItems(['invoice_number' => 'INV-ALLOWED'], null, $organization);
    Passport::actingAs($organization->owner, ['mcp:use']);

    listInvoicesOverHttp()
        ->assertOk()
        ->assertSee('INV-ALLOWED');
});

test('an API token no longer reaches the tools', function () {
    $organization = createOrganizationWithLocation();
    $plain = $organization->owner->createToken('AI assistant', ['mcp:read'])->plainTextToken;

    listInvoicesOverHttp(['Authorization' => 'Bearer '.$plain])->assertUnauthorized();
});

test('the stdio path still works, since it carries no token to check', function () {
    $organization = createOrganizationWithLocation();
    createInvoiceWithItems(['invoice_number' => 'INV-STDIO'], null, $organization);

    config()->set('mcp.local_user_email', $organization->owner->email);

    InvoicingServer::tool(ListInvoicesTool::class)
        ->assertOk()
        ->assertSee('INV-STDIO');
});
