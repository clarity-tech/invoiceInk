<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceSwitch from '@/Components/AppearanceSwitch.vue';
import AppLogo from '@/Components/AppLogo.vue';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center bg-canvas pt-6 sm:justify-center sm:pt-0"
    >
        <Head :title="title" />

        <div class="absolute top-3 right-3 sm:top-4 sm:right-4">
            <AppearanceSwitch />
        </div>

        <div>
            <a href="/" aria-label="InvoiceInk home">
                <AppLogo class="h-10 w-auto text-foreground" />
            </a>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-card px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>

        <nav
            aria-label="Legal"
            class="mt-6 mb-6 flex gap-5 text-sm text-muted-foreground"
        >
            <a href="/terms-of-service" class="hover:text-foreground"
                >Terms of Service</a
            >
            <a href="/privacy-policy" class="hover:text-foreground"
                >Privacy Policy</a
            >
        </nav>
    </div>
</template>
