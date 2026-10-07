<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    title?: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
    /**
     * Where to send focus after a confirmed action, for callers whose confirm
     * removes the element that opened the dialog. Give it a container that
     * survives the change -- typically the list itself, with tabindex="-1".
     */
    restoreFocusTo?: HTMLElement | null;
}>();

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

const panel = ref<HTMLElement | null>(null);
const cancelButton = ref<HTMLButtonElement | null>(null);

/** The element to hand focus back to once the dialog closes. */
let previouslyFocused: HTMLElement | null = null;

/**
 * Whether this close came from confirming rather than cancelling. A confirmed
 * destructive action usually removes the row that opened the dialog, so the
 * opener is the one element we must not rely on.
 */
let confirmed = false;

const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'textarea:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

/**
 * The panel only ever holds the confirm and cancel buttons, both always
 * visible, so the selector alone is enough — no visibility filtering, which
 * would depend on layout the test environment does not compute.
 */
function onConfirm() {
    confirmed = true;
    emit('confirm');
}

function focusable(): HTMLElement[] {
    if (!panel.value) {
        return [];
    }

    return Array.from(panel.value.querySelectorAll<HTMLElement>(FOCUSABLE));
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        event.preventDefault();
        emit('cancel');

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const elements = focusable();

    if (elements.length === 0) {
        return;
    }

    const first = elements[0];
    const last = elements[elements.length - 1];
    const active = document.activeElement;
    const inside = panel.value?.contains(active as Node) ?? false;

    // Wrap at both ends, and pull focus back in if it has escaped the panel
    // entirely (which is what happens on the first Tab after opening).
    if (event.shiftKey && (active === first || !inside)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && (active === last || !inside)) {
        event.preventDefault();
        first.focus();
    }
}

watch(
    () => props.show,
    async (show) => {
        if (show) {
            previouslyFocused = document.activeElement as HTMLElement | null;
            document.addEventListener('keydown', onKeydown);
            await nextTick();

            // A destructive dialog opens on Cancel rather than the first
            // focusable element, so that a reflexive Enter does not confirm
            // the very thing the dialog exists to double-check.
            const target = props.destructive
                ? cancelButton.value
                : focusable()[0];
            target?.focus();

            return;
        }

        document.removeEventListener('keydown', onKeydown);

        // Restoring focus to the opener is right after a cancel, but wrong
        // after a confirm that deletes the row the opener lives in: the node
        // is detached, .focus() on it does nothing, and focus falls to <body>,
        // dumping keyboard and screen reader users at the top of the document.
        //
        // The detachment is not reliably immediate either -- an optimistic
        // update removes the row on the next tick, while a plain delete keeps
        // it until the server replies. Preferring the caller's container on
        // confirm sidesteps the timing question entirely, because that element
        // is there either way.
        const target =
            confirmed && props.restoreFocusTo
                ? props.restoreFocusTo
                : previouslyFocused;

        confirmed = false;
        previouslyFocused = null;

        await nextTick();
        target?.focus?.();
    },
);

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <Teleport v-if="isMounted && show" to="body">
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div
                class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    @click="emit('cancel')"
                />

                <span
                    class="hidden sm:inline-block sm:h-screen sm:align-middle"
                    aria-hidden="true"
                    >&#8203;</span
                >

                <div
                    ref="panel"
                    class="relative inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="title || 'Confirm Action'"
                >
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ title || 'Confirm Action' }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                {{
                                    message ||
                                    'Are you sure you want to proceed?'
                                }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
                    >
                        <button
                            type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent px-4 py-2 text-base font-medium text-white shadow-sm focus:ring-2 focus:ring-offset-2 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                            :class="
                                destructive
                                    ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
                                    : 'bg-brand-600 hover:bg-brand-700 focus:ring-brand-500'
                            "
                            @click="onConfirm"
                        >
                            {{ confirmLabel || 'Confirm' }}
                        </button>
                        <button
                            ref="cancelButton"
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="emit('cancel')"
                        >
                            {{ cancelLabel || 'Cancel' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
