<script setup lang="ts">
import { computed } from 'vue';
import { formatAmount, formatMoney } from '@/composables/useFormatMoney';
import type { Currency } from '@/types';

const props = defineProps<{
    amount: number;
    currency: Currency;
    /**
     * Drop the symbol. For a column of figures that all share one currency,
     * named once beside the total they add up to.
     */
    bare?: boolean;
}>();

const formatted = computed(() =>
    props.bare
        ? formatAmount(props.amount, props.currency)
        : formatMoney(props.amount, props.currency),
);
</script>

<template>
    <span>{{ formatted }}</span>
</template>
