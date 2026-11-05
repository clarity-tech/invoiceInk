<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { formatMoney } from '@/composables/useFormatMoney';
import type { Invoice, TdsPreset } from '@/types';

const props = defineProps<{
    show: boolean;
    invoice: Invoice;
    /** Offer the TDS field: an Indian customer, and no TDS on the invoice itself. */
    tdsApplies?: boolean;
    tdsPresets?: TdsPreset[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const balanceDue = computed(() => {
    const total = props.invoice.total ?? 0;
    const paid = props.invoice.amount_paid ?? 0;

    return Math.max(0, total - paid);
});

const sellsInIndia = computed(
    () => props.invoice.organization?.country_code === 'IN',
);

const showSubtotal = computed(
    () => (props.invoice.subtotal ?? 0) !== (props.invoice.total ?? 0),
);

const form = useForm({
    amount: 0,
    tds_amount: 0,
    tds_section: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: '',
    reference: '',
    notes: '',
});

/**
 * Until the amount is typed over, entering TDS takes it off the prefilled
 * balance, so the amount reads as what actually arrived in the bank.
 */
const amountEdited = ref(false);

const settled = computed(() => form.amount + form.tds_amount);

function toCents(event: Event): number {
    return Math.round(Number((event.target as HTMLInputElement).value) * 100);
}

function onAmountInput(event: Event) {
    amountEdited.value = true;
    form.amount = toCents(event);
}

function setTds(cents: number) {
    form.tds_amount = cents;

    if (cents <= 0) {
        form.tds_section = '';
    }

    if (!amountEdited.value) {
        form.amount = Math.max(0, balanceDue.value - cents);
    }
}

function onTdsInput(event: Event) {
    setTds(toCents(event));
}

const tdsAlreadyWithheld = computed(() =>
    (props.invoice.payments ?? []).reduce(
        (sum, payment) => sum + (payment.tds_amount ?? 0),
        0,
    ),
);

/**
 * TDS is on the subtotal, before GST, for the whole invoice. Less what earlier
 * payments already withheld, so recording it after the cash gives the rest.
 */
function presetAmount(preset: TdsPreset): number {
    const onInvoice = Math.round(
        ((props.invoice.subtotal ?? 0) * preset.rate) / 10000,
    );

    return Math.min(
        balanceDue.value,
        Math.max(0, onInvoice - tdsAlreadyWithheld.value),
    );
}

function applyPreset(preset: TdsPreset) {
    setTds(presetAmount(preset));
    form.tds_section = form.tds_amount > 0 ? preset.section : '';
}

function isPresetApplied(preset: TdsPreset): boolean {
    return (
        form.tds_amount > 0 &&
        form.tds_section === preset.section &&
        form.tds_amount === presetAmount(preset)
    );
}

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.amount = balanceDue.value;
            form.tds_amount = 0;
            form.tds_section = '';
            amountEdited.value = false;
            form.payment_date = new Date().toISOString().split('T')[0];
            form.payment_method = '';
            form.reference = '';
            form.notes = '';
            form.clearErrors();
        }
    },
);

