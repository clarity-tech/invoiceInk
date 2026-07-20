<script setup lang="ts">
import { computed, ref } from 'vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { calcLineTotalWithTax } from '@/composables/useInvoiceCalculator';
import type { LineItem } from '@/composables/useInvoiceCalculator';
import type { Currency, TaxRateOption } from '@/types';

const props = defineProps<{
    item: LineItem;
    index: number;
    currency: Currency;
    taxOptions: TaxRateOption[];
    canRemove: boolean;
    errors: Record<string, string>;
}>();

const emit = defineEmits<{
    update: [item: LineItem];
    remove: [];
}>();

const { formatMoney, CURRENCY_CONFIG } = useFormatMoney();

const currencySymbol = computed(
    () => CURRENCY_CONFIG[props.currency]?.symbol ?? props.currency,
);

// Reserve more left padding for multi-character codes (e.g. "AED") so the
// prefix doesn't overlap with the input value.
const symbolPaddingClass = computed(() => {
    const length = currencySymbol.value.trim().length;

    if (length >= 3) {
        return 'pl-12';
    }

    if (length === 2) {
        return 'pl-9';
    }

    return 'pl-8';
});

const lineTotal = computed(() => calcLineTotalWithTax(props.item));

function updateField(field: keyof LineItem, value: string | number | null) {
    const updated = { ...props.item };

    if (
        field === 'quantity' ||
        field === 'unit_price' ||
        field === 'tax_rate'
    ) {
        updated[field] = Number(value) || 0;
    } else {
        (updated as any)[field] = value;
    }

    emit('update', updated);
}

// Tax-rate control: a single dropdown of the country's predefined tax options
// plus a "Custom rate…" option that reveals the manual percentage input.
const CUSTOM_TAX = 'custom';
const NO_TAX = '0';

// Show a synthetic "No tax (0%)" entry only when no option already covers 0%.
const hasZeroRateOption = computed(() =>
    props.taxOptions.some((t) => t.rate === 0),
);

// Order the dropdown by rate ascending (e.g. 0% → 5% → 20%), keeping a stable
// order for options that share a rate.
const sortedTaxOptions = computed(() =>
    [...props.taxOptions].sort((a, b) => a.rate - b.rate),
);

function resolveInitialTaxMode(): string {
    const match = props.taxOptions.find((t) => t.rate === props.item.tax_rate);

    if (match) {
        return match.key;
    }

    if (props.item.tax_rate === 0) {
        return NO_TAX;
    }

    return CUSTOM_TAX;
}

const taxMode = ref<string>(resolveInitialTaxMode());

const showCustomRate = computed(() => taxMode.value === CUSTOM_TAX);

function onTaxModeChange(value: string) {
    taxMode.value = value;

    if (value === CUSTOM_TAX) {
        // Keep the current rate; reveal the manual input for editing.
        return;
    }

    if (value === NO_TAX) {
        updateField('tax_rate', 0);

        return;
    }

    const option = props.taxOptions.find((t) => t.key === value);

    if (option) {
        updateField('tax_rate', option.rate);
    }
}

function errorFor(field: string): string | undefined {
    return props.errors[`items.${props.index}.${field}`];
}
</script>

<template>
    <tr class="align-top">
        <td class="px-4 py-3">
            <input
                :value="item.description"
                type="text"
                placeholder="Item description"
                maxlength="2000"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                @input="
                    updateField(
                        'description',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />
            <p v-if="errorFor('description')" class="mt-1 text-xs text-red-600">
                {{ errorFor('description') }}
            </p>

            <div class="mt-2">
                <input
                    :value="item.sac_code"
                    type="text"
                    placeholder="SAC Code (optional)"
                    class="w-40 rounded-md border border-gray-300 px-2 py-1 text-xs focus:ring-1 focus:ring-brand-500 focus:outline-none"
                    @input="
                        updateField(
                            'sac_code',
                            ($event.target as HTMLInputElement).value || null,
                        )
                    "
                />
                <span v-if="!item.sac_code" class="ml-2 text-xs text-gray-400"
                    >6-digit code</span
                >
                <span
                    v-if="item.sac_code"
                    class="ml-2 inline-flex items-center rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
                >
                    SAC: {{ item.sac_code }}
                </span>
            </div>
        </td>
        <td class="px-4 py-3">
            <input
                :value="item.quantity"
                type="number"
                min="1"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                @input="
                    updateField(
                        'quantity',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />
            <p v-if="errorFor('quantity')" class="mt-1 text-xs text-red-600">
                {{ errorFor('quantity') }}
            </p>
        </td>
        <td class="px-4 py-3">
            <div class="relative">
                <span
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400"
                    >{{ currencySymbol }}</span
                >
                <input
                    :value="item.unit_price / 100"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    :class="[
                        'w-full rounded-md border border-gray-300 py-2 pr-3 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none',
                        symbolPaddingClass,
                    ]"
                    @input="
                        updateField(
                            'unit_price',
                            Math.round(
                                Number(
                                    ($event.target as HTMLInputElement).value,
                                ) * 100,
                            ),
                        )
                    "
                />
            </div>
            <p v-if="errorFor('unit_price')" class="mt-1 text-xs text-red-600">
                {{ errorFor('unit_price') }}
            </p>
        </td>
        <td class="px-4 py-3">
            <select
                :value="taxMode"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                @change="
                    onTaxModeChange(($event.target as HTMLSelectElement).value)
                "
            >
                <option v-if="!hasZeroRateOption" value="0">No tax (0%)</option>
                <option
                    v-for="t in sortedTaxOptions"
                    :key="t.key"
                    :value="t.key"
                >
                    {{ t.name }}
                </option>
                <option value="custom">Custom rate…</option>
            </select>

            <div v-if="showCustomRate" class="relative mt-2">
                <input
                    :value="item.tax_rate / 100"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    placeholder="0"
                    aria-label="Custom tax rate"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 pr-8 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                    @input="
                        updateField(
                            'tax_rate',
                            Math.round(
                                Number(
                                    ($event.target as HTMLInputElement).value,
                                ) * 100,
                            ),
                        )
                    "
                />
                <span
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm text-gray-400"
                    >%</span
                >
            </div>

            <p v-if="errorFor('tax_rate')" class="mt-1 text-xs text-red-600">
                {{ errorFor('tax_rate') }}
            </p>
        </td>
        <td class="px-4 py-3 text-right">
            <span class="text-sm font-medium text-gray-900">
                {{ formatMoney(lineTotal, currency) }}
            </span>
        </td>
        <td class="px-4 py-3 text-center">
            <button
                v-if="canRemove"
                type="button"
                class="text-lg font-bold text-red-500 hover:text-red-700"
                :aria-label="`Remove item ${index + 1}`"
                @click="emit('remove')"
            >
                &times;
            </button>
        </td>
    </tr>
</template>
