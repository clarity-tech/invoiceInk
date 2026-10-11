<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { controlClasses } from '.';
import { cn } from '@/lib/utils';

/**
 * The `<input>` is the root element on purpose. Attributes fall through to the
 * root, so `id`, `name`, `type`, `step` and the `data-*` hooks the browser
 * tests select on all land on the real control rather than on a wrapper.
 */
defineProps<{ class?: HTMLAttributes['class'] }>();

const model = defineModel<string | number | null>();
</script>

<template>
    <input v-model="model" :class="cn(controlClasses, $props.class)" />
</template>
