<?php

use App\Enums\Appearance;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/**
 * The theme is chosen in the browser but has to be on <html> before the first
 * paint, so the server renders it from the `appearance` cookie. These cover the
 * part only the server can do; switching and the `system` choice are covered by
 * the browser tests and useAppearance.test.ts.
 */
function rootElement(string $html): string
{
    preg_match('/<html[^>]*>/', $html, $match);

    return $match[0] ?? '';
}

it('renders an explicit dark choice on the server', function (string $path) {
    // Unencrypted because the browser writes it; this also proves the cookie
    // is exempt from EncryptCookies, which would otherwise discard it.
    $response = $this->withUnencryptedCookie(Appearance::COOKIE, 'dark')->get($path);

    $response->assertOk();
    expect(rootElement($response->getContent()))->toContain('class="dark"');
})->with([
    'an Inertia page' => '/login',
    'a Blade page' => '/terms-of-service',
]);

it('leaves the theme to the browser when the choice is not dark', function (?string $cookie) {
    $request = $cookie === null ? $this : $this->withUnencryptedCookie(Appearance::COOKIE, $cookie);

    $response = $request->get('/login');

    expect(rootElement($response->getContent()))->not->toContain('dark');
})->with([
    'no choice yet' => null,
    'light' => 'light',
    'system' => 'system',
    'an unknown value' => 'sepia',
]);

it('ships the script that resolves the system choice before first paint', function (string $path) {
    $html = $this->get($path)->getContent();
    $head = substr($html, 0, strpos($html, '</head>'));

    expect($head)
        ->toContain('prefers-color-scheme: dark')
        ->toContain('<meta name="color-scheme" content="light dark">');
})->with([
    'an Inertia page' => '/login',
    'a Blade page' => '/privacy-policy',
]);

it('renders the dark choice for a signed-in page too', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $user->currentTeam->update(['setup_completed_at' => now(), 'currency' => 'INR', 'country_code' => 'IN']);

    $response = $this->actingAs($user)
        ->withUnencryptedCookie(Appearance::COOKIE, 'dark')
        ->get('/user/profile');

    $response->assertOk();
    expect(rootElement($response->getContent()))->toContain('class="dark"');
});

it('renders the dark choice on the shared document', function (string $type) {
    $document = createInvoiceWithItems(['type' => $type]);

    $response = $this->withUnencryptedCookie(Appearance::COOKIE, 'dark')
        ->get($document->shareUrl());

    $response->assertOk();
    $html = $response->getContent();
    expect(rootElement($html))->toContain('class="dark"')
        ->and(substr($html, 0, strpos($html, '</head>')))->toContain('prefers-color-scheme: dark');
})->with(['invoice', 'estimate']);

it('keeps the PDF light whatever the choice', function (string $type) {
    // The PDF route runs the same middleware, so the dark choice reaches the
    // template; only the template's own PDF check keeps it out.
    Config::set('services.gotenberg.enabled', true);
    Config::set('services.gotenberg.url', 'http://gotenberg:3000');
    Http::fake(['gotenberg:3000/*' => Http::response('%PDF-1.4 /Count 1', 200)]);
    $document = createInvoiceWithItems(['type' => $type]);

    $this->withUnencryptedCookie(Appearance::COOKIE, 'dark')
        ->get($document->sharePdfUrl(now()->addDay()))
        ->assertOk();

    $renders = Http::recorded()->map(fn (array $exchange): string => $exchange[0]->body());
    // Dark styles only apply under <html class="dark">, which the PDF never
    // gets, from the server or from the head script.
    expect($renders)->toHaveCount(2)
        ->each(fn ($html) => $html
            ->not->toMatch('/<html[^>]*class="dark"/')
            ->not->toContain('prefers-color-scheme'));
})->with(['invoice', 'estimate']);
