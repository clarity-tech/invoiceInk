<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { WifiOff, X } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/Components/ui/button';

const visible = ref(false);
let stopListening: Array<() => void> = [];

/**
 * The service worker's offline page only covers full page loads. Inertia
 * visits are XHRs, so a click while offline would otherwise do nothing.
 */
onMounted(() => {
    stopListening = [
        router.on('networkError', () => {
            visible.value = true;

            // Handled here, so Inertia need not rethrow it as an unhandled rejection.
            return false;
        }),
        router.on('success', () => {
            visible.value = false;
        }),
    ];
});

onBeforeUnmount(() => stopListening.forEach((stop) => stop()));
</script>

<template>
    <div
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
        class="pointer-events-none fixed inset-x-0 top-0 z-50 flex justify-center p-3"
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
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Check your connection and try again.
                </p>
            </div>
            <Button
                size="icon"
                variant="ghost"
                class="-mt-1 -mr-1 size-8"
                aria-label="Dismiss"
                @click="visible = false"
            >
                <X aria-hidden="true" />
            </Button>
        </div>
    </div>
</template>
