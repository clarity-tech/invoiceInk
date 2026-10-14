<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import NavigationMenu from './NavigationMenu.vue';
import FlashMessages from '@/Components/FlashMessages.vue';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-canvas">
        <Head :title="title" />
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-2 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-primary-foreground"
        >
            Skip to content
        </a>
        <NavigationMenu />

        <!-- Page Heading -->
        <header v-if="$slots.header" class="bg-card shadow">
            <div
                class="mx-auto flex min-h-17 w-full max-w-7xl items-center px-4 py-4 sm:px-6 lg:px-8"
            >
                <div class="w-full">
                    <slot name="header" />
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main id="main-content">
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <FlashMessages />
                <slot />
            </div>
        </main>
    </div>
</template>
