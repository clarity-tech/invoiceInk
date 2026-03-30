<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'InvoiceInk';

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
            <a href="/">
                <span class="text-2xl font-bold text-brand-600">{{
                    appName
                }}</span>
            </a>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
