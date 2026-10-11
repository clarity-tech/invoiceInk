<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { buttonVariants, type ButtonVariants } from '.';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        variant?: ButtonVariants['variant'];
        size?: ButtonVariants['size'];
        /**
         * Render as something other than a `<button>` — Inertia's `Link` for an
         * in-app visit, or `a` for a download or an external href. Keeping this
         * on one component is what stops "a link that looks like a button" from
         * being reimplemented per page.
         */
        as?: string | Component;
        class?: HTMLAttributes['class'];
    }>(),
    { as: 'button' },
);
</script>

<template>
    <component
        :is="as"
        :class="cn(buttonVariants({ variant, size }), props.class)"
    >
        <slot />
    </component>
</template>
