<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from './AppLayout.vue';

defineProps<{
    title?: string;
}>();

const page = usePage();

function isActive(path: string): boolean {
    return page.url.startsWith(path);
}
</script>

<template>
    <AppLayout :title="title ?? 'Settings'">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Settings
            </h2>
        </template>

        <div class="py-4">
            <!-- Settings sub-nav -->
            <div
                class="mb-6 flex gap-4 border-b border-gray-200 px-4 pb-3 sm:px-0"
            >
                <Link
                    href="/settings"
                    :class="
                        isActive('/settings') &&
                        !isActive('/numbering-series') &&
                        !isActive('/email-templates')
                            ? 'border-b-2 border-brand-500 text-sm font-medium text-brand-600'
                            : 'text-sm font-medium text-gray-500 hover:text-gray-700'
                    "
                >
                    Overview
                </Link>
                <Link
                    href="/email-templates"
                    :class="
                        isActive('/email-templates')
                            ? 'border-b-2 border-brand-500 text-sm font-medium text-brand-600'
                            : 'text-sm font-medium text-gray-500 hover:text-gray-700'
                    "
                >
                    Email Templates
                </Link>
            </div>

            <slot />
        </div>
    </AppLayout>
</template>
