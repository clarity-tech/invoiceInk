<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import NavigationMenu from './NavigationMenu.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import NetworkErrorNotice from '@/Components/NetworkErrorNotice.vue';

defineProps<{
    title?: string;
    /** For pages with their own bar along the bottom (the invoice form). */
    hideTabBar?: boolean;
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
        <NavigationMenu :hide-tab-bar="hideTabBar" />

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
        <!-- Below md, room at the foot for the fixed tab bar, so the last row
             and the pagination can scroll clear of it. -->
        <main
            id="main-content"
            :class="{ 'pb-(--tab-bar-height) md:pb-0': !hideTabBar }"
        >
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <FlashMessages />
                <slot />
            </div>
        </main>

        <NetworkErrorNotice />
    </div>
</template>
