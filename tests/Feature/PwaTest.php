<?php

use Illuminate\Support\Facades\File;

/**
 * Point public_path() at a scratch directory, with or without a built worker,
 * so the real public/build is never touched.
 */
function usePublicPathWithWorker(?string $worker): void
{
    $public = storage_path('framework/testing/pwa-'.uniqid());
    File::ensureDirectoryExists("{$public}/build");

    if ($worker !== null) {
        File::put("{$public}/build/sw.js", $worker);
    }

    app()->usePublicPath($public);
    test()->beforeApplicationDestroyed(fn () => File::deleteDirectory($public));
}

test('the web app manifest is installable', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest)
        ->name->toBe('InvoiceInk')
        ->display->toBe('standalone')
        ->start_url->toStartWith('/')
        ->and(collect($manifest['icons'])->pluck('sizes'))->toContain('192x192', '512x512')
        ->and(collect($manifest['icons'])->pluck('purpose'))->toContain('maskable');

    foreach ($manifest['icons'] as $icon) {
        expect(public_path($icon['src']))->toBeFile();
    }
});

test('the service worker is served from the site root without starting a session', function () {
    usePublicPathWithWorker('// built worker');

    $response = $this->get('/sw.js')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/javascript; charset=utf-8')
        ->assertCookieMissing('XSRF-TOKEN');

    expect($response->streamedContent())->toBe('// built worker')
        ->and($response->headers->get('Cache-Control'))->toContain('no-cache');
});

test('an unchanged service worker is answered with a 304', function () {
    usePublicPathWithWorker('// built worker');
    $lastModified = $this->get('/sw.js')->headers->get('Last-Modified');

    $this->get('/sw.js', ['If-Modified-Since' => $lastModified])->assertNoContent(304);
});

test('the service worker removes itself while the PWA is switched off', function () {
    usePublicPathWithWorker('// built worker');
    config(['app.pwa_enabled' => false]);

    $response = $this->get('/sw.js')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/javascript; charset=utf-8');

    expect($response->getContent())
        ->not->toContain('// built worker')
        ->toContain('skipWaiting()', 'caches.delete(key)', 'self.registration.unregister()')
        ->not->toContain('navigate(')
        ->and($response->headers->get('Cache-Control'))->toContain('no-cache');
});

test('the service worker removes itself rather than 404ing when none was built', function () {
    usePublicPathWithWorker(null);

    expect($this->get('/sw.js')->assertOk()->getContent())
        ->toContain('self.registration.unregister()');
});

test('the app shell links the manifest and tells the page to register the worker', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('<meta name="pwa" content="enabled">', false)
        ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
        ->assertSee('<meta name="theme-color" content="#7c3aed">', false);
});

test('the app shell tells the page to remove the worker while the PWA is switched off', function () {
    config(['app.pwa_enabled' => false]);

    $this->get('/login')
        ->assertOk()
        ->assertSee('<meta name="pwa" content="disabled">', false)
        ->assertDontSee('rel="manifest"', false)
        ->assertDontSee('apple-mobile-web-app-capable', false);
});
