<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The PWA service worker, served from the site root so its scope covers "/".
 *
 * With the PWA switched off (or no worker built) it serves a worker that
 * removes itself instead. Never a 404: a failed update check leaves the
 * installed worker running indefinitely.
 */
class ServiceWorkerController extends Controller
{
    /**
     * Replaces whatever worker is installed, deletes its caches and
     * unregisters. No fetch handler, so open tabs go straight to the network.
     * It deliberately does not reload them: that would discard unsaved forms.
     */
    private const UNREGISTER = <<<'JS'
        self.addEventListener('install', () => self.skipWaiting());
        self.addEventListener('activate', (event) => {
            event.waitUntil((async () => {
                const keys = await caches.keys();
                await Promise.all(keys.map((key) => caches.delete(key)));
                indexedDB.deleteDatabase('workbox-expiration');
                await self.registration.unregister();
            })());
        });

        JS;

    private const HEADERS = [
        'Content-Type' => 'text/javascript; charset=utf-8',
        'Cache-Control' => 'no-cache',
    ];

    public function __invoke(Request $request): Response
    {
        $built = public_path('build/sw.js');

        if (config('app.pwa_enabled') && is_file($built)) {
            // Browsers re-check the worker on every full page load; answer an
            // unchanged one with a 304 rather than the whole file.
            $response = response()->file($built, self::HEADERS);
            $response->isNotModified($request);

            return $response;
        }

        return response(self::UNREGISTER, 200, self::HEADERS);
    }
}
