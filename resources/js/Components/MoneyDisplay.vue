<script setup lang="ts">
import { computed } from 'vue';
import { formatMoney } from '@/composables/useFormatMoney';
import type { Currency } from '@/types';

const props = defineProps<{
    amount: number;
    currency: Currency;
}>();

const formatted = computed(() => formatMoney(props.amount, props.currency));
</script>

<template>
    <span>{{ formatted }}</span>
</template>
