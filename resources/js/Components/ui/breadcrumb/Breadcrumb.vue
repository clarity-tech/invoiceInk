<script setup lang="ts">
defineProps<{
    separator?: string;
}>();
</script>

<template>
    <nav aria-label="Breadcrumb">
        <ol class="flex items-center gap-1.5 text-sm">
            <slot />
        </ol>
    </nav>
</template>
