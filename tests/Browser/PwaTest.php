<?php

/*
 * The service worker only registers in a production build (`vite build`), so
 * these need built assets in public/build and no public/hot, like the rest of
 * the browser suite. Each script stays well under the 5s script timeout, so a
 * miss fails an assertion rather than the call.
 */

/** Waits for an activated worker to control the page, then reports on it. */
function pwaWorkerStatusScript(): string
{
    return <<<'JS'
        async () => {
            const registration = await navigator.serviceWorker.ready;

            for (let i = 0; i < 30 && (!navigator.serviceWorker.controller || registration.active.state !== 'activated'); i++) {
                await new Promise((resolve) => setTimeout(resolve, 100));
            }

            return {
                scope: new URL(registration.scope).pathname,
                script: new URL(registration.active.scriptURL).pathname,
                state: registration.active.state,
                controlled: navigator.serviceWorker.controller !== null,
                navigationPreload: (await registration.navigationPreload.getState()).enabled,
                caches: (await caches.keys()).length > 0,
            };
        }
    JS;
}

it('registers a service worker that controls the whole site', function () {
    $page = $this->visit('/login');

    expect($page->script(pwaWorkerStatusScript()))->toBe([
        'scope' => '/',
        'script' => '/sw.js',
        'state' => 'activated',
        'controlled' => true,
        'navigationPreload' => true,
        'caches' => true,
    ]);

    $page->assertNoJavascriptErrors();
});

it('precaches the entry bundle and caches page chunks as they load', function () {
    $page = $this->visit('/login');
    expect($page->script(pwaWorkerStatusScript()))->toMatchArray(['controlled' => true]);

    $cached = $page->script(<<<'JS'
        async () => {
            const paths = async (name) =>
                (await (await caches.open(name)).keys()).map((request) => new URL(request.url).pathname);

            const precache = (await caches.keys()).find((name) => name.startsWith('workbox-precache'));
            const precached = precache ? await paths(precache) : [];

            // The page's own chunk loaded before the worker took control; fetching
            // it again now goes through the worker, as a later visit's chunks would.
            const chunk = [...document.querySelectorAll('script[src], link[rel="modulepreload"]')]
                .map((el) => new URL(el.src || el.href).pathname)
                .find((path) => path.startsWith('/build/assets/') && !precached.includes(path));

            await fetch(chunk);

            // The cache write finishes after the response is returned.
            let runtime = [];
            for (let i = 0; i < 30 && !runtime.includes(chunk); i++) {
                await new Promise((resolve) => setTimeout(resolve, 100));
                runtime = await paths('build-assets');
            }

            return { precache: precached, chunk, runtime };
        }
    JS);

    expect($cached['precache'])->not->toBeEmpty()
        ->and(collect($cached['precache'])->every(fn ($path) => str_starts_with($path, '/build/assets/')))->toBeTrue()
        ->and(collect($cached['precache'])->contains(fn ($path) => str_ends_with($path, '.js')))->toBeTrue()
        ->and(collect($cached['precache'])->contains(fn ($path) => str_ends_with($path, '.css')))->toBeTrue()
        ->and($cached['chunk'])->toStartWith('/build/assets/')
        ->and($cached['runtime'])->toContain($cached['chunk']);
});

it('links a manifest that describes an installable app', function () {
    $page = $this->visit('/login');

    $manifest = $page->script(<<<'JS'
        async () => {
            const link = document.querySelector('link[rel="manifest"]');
            const response = await fetch(link.href);

            return { status: response.status, body: await response.json() };
        }
    JS);

    expect($manifest['status'])->toBe(200)
        ->and($manifest['body']['display'])->toBe('standalone')
        ->and(collect($manifest['body']['icons'])->pluck('sizes')->all())->toContain('192x192', '512x512');
});
