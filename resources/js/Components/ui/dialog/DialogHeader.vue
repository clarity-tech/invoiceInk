<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { Button } from '../button';

defineProps<{
    title: string;
    description?: string;
    /** For the dialog's aria-labelledby. */
    titleId?: string;
}>();

const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <div
        data-slot="dialog-header"
        class="flex items-start gap-4 border-b border-border px-6 py-5"
    >
        <div
            v-if="$slots.icon"
            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary-soft-foreground [&_svg]:size-5"
        >
            <slot name="icon" />
        </div>
        <div class="min-w-0 flex-1">
            <h2
                :id="titleId"
                class="text-lg leading-snug font-semibold text-foreground"
            >
                {{ title }}
            </h2>
            <p v-if="description" class="mt-0.5 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <Button
            variant="ghost"
            size="icon"
            class="-mt-1 -mr-2 size-8 text-muted-foreground"
            aria-label="Close"
            @click="emit('close')"
        >
            <X />
        </Button>
    </div>
</template>
