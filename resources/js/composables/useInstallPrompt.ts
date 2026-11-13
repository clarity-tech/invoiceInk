import { computed, ref, shallowRef } from 'vue';

/**
 * The non-standard event Chromium fires when the app is installable. Safari
 * has no equivalent, so iOS is handled by showing instructions instead.
 */
interface BeforeInstallPromptEvent extends Event {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

type InstallMode = 'prompt' | 'ios' | null;

const SNOOZE_KEY = 'pwa-install-snoozed-until';
const SNOOZE_MS = 30 * 24 * 60 * 60 * 1000;

// Module-level on purpose: the browser fires `beforeinstallprompt` once, early,
// and the banner may not exist yet (or may be remounted by a layout change).
const deferredPrompt = shallowRef<BeforeInstallPromptEvent | null>(null);
const snoozed = ref(readSnooze() > Date.now());

function readSnooze(): number {
    try {
        return Number(localStorage.getItem(SNOOZE_KEY)) || 0;
    } catch {
        return 0;
    }
}

/** Already running as an installed app, so there is nothing to offer. */
function isStandalone(): boolean {
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        (navigator as Navigator & { standalone?: boolean }).standalone === true
    );
}

/** A phone or tablet. Desktop browsers get their own install affordance. */
function isTouchDevice(): boolean {
    return window.matchMedia('(pointer: coarse)').matches;
}

/** iPhone/iPad Safari, which can install but never fires the event. */
function isIosSafari(): boolean {
    const ua = navigator.userAgent;
    // iPadOS reports itself as a Mac; the touch points give it away.
    const ios =
        /iphone|ipad|ipod/i.test(ua) ||
        (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
    // Only Safari sends `Version/x … Safari/y`; in-app web views (Facebook,
    // Instagram, the Google app) mostly drop it, and the token list catches
    // the rest. None of them offer Add to Home Screen.
    const safari = /version\/[\d.]+.*safari\//i.test(ua);
    const otherBrowser =
        /crios|fxios|edgios|opios|gsa\/|fban|fbav|instagram|line\/|twitter|linkedinapp|snapchat/i.test(
            ua,
        );

    return ios && safari && !otherBrowser;
}

/** Called once from the entry point, before any page renders. */
export function listenForInstallPrompt(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        // Hold the event back so the browser's own mini-infobar stays quiet
        // and our banner decides when to ask.
        event.preventDefault();
        deferredPrompt.value = event as BeforeInstallPromptEvent;
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt.value = null;
    });
}

function snooze(): void {
    snoozed.value = true;

    try {
        localStorage.setItem(SNOOZE_KEY, String(Date.now() + SNOOZE_MS));
    } catch {
        // Private mode: the banner simply comes back next visit.
    }
}

export function useInstallPrompt() {
    const mode = computed<InstallMode>(() => {
        if (
            snoozed.value ||
            typeof window === 'undefined' ||
            isStandalone() ||
            !isTouchDevice()
        ) {
            return null;
        }

        return deferredPrompt.value ? 'prompt' : isIosSafari() ? 'ios' : null;
    });

    async function install(): Promise<void> {
        const event = deferredPrompt.value;

        if (!event) {
            return;
        }

        // The event is single-use whatever the answer.
        deferredPrompt.value = null;

        try {
            await event.prompt();
            const { outcome } = await event.userChoice;

            if (outcome === 'dismissed') {
                snooze();
            }
        } catch {
            // The browser refused to show it; treat that as "not now".
            snooze();
        }
    }

    return { mode, install, dismiss: snooze };
}
