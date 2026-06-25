<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <Head :title="title" />

        <div>
            <a href="/" aria-label="InvoiceInk home">
                <AppLogo class="h-10 w-auto text-gray-900" />
            </a>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
