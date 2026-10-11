<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <!--
        `data-slot` is shadcn's own convention upstream, and it gives tests and
        page-level CSS a handle on "a card" that does not depend on which
        rounding utility the card happens to use this month.
    -->
    <div
        data-slot="card"
        :class="
            cn(
                'rounded-xl border border-border bg-card text-card-foreground shadow-sm',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
