<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, inject, nextTick, ref } from 'vue';
import { Plus } from 'lucide-vue-next';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { buildGstBreakdown } from '@/composables/useGstBreakdown';
import { useInvoiceCalculator } from '@/composables/useInvoiceCalculator';
import type { LineItem } from '@/composables/useInvoiceCalculator';
import { calcTcsAmount, calcTdsAmount } from '@/composables/useSourceTaxes';
import type { Currency, TaxRateOption } from '@/types';
import ItemRow from '../ItemRow.vue';
import { DocumentFormKey } from './formContext';

/**
 * The line items and the totals they add up to.
 *
 * The lines come in as a model rather than through the injected form. The form
 * that owns them copies them into `form.items` only when it submits (see
 * InvoiceForm's syncItems), so `form.items` is not the list being edited. What
 * the totals need from the form -- place of supply, TDS/TCS, errors -- comes
 * from DocumentFormKey, as in the other shared cards.
 */
const props = defineProps<{
    currency: Currency;
    taxOptions: TaxRateOption[];
    /** Resolved by useDocumentParties, with the rest of the GST inputs. */
    supplierStateCode: string | null;
    isForeignCustomer: boolean;
}>();

const items = defineModel<LineItem[]>('items', { required: true });

const form = inject(DocumentFormKey)!;

const page = usePage();

const { formatAmount, currencySymbol } = useFormatMoney();

const { totals } = useInvoiceCalculator(items);

// Stable keys for the v-for so each ItemRow keeps its own local UI state
// (e.g. the tax-rate selection) when rows are added or removed.
let nextItemKey = items.value.length;
const itemKeys = ref<number[]>(items.value.map((_, index) => index));

// Live CGST/SGST/UTGST/IGST split for the totals panel. Empty for non-GST
// supplies, in which case the single aggregate tax line is shown instead.
const gstBreakdown = computed(() =>
    buildGstBreakdown({
        items: items.value,
        supplierStateCode: props.supplierStateCode,
        placeOfSupplyCode: form.place_of_supply || null,
        isForeignCustomer: props.isForeignCustomer,
    }),
);

// TDS/TCS (India source taxes): TDS is deducted from the amount payable, TCS is
// added on top.
const tdsAmount = computed(() =>
    calcTdsAmount(totals.value.subtotal, form.tds_rate ?? 0),
);

const tcsAmount = computed(() =>
    calcTcsAmount(totals.value.subtotal, totals.value.tax, form.tcs_rate ?? 0),
);

const netTotal = computed(
    () => totals.value.total + tcsAmount.value - tdsAmount.value,
);

function sourceTaxLabel(
    type: 'TDS' | 'TCS',
    rate: number | null,
    section: string,
): string {
    const pct = parseFloat(((rate ?? 0) / 100).toPrecision(6));
    const base = `${type} @ ${pct}%`;

    return section ? `${base} (${section})` : base;
}

const tdsLabel = computed(() =>
    sourceTaxLabel('TDS', form.tds_rate, form.tds_section),
);
const tcsLabel = computed(() =>
    sourceTaxLabel('TCS', form.tcs_rate, form.tcs_section),
);

// The list is replaced rather than changed in place below: it belongs to the
// parent, and assigning the model is what hands the new list back to it.
async function addItem() {
    items.value = [
        ...items.value,
        {
            description: '',
            sac_code: null,
            quantity: 1,
            unit_price: 0,
            tax_rate: 0,
        },
    ];
    itemKeys.value.push(nextItemKey++);

    // Straight into the new line. It lands under the last one -- on a phone
    // a long scroll from the header's button -- and focusing it brings it into
    // view and opens the keyboard in one step, where before nothing moved.
    await nextTick();
    document
        .getElementById(`item-${items.value.length - 1}-description`)
        ?.focus();
}

const confirmingItemRemove = ref(false);
const itemRemoveIndex = ref<number | null>(null);

// Ask before discarding a line item the user has actually filled in. Empty
// rows carry no work, so remove those immediately to keep the form snappy.
function requestRemoveItem(index: number) {
    if (items.value.length <= 1) {
        return;
    }

    const item = items.value[index];
    const hasContent =
        !!item.description?.trim() ||
        !!item.sac_code?.trim() ||
        item.unit_price > 0;

    if (!hasContent) {
        removeItem(index);

        return;
    }

    itemRemoveIndex.value = index;
    confirmingItemRemove.value = true;
}

function confirmRemoveItem() {
    if (itemRemoveIndex.value !== null) {
        removeItem(itemRemoveIndex.value);
    }

    confirmingItemRemove.value = false;
    itemRemoveIndex.value = null;
}

function removeItem(index: number) {
    if (items.value.length > 1) {
        items.value = items.value.filter((_, i) => i !== index);
        itemKeys.value.splice(index, 1);
    }
}

