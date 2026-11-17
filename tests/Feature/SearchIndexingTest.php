<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;

/*
 * What search engines may index, checked as a whole rather than page by page:
 * the private sign-in screens must say noindex, and everything the sitemap
 * offers must be indexable and say so consistently.
 */

beforeEach(function () {
    config(['app.url' => 'https://invoiceink.test']);
});

/**
 * The canonical URL a page names, or null when it names none.
 */
function canonicalOf(string $html): ?string
{
    return preg_match('#<link rel="canonical" href="([^"]+)"#', $html, $match) ? $match[1] : null;
}

/**
 * The meta description a page gives, or null when it gives none.
 */
function descriptionOf(string $html): ?string
{
    return preg_match('#<meta name="description" content="([^"]*)"#', $html, $match) ? html_entity_decode($match[1]) : null;
}

it('keeps the private and throwaway sign-in screens out of search results', function (string $path) {
    $this->get($path)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with([
    'forgot password' => '/forgot-password',
    'a password reset link' => '/reset-password/a-token?email=someone%40example.test',
]);

it('keeps the screens partway through signing in out of search results', function (string $path, Closure $as) {
    $as($this, User::factory()->withPersonalTeam()->create())
        ->get($path)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with([
    'two-factor challenge' => ['/two-factor-challenge', fn ($test, User $user) => $test->withSession(['login.id' => $user->id])],
    'confirm password' => ['/user/confirm-password', fn ($test, User $user) => $test->actingAs($user)],
    'verify email' => ['/email/verify', fn ($test, User $user) => $test->actingAs(tap($user)->update(['email_verified_at' => null]))],
]);

it('lets search engines index log in and register, with the title, description and canonical in the HTML it sends', function (string $path, string $title, string $description) {
    $html = $this->get('http://invoicing-web-abc123-el.a.run.app'.$path)
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertSee('<title>'.$title.' - '.config('app.name').'</title>', false)
        ->getContent();

    expect(canonicalOf($html))->toBe('https://invoiceink.test'.$path)
        ->and(descriptionOf($html))->toBe(__($description, ['app' => config('app.name')]));
})->with([
    'log in' => ['/login', 'Log in', 'marketing.auth.login.description'],
    'register' => ['/register', 'Register', 'marketing.auth.register.description'],
]);

it('marks log in and register noindex when the deployment is not indexable', function (string $path) {
    config(['app.indexable' => false]);

    $this->get($path)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with(['/login', '/register']);

it('points its icons and share image at real files of the size it declares', function () {
    $html = $this->get('/')->assertOk()->getContent();
    preg_match_all('#<link rel="(?:icon|apple-touch-icon)" href="https?://[^/"]+/([^"]+)"(?: sizes="(\d+)x\d+")?#', $html, $icons, PREG_SET_ORDER);
    preg_match('#<meta property="og:image" content="https?://[^/"]+/([^"]+)">#', $html, $image);
    preg_match('#<meta property="og:image:width" content="(\d+)">\s*<meta property="og:image:height" content="(\d+)">#', $html, $imageSize);

    expect($icons)->toHaveCount(3);
    foreach ($icons as $icon) {
        // filesize() rather than file_exists(): favicon.ico used to be an empty file.
        expect(filesize(public_path($icon[1])))->toBeGreaterThan(0, "{$icon[1]} is empty");
        if (isset($icon[2])) {
            expect(getimagesize(public_path($icon[1]))[0])->toBe((int) $icon[2]);
        }
    }
    expect(array_slice(getimagesize(public_path($image[1])), 0, 2))->toBe([(int) $imageSize[1], (int) $imageSize[2]]);
});

/**
 * Each page the sitemap lists, fetched on another host the way a stray
 * *.run.app link would reach it, keyed by the URL the sitemap gives.
 *
 * @return array<string, TestResponse>
 */
function sitemapPages(): array
{
    $sitemap = test()->get('/sitemap.xml')->assertOk()->getContent();
    $pages = [];

    foreach (simplexml_load_string($sitemap)->xpath('//*[local-name()="loc"]') as $loc) {
        $pages[(string) $loc] = test()->get('http://invoicing-web-abc123-el.a.run.app'.parse_url((string) $loc, PHP_URL_PATH));
    }

    return $pages;
}

it('lets search engines index every page in the sitemap, each naming its own sitemap URL as canonical', function () {
    $pages = sitemapPages();

    expect($pages)->toHaveCount(9);
    foreach ($pages as $url => $response) {
        $response->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertDontSee('<meta name="robots"', false);
        expect(canonicalOf($response->getContent()))->toBe($url);
    }
});

it('gives every page in the sitemap a title and description of its own', function () {
    $titles = [];
    $descriptions = [];

    foreach (sitemapPages() as $url => $response) {
        preg_match('#<title>(.*?)</title>#s', $response->getContent(), $title);

        expect($title[1] ?? '')->not->toBeEmpty("{$url} has no title")
            ->and(descriptionOf($response->getContent()))->not->toBeEmpty("{$url} has no meta description");
        $titles[$url] = $title[1];
        $descriptions[$url] = descriptionOf($response->getContent());
    }

    expect(array_unique($titles))->toBe($titles)
        ->and(array_unique($descriptions))->toBe($descriptions);
});

it('only serves structured data that parses', function () {
    foreach (sitemapPages() as $response) {
        // structuredData() itself fails the test on a block that isn't valid JSON-LD.
        expect(structuredData($response->getContent()))->toBeArray();
    }
});
