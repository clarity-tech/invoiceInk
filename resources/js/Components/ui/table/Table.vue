<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <!--
        The scroll container is part of the component, not something each page
        remembers to add. A table of currency columns is wider than a phone,
        and the invoice list has already regressed once (5542f48e) by letting
        one push the whole page sideways instead of scrolling itself.
    -->
    <div class="relative w-full overflow-x-auto">
        <table :class="cn('w-full caption-bottom text-sm', props.class)">
            <slot />
        </table>
    </div>
</template>
