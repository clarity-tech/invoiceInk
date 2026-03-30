<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { formatMoney } from '@/composables/useFormatMoney';
import type { Invoice } from '@/types';

const props = defineProps<{
    show: boolean;
    invoice: Invoice;
}>();

const emit = defineEmits<{
    close: [];
}>();

const balanceDue = computed(() => {
    const total = props.invoice.total ?? 0;
    const paid = props.invoice.amount_paid ?? 0;

    return Math.max(0, total - paid);
});

const form = useForm({
    amount: 0,
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: '',
    reference: '',
    notes: '',
});

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.amount = balanceDue.value;
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
        class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-gray-900"
        @keydown.escape="emit('close')"
    >
        <div
            class="mx-4 w-full max-w-md overflow-hidden rounded-lg bg-white shadow-2xl"
            role="dialog"
            aria-modal="true"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-gray-200 px-6 py-4"
            >
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Record Payment
                    </h2>
                    <p class="text-sm text-gray-500">
                        {{ invoice.invoice_number }}
                    </p>
                </div>
                <button
                    class="text-gray-400 hover:text-gray-600"
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
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total</span>
                    <span class="font-medium">{{
                        fmt(invoice.total ?? 0)
                    }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Paid</span>
                    <span class="font-medium text-green-600">{{
                        fmt(invoice.amount_paid ?? 0)
                    }}</span>
                </div>
                <div
                    class="mt-1 flex justify-between border-t border-gray-200 pt-1 text-sm font-semibold"
                >
                    <span>Balance Due</span>
                    <span class="text-brand-600">{{ fmt(balanceDue) }}</span>
                </div>
            </div>

            <!-- Form -->
            <form class="px-6 py-4" @submit.prevent="submit">
                <!-- Amount (in cents, displayed as currency) -->
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Amount *</label
                    >
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400"
                            >{{ invoice.currency }}</span
                        >
                        <input
                            :value="form.amount / 100"
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="w-full rounded-md border border-gray-300 py-2 pr-3 pl-14 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            @input="
                                form.amount = Math.round(
                                    Number(
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    ) * 100,
                                )
                            "
                        />
                    </div>
                    <p
                        v-if="form.errors.amount"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ form.errors.amount }}
                    </p>
                </div>

                <!-- Payment Date -->
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Payment Date *</label
                    >
                    <input
                        v-model="form.payment_date"
                        type="date"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                    />
                    <p
                        v-if="form.errors.payment_date"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ form.errors.payment_date }}
                    </p>
                </div>

                <!-- Payment Method -->
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Payment Method</label
                    >
                    <select
                        v-model="form.payment_method"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
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
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Reference / Transaction ID</label
                    >
                    <input
                        v-model="form.reference"
                        type="text"
                        placeholder="e.g. TXN-12345"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                    />
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Notes</label
                    >
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        placeholder="Optional notes..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                    />
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button
                        type="button"
                        class="flex-1 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || form.amount <= 0"
                        class="flex-1 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
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
