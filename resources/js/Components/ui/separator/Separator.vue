<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        orientation?: 'horizontal' | 'vertical';
        /**
         * A rule drawn purely to group things visually, which is the usual
         * case. Decorative separators are hidden from assistive technology so
         * they do not announce a structural break that is not really there.
         */
        decorative?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    { orientation: 'horizontal', decorative: true },
);
</script>

<template>
    <div
        :role="decorative ? 'none' : 'separator'"
        :aria-hidden="decorative ? 'true' : undefined"
        :aria-orientation="decorative ? undefined : orientation"
        :class="
            cn(
                'shrink-0 bg-border',
                orientation === 'horizontal'
                    ? 'h-px w-full'
                    : 'w-px self-stretch',
                props.class,
            )
        "
    />
</template>
