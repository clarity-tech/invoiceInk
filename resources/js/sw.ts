/// <reference lib="webworker" />
import { ExpirationPlugin } from 'workbox-expiration';
import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { CacheFirst, NetworkOnly } from 'workbox-strategies';

declare const self: ServiceWorkerGlobalScope;

const OFFLINE_HTML = `<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#7c3aed">
<title>Offline - InvoiceInk</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;font:16px system-ui,sans-serif;background:#f3f4f6;color:#111827;text-align:center;padding:24px}
@media(prefers-color-scheme:dark){body{background:#030712;color:#f3f4f6}}
button{margin-top:16px;padding:10px 20px;border:0;border-radius:8px;background:#7c3aed;color:#fff;font:inherit;font-weight:600}</style></head>
<body><div><h1>You're offline</h1><p>InvoiceInk needs a connection to load your invoices.</p>
<button onclick="location.reload()">Try again</button></div></body></html>`;

// The entry bundle is precached by vite-plugin-pwa at build time; the other
// (content-hashed, immutable) build chunks are cached the first time they load.
// Pages and data are never cached (invoices must not be stale): they always
// hit the network, and a failed navigation falls back to a small offline page.
precacheAndRoute(self.__WB_MANIFEST);
cleanupOutdatedCaches();
registerRoute(
    ({ url }) =>
        url.origin === self.location.origin &&
        url.pathname.startsWith('/build/assets/'),
    new CacheFirst({
        cacheName: 'build-assets',
        // Every deploy renames every chunk, so without a cap old builds pile up.
        plugins: [
            new ExpirationPlugin({
                maxEntries: 120,
                maxAgeSeconds: 30 * 24 * 60 * 60,
                purgeOnQuotaError: true,
            }),
        ],
    }),
);
registerRoute(
    ({ request }) => request.mode === 'navigate',
    new NetworkOnly({
        plugins: [
            {
                handlerDidError: async () =>
                    new Response(OFFLINE_HTML, {
                        headers: { 'Content-Type': 'text/html; charset=utf-8' },
                    }),
            },
        ],
    }),
);

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) =>
    event.waitUntil(
        Promise.all([
            self.clients.claim(),
            // Page loads start fetching while the worker boots, not after.
            self.registration.navigationPreload?.enable(),
        ]),
    ),
);
