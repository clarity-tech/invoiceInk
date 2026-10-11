<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { Pencil, Plus, X } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { Input, quietControlClasses } from '@/Components/ui/input';
import { Select } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
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

const { formatAmount } = useFormatMoney();

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

/**
 * The SAC code is a six-digit classification: set once, then only read. As a
 * second input under every description it cost each row the height of a field
 * nobody was editing, so it reads as a line of text and becomes an input when
 * it is asked for.
 */
const editingSac = ref(false);
const sacInput = ref<{ $el: HTMLInputElement } | null>(null);

async function editSac() {
    editingSac.value = true;
    await nextTick();
    sacInput.value?.$el?.focus();
}
</script>

<template>
    <tr class="group align-top">
        <td class="px-2 py-2">
            <Textarea
                :model-value="item.description"
                rows="1"
                autosize
                placeholder="Item description"
                maxlength="2000"
                :class="quietControlClasses"
                :aria-invalid="Boolean(errorFor('description')) || undefined"
                @input="
                    updateField(
                        'description',
                        ($event.target as HTMLTextAreaElement).value,
                    )
                "
            />
            <p
                v-if="errorFor('description')"
                class="mt-1 text-xs text-destructive"
            >
                {{ errorFor('description') }}
            </p>

            <div class="mt-1">
                <Input
                    v-if="editingSac"
                    ref="sacInput"
                    :model-value="item.sac_code"
                    type="text"
                    placeholder="6-digit code"
                    aria-label="SAC Code (optional)"
                    class="w-40 px-2 py-1 text-xs"
                    @blur="editingSac = false"
                    @keyup.enter="editingSac = false"
                    @input="
                        updateField(
                            'sac_code',
                            ($event.target as HTMLInputElement).value || null,
                        )
                    "
                />
                <button
                    v-else
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-1 rounded px-1 py-0.5 text-xs text-muted-foreground hover:bg-muted hover:text-foreground"
                    :aria-label="
                        item.sac_code
                            ? `Edit SAC code for item ${index + 1}`
                            : `Add a SAC code to item ${index + 1}`
                    "
                    @click="editSac"
                >
                    <component
                        :is="item.sac_code ? Pencil : Plus"
                        class="size-3"
                    />
                    {{ item.sac_code ? `SAC ${item.sac_code}` : 'SAC code' }}
                </button>
            </div>
        </td>
        <td class="px-2 py-2">
            <Input
                :model-value="item.quantity"
                type="number"
                min="1"
                :class="[quietControlClasses, 'text-right']"
                :aria-invalid="Boolean(errorFor('quantity')) || undefined"
                @input="
                    updateField(
                        'quantity',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />
            <p
                v-if="errorFor('quantity')"
                class="mt-1 text-xs text-destructive"
            >
                {{ errorFor('quantity') }}
            </p>
        </td>
        <td class="px-2 py-2">
            <!--
                No currency symbol per line. A document has one currency, named
                in the Amount column and again in the totals, and repeating it
                on every row cost a prefix whose left padding had to change
                with the symbol's length -- 8px for a dollar, 12px for an AED.
            -->
            <div>
                <Input
                    :model-value="item.unit_price / 100"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                    :class="[quietControlClasses, 'text-right']"
                    :aria-invalid="Boolean(errorFor('unit_price')) || undefined"
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
            <p
                v-if="errorFor('unit_price')"
                class="mt-1 text-xs text-destructive"
            >
                {{ errorFor('unit_price') }}
            </p>
        </td>
        <td class="px-2 py-2">
            <Select
                :model-value="taxMode"
                :class="quietControlClasses"
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
            </Select>

            <div v-if="showCustomRate" class="relative mt-2">
                <Input
                    :model-value="item.tax_rate / 100"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    placeholder="0"
                    aria-label="Custom tax rate"
                    :class="[quietControlClasses, 'pr-8']"
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
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm text-muted-foreground"
                    >%</span
                >
            </div>

            <p
                v-if="errorFor('tax_rate')"
                class="mt-1 text-xs text-destructive"
            >
                {{ errorFor('tax_rate') }}
            </p>
        </td>
        <td class="px-2 py-2 text-right">
            <span class="text-sm font-medium text-foreground">
                {{ formatAmount(lineTotal, currency) }}
            </span>
        </td>
        <td class="px-2 py-2 text-center">
            <!--
                Hidden until the row is pointed at. A bare red glyph was the
                loudest thing in the row, on every line of every invoice; even
                as a grey icon it is a control for an action nobody takes often.

                Guarded by `hover: hover`, because a touch screen never
                delivers the hover that would bring it back -- there, it simply
                stays visible. It also reappears on keyboard focus, which is
                the other way to reach it.
            -->
            <Button
                v-if="canRemove"
                type="button"
                variant="ghost"
                size="icon"
                class="text-muted-foreground transition-opacity hover:text-destructive [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100 [@media(hover:hover)]:focus-visible:opacity-100"
                :aria-label="`Remove item ${index + 1}`"
                @click="emit('remove')"
            >
                <X />
            </Button>
        </td>
    </tr>
</template>
