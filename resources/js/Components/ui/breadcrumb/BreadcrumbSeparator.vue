<script setup lang="ts">
import { ChevronRight } from 'lucide-vue-next';
</script>

<template>
    <li role="presentation" aria-hidden="true" class="text-faint-foreground">
        <slot>
            <ChevronRight class="size-3.5" />
        </slot>
    </li>
</template>
