<script setup lang="ts">
import { computed } from 'vue';
import type { InvoiceStatus } from '@/types';

const props = defineProps<{
    status: InvoiceStatus;
}>();

const config = computed(() => {
    const map: Record<InvoiceStatus, { label: string; classes: string }> = {
        draft: { label: 'Draft', classes: 'bg-secondary text-foreground' },
        sent: {
            label: 'Sent',
            classes: 'bg-primary-soft text-primary-soft-foreground',
        },
        accepted: {
            label: 'Accepted',
            classes: 'bg-caution-soft text-caution-soft-foreground',
        },
        partially_paid: {
            label: 'Partially Paid',
            classes: 'bg-warning-soft text-warning-soft-foreground',
        },
        paid: {
            label: 'Paid',
            classes: 'bg-success-soft text-success-soft-foreground',
        },
        void: {
            label: 'Void',
            classes: 'bg-destructive-soft text-destructive-soft-foreground',
        },
    };

    return map[props.status];
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="config.classes"
    >
        {{ config.label }}
    </span>
</template>
