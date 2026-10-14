import { onMounted, readonly, ref } from 'vue';

export type Appearance = 'light' | 'dark' | 'system';

const appearances: readonly Appearance[] = ['system', 'light', 'dark'];

/**
 * Must match `App\Enums\Appearance::COOKIE`. The cookie, not localStorage, is
 * the one place the choice lives: the server reads it to render the first
 * paint in the right theme, and `partials/appearance.blade.php` reads it to
 * keep following the system setting.
 */
const COOKIE = 'appearance';
const ONE_YEAR_IN_SECONDS = 60 * 60 * 24 * 365;

function isAppearance(value: unknown): value is Appearance {
    return appearances.includes(value as Appearance);
}

// Everything below runs from `onMounted` or a click, never during a server
// render, so it can use the browser's globals directly.
function readStoredAppearance(): Appearance {
    const value = document.cookie.match(
        new RegExp(`(?:^|;\\s*)${COOKIE}=([^;]*)`),
    )?.[1];

    return isAppearance(value) ? value : 'system';
}

/**
 * Puts the `dark` class on <html> when the choice, or the system setting it
 * defers to, is dark. Every colour in the app follows from that one class.
 */
function applyAppearance(value: Appearance): void {
    const isDark =
        value === 'dark' ||
        (value === 'system' &&
            window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', isDark);
}

// Module-level, so every toggle on the page shows the same choice. It starts
// at `system` and reads the cookie once mounted, as Laravel's starter kits do:
// a server-rendered page can only ever say `system`, and Vue does not patch an
// input's `checked` while hydrating, so reading it during setup would leave the
// wrong radio checked.
const appearance = ref<Appearance>('system');

// Whether the page is dark right now, whatever the choice that made it so.
// `system` alone does not say, and the head script switches the class when the
// operating system does, so this watches the class itself.
const isDark = ref(false);
let themeObserver: MutationObserver | undefined;

function readIsDark(): void {
    isDark.value = document.documentElement.classList.contains('dark');
}

function watchTheme(): void {
    readIsDark();
    themeObserver ??= new MutationObserver(readIsDark);
    // Observing the same node again only replaces the options, so every
    // mounted caller can do this without stacking callbacks.
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}

export function useAppearance() {
    onMounted(() => {
        appearance.value = readStoredAppearance();
        watchTheme();
    });

    function setAppearance(value: Appearance): void {
        appearance.value = value;

        const secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `${COOKIE}=${value}; path=/; max-age=${ONE_YEAR_IN_SECONDS}; SameSite=Lax${secure}`;

        applyAppearance(value);
        readIsDark();
    }

    return {
        appearance: readonly(appearance),
        isDark: readonly(isDark),
        setAppearance,
    };
}
