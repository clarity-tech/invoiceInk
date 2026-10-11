<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineProps<{
    /** Renders the required marker. Kept out of the slot so every one matches. */
    required?: boolean;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <label
        :class="
            cn(
                'block text-sm font-medium text-foreground select-none',
                $props.class,
            )
        "
    >
        <slot />
        <span v-if="required" class="text-destructive" aria-hidden="true">
            *</span
        >
    </label>
</template>
