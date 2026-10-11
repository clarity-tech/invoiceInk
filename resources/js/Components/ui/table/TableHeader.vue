<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <thead :class="cn('border-b border-border', props.class)">
        <slot />
    </thead>
</template>
