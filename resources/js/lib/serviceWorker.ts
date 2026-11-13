type PwaState = 'enabled' | 'disabled' | null;

/**
 * The server's PWA switch, from the `pwa` meta tag in app.blade.php. Read at
 * runtime rather than baked into the build, so turning it off needs no new
 * image. Null on any page that does not render the tag: leave things alone.
 */
export function pwaState(): PwaState {
    const content =
        document.querySelector<HTMLMetaElement>('meta[name="pwa"]')?.content;

    return content === 'enabled' || content === 'disabled' ? content : null;
}

/**
 * Register the service worker, or remove it while the PWA is switched off.
 *
 * Removal never reloads the page: that would discard unsaved forms, and
 * could loop if this page and the server ever disagreed about the switch.
 */
export function syncServiceWorker(): void {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    const state = pwaState();

    if (state === 'enabled' && import.meta.env.PROD) {
        window.addEventListener(
            'load',
            () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            },
            { once: true },
        );
    } else if (state === 'disabled') {
        removeServiceWorker().catch(() => {});
    }
}

/**
 * Updating swaps in the self-removing worker the server now serves, which takes
 * this tab off the old worker at once, then deletes the caches and
 * unregisters. Unregistering alone would leave the old worker serving (and
 * caching for) this tab until it closes.
 */
async function removeServiceWorker(): Promise<void> {
    const registrations = await navigator.serviceWorker.getRegistrations();

    await Promise.all(
        registrations.map((registration) =>
            registration.update().catch(() => registration.unregister()),
        ),
    );
}
