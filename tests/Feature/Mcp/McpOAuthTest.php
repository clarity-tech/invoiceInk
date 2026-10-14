<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Token;

/*
|--------------------------------------------------------------------------
| OAuth sign-in for MCP clients
|--------------------------------------------------------------------------
|
| Claude's connectors (claude.ai, Desktop, mobile) cannot send a pasted token:
| they read the 401, discover the authorization server, register themselves and
| send the user through the consent screen. Each step below is one Claude
| performs, in the order it performs them, so a break anywhere in the chain
| shows up as the step that fails rather than as "Couldn't reach the MCP server".
|
*/

const CLAUDE_CALLBACK = 'https://claude.ai/api/mcp/auth_callback';

/**
 * Register a client the way Claude does, through Dynamic Client Registration.
 */
function registerClaudeClient(): string
{
    return test()->postJson('/oauth/register', [
        'client_name' => 'Claude',
        'redirect_uris' => [CLAUDE_CALLBACK],
    ])->assertCreated()->json('client_id');
}

/**
 * Run the authorization code flow with PKCE as $user and return the token
 * endpoint's response.
 */
function signInAsClaude(User $user, string $clientId): TestResponse
{
    $verifier = Str::random(64);
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

    test()->actingAs($user)
        ->get('/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => CLAUDE_CALLBACK,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:use',
            'state' => 'opaque-state',
            'resource' => url('/mcp'),
        ]))
        ->assertOk();

    $approval = test()->actingAs($user)->post('/oauth/authorize', [
        'state' => '',
        'client_id' => $clientId,
        'auth_token' => session('authToken'),
    ]);

    $location = $approval->headers->get('Location');
    expect($location)->toStartWith(CLAUDE_CALLBACK.'?');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $callback);
    expect($callback['state'])->toBe('opaque-state');

    return test()->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $clientId,
        'redirect_uri' => CLAUDE_CALLBACK,
        'code_verifier' => $verifier,
        'code' => $callback['code'],
    ]);
}

function callListInvoices(string $accessToken): TestResponse
{
    return test()->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'list-invoices', 'arguments' => []],
    ], ['Authorization' => 'Bearer '.$accessToken]);
}

/**
 * @return array{0: Organization, 1: Organization}
 */
function organizationsWithOneInvoiceEach(): array
{
    $mine = createOrganizationWithLocation(['name' => 'Mine Ltd']);
    $theirs = createOrganizationWithLocation(['name' => 'Theirs Ltd']);

    createInvoiceWithItems(['invoice_number' => 'INV-OAUTH-MINE'], null, $mine);
    createInvoiceWithItems(['invoice_number' => 'INV-OAUTH-THEIRS'], null, $theirs);

    return [$mine, $theirs];
}

test('an unauthenticated request is told where to find the authorization server', function () {
    $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertUnauthorized()
        ->assertHeader(
            'WWW-Authenticate',
            'Bearer realm="mcp", resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp').'"',
        );
});

test('the protected resource metadata names the endpoint exactly as users paste it', function () {
    // Claude compares `resource` with the URL typed into the connector dialog
    // and uses only the first authorization server listed.
    $this->getJson('/.well-known/oauth-protected-resource/mcp')
        ->assertOk()
        ->assertJsonPath('resource', url('/mcp'))
        ->assertJsonPath('authorization_servers.0', url('/'))
        ->assertJsonPath('scopes_supported', ['mcp:use']);
});

test('the authorization server metadata advertises registration and S256 PKCE', function () {
    $this->getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonPath('authorization_endpoint', url('/oauth/authorize'))
        ->assertJsonPath('token_endpoint', url('/oauth/token'))
        ->assertJsonPath('registration_endpoint', url('/oauth/register'))
        ->assertJsonPath('code_challenge_methods_supported', ['S256']);
});

test('a guest who starts the flow is sent to log in first', function () {
    $clientId = registerClaudeClient();

    $this->get('/oauth/authorize?'.http_build_query([
        'response_type' => 'code',
        'client_id' => $clientId,
        'redirect_uri' => CLAUDE_CALLBACK,
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
        'scope' => 'mcp:use',
    ]))->assertRedirect('/login');
});

