<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { WifiOff, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/Components/ui/button';
import {
    dismissLazyDialogFailure,
    lazyDialogFailed,
} from '@/composables/useLazyDialog';

const visitFailed = ref(false);
let stopListening: Array<() => void> = [];

/**
 * The service worker's offline page only covers full page loads. Inertia
 * visits are XHRs, so a click while offline would otherwise do nothing.
 *
 * The same goes for a dialog whose code is fetched on first use (see
 * useLazyDialog): offline, or after a deploy has renamed the file the open
 * page still asks for, its button would simply not open. A renamed file never
 * comes back, so that notice offers a reload as well as a retry.
 */
onMounted(() => {
    stopListening = [
        router.on('networkError', () => {
            visitFailed.value = true;

            // Handled here, so Inertia need not rethrow it as an unhandled rejection.
            return false;
        }),
        router.on('success', () => {
            visitFailed.value = false;
            dismissLazyDialogFailure();
        }),
    ];
});

onBeforeUnmount(() => stopListening.forEach((stop) => stop()));

const visible = computed(() => visitFailed.value || lazyDialogFailed.value);
// Only a dialog's code went missing; a failed visit says the shorter thing.
const dialogFailed = computed(
    () => lazyDialogFailed.value && !visitFailed.value,
);

function dismiss() {
    visitFailed.value = false;
    dismissLazyDialogFailure();
}

function reload() {
    window.location.reload();
}
</script>

<template>
    <!--
        Above dialogs (z-50): a send or a "Send one combined reminder" can
        fail from inside one, and the overlay would otherwise hide the notice.
    -->
    <div
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
        class="pointer-events-none fixed inset-x-0 top-0 z-60 flex justify-center p-3"
    >
        <div
            v-if="visible"
            data-testid="network-error"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-border bg-card p-4 shadow-lg"
        >
            <WifiOff
                class="mt-0.5 size-5 shrink-0 text-destructive"
                aria-hidden="true"
            />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-foreground">
                    Couldn't reach InvoiceInk
                </p>
                <p
                    v-if="dialogFailed"
                    class="mt-0.5 text-sm text-muted-foreground"
                >
                    That didn't open. Check your connection and try again, or
                    reload the page if InvoiceInk was just updated.
                </p>
                <p v-else class="mt-0.5 text-sm text-muted-foreground">
                    Check your connection and try again.
                </p>
                <Button
                    v-if="dialogFailed"
                    size="sm"
                    variant="outline"
                    class="mt-2"
                    @click="reload"
                >
                    Reload page
                </Button>
            </div>
            <Button
                size="icon"
                variant="ghost"
                class="-mt-1 -mr-1 size-8"
                aria-label="Dismiss"
                @click="dismiss"
            >
                <X aria-hidden="true" />
            </Button>
        </div>
    </div>
</template>
