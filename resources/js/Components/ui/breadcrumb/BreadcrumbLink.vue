<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="text-sm text-gray-500 transition-colors hover:text-gray-700"
    >
        <slot />
    </Link>
</template>
