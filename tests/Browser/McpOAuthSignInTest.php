<?php

use App\Models\User;
use Laravel\Passport\ClientRepository;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\Webpage;

/*
| The usual way an AI client's sign-in starts: Claude opens the authorize URL in
| a browser that is not logged in to InvoiceInk. The login form submits over
| Inertia, so the hop back to the consent screen is an XHR redirect, and the
| consent buttons are plain form posts built from the page's hidden fields.
| Neither is reproducible in a feature test, which posts those fields itself.
*/

const SIGN_IN_CALLBACK_PATH = '/oauth-test-callback';

/**
 * Log a guest in through an authorize URL and stop on the consent screen.
 *
 * The client's redirect URI is on the test server itself: a claude.ai callback
 * would send the browser out to the real site once a button is pressed. The
 * login page is opened first only to learn the test server's origin.
 */
function signInToConsentScreen(): AwaitableWebpage|Webpage
{
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'oauth-sign-in-'.uniqid().'@example.test',
        'password' => 'password',
    ]);
    createOrganizationWithLocation(['name' => 'Clarity Technologies'], [], $user);

    $page = visit('/login');

    $parts = parse_url($page->url());
    $callback = "{$parts['scheme']}://{$parts['host']}:{$parts['port']}".SIGN_IN_CALLBACK_PATH;

    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        name: 'Claude',
        redirectUris: [$callback],
        confidential: false,
    );

    return $page->navigate('/oauth/authorize?'.http_build_query([
        'response_type' => 'code',
        'client_id' => $client->getKey(),
        'redirect_uri' => $callback,
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
        'scope' => 'mcp:use',
        'state' => 'opaque-state',
    ]))
        ->assertPathIs('/login')
        ->fill('#email', $user->email)
        ->fill('#password', 'password')
        ->click('Log in')
        ->assertPathIs('/oauth/authorize')
        ->assertSee('Connect Claude')
        ->assertSee('Clarity Technologies')
        ->assertNoJavascriptErrors();
}

it('approving sends the user back to the client with a code', function () {
    signInToConsentScreen()
        ->click('Approve')
        ->assertPathIs(SIGN_IN_CALLBACK_PATH)
        ->assertQueryStringHas('code')
        ->assertQueryStringHas('state', 'opaque-state');
});

it('cancelling sends the user back to the client with access denied', function () {
    signInToConsentScreen()
        ->click('Cancel')
        ->assertPathIs(SIGN_IN_CALLBACK_PATH)
        ->assertQueryStringHas('error', 'access_denied')
        ->assertQueryStringHas('state', 'opaque-state')
        ->assertQueryStringMissing('code');
});
