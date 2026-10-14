<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="text-sm text-muted-foreground transition-colors hover:text-soft-foreground"
    >
        <slot />
    </Link>
</template>
