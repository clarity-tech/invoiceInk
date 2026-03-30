<script setup lang="ts">
import { computed } from 'vue';
import type { InvoiceStatus } from '@/types';

const props = defineProps<{
    status: InvoiceStatus;
}>();

const config = computed(() => {
    const map: Record<InvoiceStatus, { label: string; classes: string }> = {
        draft: { label: 'Draft', classes: 'bg-gray-100 text-gray-800' },
        sent: { label: 'Sent', classes: 'bg-brand-100 text-brand-800' },
        accepted: {
            label: 'Accepted',
            classes: 'bg-yellow-100 text-yellow-800',
        },
        partially_paid: {
            label: 'Partially Paid',
            classes: 'bg-amber-100 text-amber-800',
        },
        paid: { label: 'Paid', classes: 'bg-green-100 text-green-800' },
        void: { label: 'Void', classes: 'bg-red-100 text-red-800' },
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
