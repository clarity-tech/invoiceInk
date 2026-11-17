import {
    computed,
    defineAsyncComponent,
    reactive,
    readonly,
    ref,
    type Component,
    type Ref,
} from 'vue';

/**
 * A dialog whose code is fetched the first time it is wanted, not with the
 * page. The email composer carries the TipTap editor (~160 KB gzipped) and the
 * customer form carries libphonenumber (~36 KB); every invoice and customer
 * page used to download them up front for a dialog most visits never open.
 */
export interface LazyDialog<T extends Component> {
    /** Render it behind `v-if="dialog.loaded"`, or inside markup only shown once open. */
    component: T;
    /** The code has arrived; from here on the dialog opens at once. */
    loaded: Readonly<Ref<boolean>>;
    /** Fetch the code; resolves once it is there. A failed fetch is not remembered. */
    load: () => Promise<void>;
}

// Module-level, like each LazyDialog below: one failure notice for the app,
// read by NetworkErrorNotice whichever page the dialog was opened from.
const failed = ref(false);

/** A dialog's code could not be fetched when someone tried to open it. */
export const lazyDialogFailed = readonly(failed);

export function dismissLazyDialogFailure(): void {
    failed.value = false;
}

/**
 * Declare a lazily loaded dialog. Call it once at module level (see
 * `@/Components/lazyDialogs`), not in a component's setup: the loaded flag is
 * then shared, so a composer fetched on one page opens instantly on the next.
 */
export function defineLazyDialog<T extends Component>(
    importer: () => Promise<{ default: T }>,
): LazyDialog<T> {
    const loaded = ref(false);
    let pending: Promise<{ default: T }> | null = null;

    // One request shared by prefetching, opening and the component itself.
    function fetchModule(): Promise<{ default: T }> {
        pending ??= importer().then(
            (module) => {
                loaded.value = true;

                return module;
            },
            (error: unknown) => {
                // Forget the failure so the next attempt fetches again: the
                // connection may be back, and a cached rejection never would be.
                pending = null;
                throw error;
            },
        );

        return pending;
    }

    return {
        component: defineAsyncComponent(fetchModule),
        loaded: readonly(loaded),
        load: () => fetchModule().then(() => undefined),
    };
}

/**
 * Open a lazy dialog from its trigger.
 *
 * `intent` goes on the trigger (`v-on="dialog.intent"`): pointerdown, focus and
 * hover start the fetch, so the code is usually there by the time the click
 * lands, and the service worker keeps it for later visits. A failed prefetch
 * says nothing; only a click that cannot open the dialog reports it.
 *
 * While a click waits for the code, the trigger shows that it is busy
 * (`aria-busy` and a spinner, from `opening`). It is never disabled: a focused
 * button that becomes disabled drops focus to the page, and Dialog would then
 * hand focus back to the page instead of the trigger when it closes. The
 * dialog mounts already open, and Dialog moves focus into it as usual.
 *
 * `key` tells apart triggers sharing one dialog, such as the Edit button on
 * each row, so only the one pressed shows busy.
 */
export function useLazyDialog<T extends Component>(dialog: LazyDialog<T>) {
    const opening = ref<string | number | null>(null);

    function prefetch(): void {
        dialog.load().catch(() => {});
    }

    async function open(
        show: () => void,
        key: string | number = 'default',
    ): Promise<void> {
        if (dialog.loaded.value) {
            show();

            return;
        }

        if (opening.value !== null) {
            return;
        }

        opening.value = key;

        try {
            await dialog.load();
            failed.value = false;
            show();
        } catch {
            failed.value = true;
        } finally {
            opening.value = null;
        }
    }

    return reactive({
        loaded: dialog.loaded,
        opening: readonly(opening),
        busy: computed(() => opening.value !== null),
        intent: {
            pointerdown: prefetch,
            focus: prefetch,
            mouseenter: prefetch,
        },
        open,
    });
}
