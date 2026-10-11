<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { controlClasses } from '../input';
import { cn } from '@/lib/utils';

defineProps<{ class?: HTMLAttributes['class'] }>();

const model = defineModel<string | null>();
</script>

<template>
    <textarea
        v-model="model"
        :class="cn(controlClasses, 'min-h-20 resize-y', $props.class)"
    />
</template>
