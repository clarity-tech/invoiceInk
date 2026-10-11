<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <th
        scope="col"
        :class="
            cn(
                'px-4 py-2.5 text-left align-middle text-xs font-medium text-muted-foreground',
                props.class,
            )
        "
    >
        <slot />
    </th>
</template>
