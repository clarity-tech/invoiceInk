<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineProps<{ class?: HTMLAttributes['class'] }>();

const model = defineModel<boolean>();
</script>

<template>
    <input
        type="checkbox"
        v-model="model"
        :class="
            cn(
                'size-4 shrink-0 cursor-pointer rounded border-input text-primary shadow-xs',
                'focus:ring-2 focus:ring-ring/30 focus:ring-offset-0 focus:outline-none',
                'disabled:cursor-not-allowed disabled:opacity-50',
                $props.class,
            )
        "
    />
</template>
