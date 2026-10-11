<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { controlClasses } from '../input';
import { cn } from '@/lib/utils';

/**
 * A native `<select>`, not a listbox built out of divs. The form is long and
 * mostly a sequence of choices, and on a phone the platform picker beats
 * anything we would reimplement. It also keeps `fill()`/`select()` working in
 * the browser tests, and costs no extra dependency.
 */
defineProps<{ class?: HTMLAttributes['class'] }>();

const model = defineModel<string | number | null>();
</script>

<template>
    <select
        v-model="model"
        :class="cn(controlClasses, 'cursor-pointer pr-8', $props.class)"
    >
        <slot />
    </select>
</template>
