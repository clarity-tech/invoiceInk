import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            // Manifest stays a static file in /public (linked in app.blade.php).
            manifest: false,
            injectRegister: false,
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.ts',
            injectManifest: {
                // Only the entry bundle is precached; page chunks are cached on first use
                // (see sw.ts) so installing the app stays small.
                globPatterns: ['assets/app-*.{js,css}'],
                // Precache URLs are relative to the build dir; the worker is
                // served from /sw.js, so point them at /build/ explicitly.
                modifyURLPrefix: { '': '/build/' },
                // The names are content hashes, so no revision is needed. With
                // one, Workbox re-downloads the files the page just loaded.
                // (The plugin's default pattern runs after the prefix change.)
                dontCacheBustURLsMatching: /^\/build\/assets\//,
            },
        }),
        ...(process.env.WAYFINDER_SKIP_GENERATE
            ? []
            : [wayfinder({ formVariants: true })]),
    ],
});