function updateItem(index: number, item: LineItem) {
    items.value = items.value.map((existing, i) =>
        i === index ? item : existing,
    );
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Line Items</CardTitle>
            <CardAction>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="addItem"
                >
                    <Plus />
                    Add Item
                </Button>
            </CardAction>
        </CardHeader>
        <!--
            `px-0 py-0` so the table meets the card edge, the way the
            detail page's items table does. Inset by the card's own
            padding it sat a few pixels in from every other table in
            the app, with its header stripe floating rather than
            spanning.
        -->
        <CardContent class="px-0 py-0">
            <div class="overflow-x-auto">
                <!--
                    Block below 1024px, where each row is a card that
                    labels its own fields (see ItemRow), so the
                    headings would only label columns that are gone.
                -->
                <table class="min-w-full divide-y divide-border max-lg:block">
                    <thead class="bg-muted/60 max-lg:hidden">
                        <tr>
                            <th
                                class="px-2 py-2 text-left text-xs font-medium text-muted-foreground"
                            >
                                Description *
                            </th>
                            <th
                                class="w-20 py-2 pr-5 pl-2 text-right text-xs font-medium text-muted-foreground"
                            >
                                Qty *
                            </th>
                            <th
                                class="w-32 py-2 pr-5 pl-2 text-right text-xs font-medium text-muted-foreground"
                            >
                                Unit Price
                            </th>
                            <th
                                class="w-40 px-2 py-2 text-left text-xs font-medium text-muted-foreground"
                            >
                                Tax Rate
                            </th>
                            <th
                                class="w-32 px-2 py-2 text-right text-xs font-medium text-muted-foreground"
                            >
                                Amount
                            </th>
                            <!--
                                No heading. The column holds one
                                control that is invisible until the row
                                is pointed at, so a permanent "Action"
                                label announced a column that usually
                                shows nothing. The name stays for a
                                screen reader, which has no hover.
                            -->
                            <th class="w-10 px-2 py-2">
                                <span class="sr-only">Remove</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card max-lg:block">
                        <ItemRow
                            v-for="(item, index) in items"
                            :key="itemKeys[index]"
                            :item="item"
                            :index="index"
                            :currency="currency"
                            :tax-options="taxOptions"
                            :can-remove="items.length > 1"
                            :errors="form.errors"
                            :ai-suggest="
                                page.props.auth?.currentTeam?.ai === true
                            "
                            @update="updateItem(index, $event)"
                            @remove="requestRemoveItem(index)"
                        />
                    </tbody>
                </table>
            </div>

            <!--
                Totals, and the second way to add a line. The only
                other one is in the card header, which on a document
                with a dozen items is a scroll away from the row you
                just finished typing -- so it shares the strip the
                totals already occupy and costs no extra height.
            -->
            <div
                class="flex flex-col gap-4 border-t border-border px-5 py-4 md:flex-row md:items-start md:justify-between"
            >
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="addItem"
                >
                    <Plus />
                    Add Item
                </Button>

                <div class="w-full space-y-2 md:w-1/3">
                    <div class="flex justify-between text-sm">
                        <span class="text-soft-foreground">Subtotal:</span>
                        <span class="font-medium text-foreground">{{
                            formatAmount(totals.subtotal, currency)
                        }}</span>
                    </div>
                    <template v-if="gstBreakdown.length > 0">
                        <div
                            v-for="row in gstBreakdown"
                            :key="row.label"
                            class="flex justify-between text-sm"
                        >
                            <span class="text-soft-foreground"
                                >{{ row.label }}:</span
                            >
                            <span class="font-medium text-foreground">{{
                                formatAmount(row.amount, currency)
                            }}</span>
                        </div>
                    </template>
                    <div v-else class="flex justify-between text-sm">
                        <span class="text-soft-foreground">Tax:</span>
                        <span class="font-medium text-foreground">{{
                            formatAmount(totals.tax, currency)
                        }}</span>
                    </div>
                    <div
                        v-if="tcsAmount > 0"
                        class="flex justify-between text-sm"
                    >
                        <span class="text-soft-foreground"
                            >{{ tcsLabel }}:</span
                        >
                        <span class="font-medium text-foreground"
                            >+ {{ formatAmount(tcsAmount, currency) }}</span
                        >
                    </div>
                    <div
                        v-if="tdsAmount > 0"
                        class="flex justify-between text-sm"
                    >
                        <span class="text-soft-foreground"
                            >{{ tdsLabel }}:</span
                        >
                        <span class="font-medium text-foreground"
                            >&minus;
                            {{ formatAmount(tdsAmount, currency) }}</span
                        >
                    </div>
                    <div
                        class="flex justify-between border-t border-input pt-2"
                    >
                        <!--
                            The one place the currency is named. Every
                            figure above shares it, and repeating it on
                            each line stops a column of numbers lining
                            up on its decimal point.
                        -->
                        <span class="text-lg font-bold text-foreground">
                            Total ({{ currencySymbol(currency) }})
                        </span>
                        <span class="text-lg font-bold text-primary">{{
                            formatAmount(netTotal, currency)
                        }}</span>
                    </div>
                </div>
            </div>
        </CardContent>

        <!--
            Inside the card so the component keeps a single root. The dialog
            teleports to <body> when it opens, so where it sits here changes
            nothing on screen.
        -->
        <ConfirmationModal
            :show="confirmingItemRemove"
            title="Remove Line Item"
            message="Are you sure you want to remove this line item?"
            confirm-label="Remove"
            :destructive="true"
            @confirm="confirmRemoveItem"
            @cancel="
                confirmingItemRemove = false;
                itemRemoveIndex = null;
            "
        />
    </Card>
</template>