test('the consent screen shows where approval sends the user and what it grants', function () {
    [$mine] = organizationsWithOneInvoiceEach();
    $clientId = registerClaudeClient();

    $this->actingAs($mine->owner)
        ->get('/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => CLAUDE_CALLBACK,
            'code_challenge' => str_repeat('a', 43),
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:use',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('OAuth/Authorize')
            ->where('clientName', 'Claude')
            ->where('redirectHost', 'claude.ai')
            ->where('isLocalRedirect', false)
            ->where('organization', 'Mine Ltd')
            ->where('email', $mine->owner->email));
});

test('a token from signing in reads only the signed-in user\'s organization', function () {
    [$mine] = organizationsWithOneInvoiceEach();

    $tokens = signInAsClaude($mine->owner, registerClaudeClient())
        ->assertOk()
        ->assertJsonStructure(['access_token', 'refresh_token', 'expires_in']);

    // The token endpoint and the MCP call both run in this one test, so drop
    // the session the consent step left behind: the MCP request must stand on
    // its bearer token alone.
    auth()->forgetGuards();

    callListInvoices($tokens->json('access_token'))
        ->assertOk()
        ->assertSee('INV-OAUTH-MINE')
        ->assertDontSee('INV-OAUTH-THEIRS')
        ->assertDontSee('not authorised');
});

test('a refreshed token keeps working', function () {
    [$mine] = organizationsWithOneInvoiceEach();
    $clientId = registerClaudeClient();

    $tokens = signInAsClaude($mine->owner, $clientId)->assertOk();

    $refreshed = $this->post('/oauth/token', [
        'grant_type' => 'refresh_token',
        'client_id' => $clientId,
        'refresh_token' => $tokens->json('refresh_token'),
    ])->assertOk();

    auth()->forgetGuards();

    callListInvoices($refreshed->json('access_token'))
        ->assertOk()
        ->assertSee('INV-OAUTH-MINE');
});

test('the settings page lists a signed-in app and disconnecting it cuts access', function () {
    [$mine] = organizationsWithOneInvoiceEach();
    $clientId = registerClaudeClient();
    $tokens = signInAsClaude($mine->owner, $clientId)->assertOk();
    $accessToken = $tokens->json('access_token');

    $this->actingAs($mine->owner)
        ->get('/settings/ai-connection')
        ->assertInertia(fn ($page) => $page
            ->has('apps', 1)
            ->where('apps.0.id', $clientId)
            ->where('apps.0.name', 'Claude')
            ->etc());

    $this->actingAs($mine->owner)
        ->delete('/settings/ai-connection/apps/'.$clientId)
        ->assertRedirect();

    expect(Token::where('client_id', $clientId)->where('revoked', false)->exists())->toBeFalse();

    auth()->forgetGuards();

    callListInvoices($accessToken)->assertUnauthorized();

    // Claude treats only invalid_grant as "sign in again"; any other error on
    // refresh and it keeps retrying a connection that can never recover.
    $this->post('/oauth/token', [
        'grant_type' => 'refresh_token',
        'client_id' => $clientId,
        'refresh_token' => $tokens->json('refresh_token'),
    ])->assertBadRequest()->assertJsonPath('error', 'invalid_grant');

    $this->actingAs($mine->owner)
        ->get('/settings/ai-connection')
        ->assertInertia(fn ($page) => $page->has('apps', 0)->etc());
});

test('a user cannot disconnect another user\'s app', function () {
    [$mine, $theirs] = organizationsWithOneInvoiceEach();
    $clientId = registerClaudeClient();
    signInAsClaude($theirs->owner, $clientId)->assertOk();

    $this->actingAs($mine->owner)
        ->delete('/settings/ai-connection/apps/'.$clientId)
        ->assertRedirect();

    expect(Token::where('client_id', $clientId)->where('revoked', false)->exists())->toBeTrue();
});
