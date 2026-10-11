<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <h2 :class="cn('text-sm font-semibold tracking-tight', props.class)">
        <slot />
    </h2>
</template>
