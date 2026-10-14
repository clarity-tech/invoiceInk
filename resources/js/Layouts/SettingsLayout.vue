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
            <h2 class="text-xl leading-tight font-semibold text-foreground">
                Settings
            </h2>
        </template>

        <div class="py-4">
            <!-- Settings sub-nav -->
            <div
                class="mb-6 flex gap-4 border-b border-border px-4 pb-3 sm:px-0"
            >
                <Link
                    href="/settings"
                    :class="
                        isActive('/settings') && !isActive('/email-templates')
                            ? 'border-b-2 border-primary text-sm font-medium text-primary'
                            : 'text-sm font-medium text-muted-foreground hover:text-soft-foreground'
                    "
                >
                    Overview
                </Link>
                <Link
                    href="/email-templates"
                    :class="
                        isActive('/email-templates')
                            ? 'border-b-2 border-primary text-sm font-medium text-primary'
                            : 'text-sm font-medium text-muted-foreground hover:text-soft-foreground'
                    "
                >
                    Email Templates
                </Link>
            </div>

            <slot />
        </div>
    </AppLayout>
</template>