function submit() {
    form.post(`/invoices/${props.invoice.id}/payments`, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}

function fmt(amount: number): string {
    return formatMoney(amount, props.invoice.currency);
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-overlay"
        @keydown.escape="emit('close')"
    >
        <div
            class="mx-4 w-full max-w-md overflow-hidden rounded-lg bg-card shadow-2xl"
            role="dialog"
            aria-modal="true"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-border px-6 py-4"
            >
                <div>
                    <h2 class="text-lg font-semibold text-foreground">
                        Record Payment
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ invoice.invoice_number }}
                    </p>
                </div>
                <button
                    class="text-faint-foreground hover:text-soft-foreground"
                    @click="emit('close')"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Balance summary -->
            <div class="border-b border-border bg-muted px-6 py-3">
                <div v-if="showSubtotal" class="flex justify-between text-sm">
                    <span class="text-muted-foreground">{{
                        sellsInIndia ? 'Subtotal (before GST)' : 'Subtotal'
                    }}</span>
                    <span class="font-medium">{{
                        fmt(invoice.subtotal ?? 0)
                    }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">Total</span>
                    <span class="font-medium">{{
                        fmt(invoice.total ?? 0)
                    }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">Paid</span>
                    <span class="font-medium text-success">{{
                        fmt(invoice.amount_paid ?? 0)
                    }}</span>
                </div>
                <div
                    class="mt-1 flex justify-between border-t border-border pt-1 text-sm font-semibold"
                >
                    <span>Balance Due</span>
                    <span class="text-primary">{{ fmt(balanceDue) }}</span>
                </div>
            </div>

            <!-- Form -->
            <form class="px-6 py-4" @submit.prevent="submit">
                <!-- Amount (in cents, displayed as currency) -->
                <div class="mb-4">
                    <label
                        class="mb-1 block text-sm font-medium text-soft-foreground"
                        >Amount *</label
                    >
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-faint-foreground"
                            >{{ invoice.currency }}</span
                        >
                        <input
                            :value="form.amount / 100"
                            type="number"
                            inputmode="decimal"
                            min="0"
                            step="0.01"
                            class="w-full rounded-md border border-input py-2 pr-3 pl-14 text-sm focus:ring-2 focus:ring-ring focus:outline-none pointer-coarse:text-base"
                            @input="onAmountInput"
                        />
                    </div>
                    <p
                        v-if="form.errors.amount"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.amount }}
                    </p>
                </div>

                <!-- TDS withheld by the customer -->
                <div v-if="tdsApplies" class="mb-4">
                    <label
                        for="payment-tds-amount"
                        class="mb-1 block text-sm font-medium text-soft-foreground"
                        >TDS deducted by customer</label
                    >
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-faint-foreground"
                            >{{ invoice.currency }}</span
                        >
                        <input
                            id="payment-tds-amount"
                            :value="
                                form.tds_amount ? form.tds_amount / 100 : ''
                            "
                            type="number"
                            inputmode="decimal"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full rounded-md border border-input py-2 pr-3 pl-14 text-sm focus:ring-2 focus:ring-ring focus:outline-none pointer-coarse:text-base"
                            @input="onTdsInput"
                        />
                    </div>
                    <div
                        v-if="tdsPresets?.length"
                        class="mt-2 flex flex-wrap gap-1.5"
                    >
                        <button
                            v-for="preset in tdsPresets"
                            :key="`${preset.section}-${preset.rate}-${preset.label}`"
                            type="button"
                            class="rounded-full border px-2.5 py-1 text-xs hover:bg-muted"
                            :class="
                                isPresetApplied(preset)
                                    ? 'border-primary text-primary'
                                    : 'border-input text-soft-foreground'
                            "
                            @click="applyPreset(preset)"
                        >
                            <span class="font-medium"
                                >{{ preset.rate / 100 }}%</span
                            >
                            {{ preset.section }}
                            {{ preset.label }}
                        </button>
                    </div>
                    <p
                        v-if="tdsPresets?.length"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        Worked out on the subtotal, before GST. Pick the rate on
                        the customer's TDS certificate.
                    </p>
                    <p
                        v-if="form.errors.tds_amount"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.tds_amount }}
                    </p>
                    <p v-else class="mt-1 text-xs text-muted-foreground">
                        <template v-if="form.tds_amount > 0">
                            Settles {{ fmt(settled) }} of the
                            {{ fmt(balanceDue) }} due.
                        </template>
                        <template v-else>
                            Paid short because they withheld TDS? Enter it here.
                            It counts toward the balance.
                        </template>
                    </p>
                </div>

                <!-- Payment Date -->
                <div class="mb-4">
                    <label
                        class="mb-1 block text-sm font-medium text-soft-foreground"
                        >Payment Date *</label
                    >
                    <input
                        v-model="form.payment_date"
                        type="date"
                        class="w-full rounded-md border border-input px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none pointer-coarse:text-base"
                    />
                    <p
                        v-if="form.errors.payment_date"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.payment_date }}
                    </p>
                </div>

                <!-- Payment Method -->
                <div class="mb-4">
                    <label
                        class="mb-1 block text-sm font-medium text-soft-foreground"
                        >Payment Method</label
                    >
                    <select
                        v-model="form.payment_method"
                        class="w-full rounded-md border border-input px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none pointer-coarse:text-base"
                    >
                        <option value="">Select method</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="upi">UPI</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="paypal">PayPal</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <!-- Reference -->
                <div class="mb-4">
                    <label
                        class="mb-1 block text-sm font-medium text-soft-foreground"
                        >Reference / Transaction ID</label
                    >
                    <input
                        v-model="form.reference"
                        type="text"
                        placeholder="e.g. TXN-12345"
                        class="w-full rounded-md border border-input px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none pointer-coarse:text-base"
                    />
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label
                        class="mb-1 block text-sm font-medium text-soft-foreground"
                        >Notes</label
                    >
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        placeholder="Optional notes..."
                        class="w-full rounded-md border border-input px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none pointer-coarse:text-base"
                    />
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button
                        type="button"
                        class="flex-1 rounded-md border border-input px-4 py-2 text-sm font-medium text-soft-foreground hover:bg-muted"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || settled <= 0"
                        class="flex-1 rounded-md bg-success px-4 py-2 text-sm font-medium text-success-foreground hover:bg-success/90 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            form.processing ? 'Recording...' : 'Record Payment'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
