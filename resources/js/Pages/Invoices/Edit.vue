<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import InvoiceForm from '@/Components/Invoice/InvoiceForm.vue';
import PaymentModal from '@/Components/Invoice/PaymentModal.vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import { formatDate } from '@/composables/useFormatDate';
import { formatMoney } from '@/composables/useFormatMoney';
import AppLayout from '@/Layouts/AppLayout.vue';
import type {
    Invoice,
    Customer,
    Location,
    TaxRateOption,
    InvoiceNumberingSeries,
    InvoiceAttachment,
} from '@/types';

const props = defineProps<{
    invoice: Invoice;
    customers: Customer[];
    organizationLocations: Location[];
    taxOptions: TaxRateOption[];
    numberingSeries: InvoiceNumberingSeries[];
    statusOptions: Record<string, string>;
    taxLabels: Record<string, string>;
    attachments: InvoiceAttachment[];
    indianStates: Record<string, string>;
}>();

const showPaymentModal = ref(false);

const confirmingPaymentDelete = ref(false);
const paymentToDelete = ref<number | null>(null);

const balanceDue = computed(() =>
    Math.max(0, (props.invoice.total ?? 0) - (props.invoice.amount_paid ?? 0)),
);

function fmt(amount: number): string {
    return formatMoney(amount, props.invoice.currency);
}

function confirmDeletePayment(paymentId: number) {
    paymentToDelete.value = paymentId;
    confirmingPaymentDelete.value = true;
}

function deletePayment() {
    if (paymentToDelete.value === null) {
        return;
    }

    router.delete(
        `/invoices/${props.invoice.id}/payments/${paymentToDelete.value}`,
        {
            preserveScroll: true,
            onFinish: () => {
                paymentToDelete.value = null;
            },
        },
    );

    confirmingPaymentDelete.value = false;
}
</script>

<template>
    <AppLayout
        :title="`Edit ${invoice.type === 'estimate' ? 'Estimate' : 'Invoice'}`"
    >
        <template #header>
            <Breadcrumb>
                <BreadcrumbItem>
                    <BreadcrumbLink href="/invoices"> Invoices </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                    <BreadcrumbPage>
                        Edit
                        {{
                            invoice.type === 'estimate' ? 'Estimate' : 'Invoice'
                        }}
                    </BreadcrumbPage>
                </BreadcrumbItem>
            </Breadcrumb>
        </template>

        <div class="py-4">
            <!--
                Payment history sits above the form. It used to follow it,
                which put the Cancel / Save / Save & Send bar in the middle
                of the page with another card beneath it -- the form
                appeared to end, and then did not.

                It reads better here regardless: what has been paid is
                context for the edit, not a footnote to it.
            -->
            <!-- Payment Section (invoices only, not estimates) -->
            <div
                v-if="invoice.type === 'invoice'"
                class="mt-8 rounded-lg border border-gray-200 bg-white p-6"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Payments
                    </h3>
                    <button
                        v-if="(invoice.amount_paid ?? 0) < (invoice.total ?? 0)"
                        type="button"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                        @click="showPaymentModal = true"
                    >
                        Record Payment
                    </button>
                </div>

                <!-- Payment summary -->
                <div
                    class="mb-4 grid grid-cols-3 gap-4 rounded-md bg-gray-50 p-4"
                >
                    <div>
                        <div class="text-xs text-gray-500">Total</div>
                        <div class="text-sm font-semibold">
                            {{ fmt(invoice.total ?? 0) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Paid</div>
                        <div class="text-sm font-semibold text-green-600">
                            {{ fmt(invoice.amount_paid ?? 0) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Balance Due</div>
                        <div
                            class="text-sm font-semibold"
                            :class="
                                (invoice.amount_paid ?? 0) >=
                                (invoice.total ?? 0)
                                    ? 'text-green-600'
                                    : 'text-red-600'
                            "
                        >
                            {{ fmt(balanceDue) }}
                        </div>
                    </div>
                </div>

                <!-- Payment history -->
                <div
                    v-if="invoice.payments && invoice.payments.length > 0"
                    class="overflow-hidden rounded-md border border-gray-200"
                >
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Method
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Reference
                                </th>
                                <th
                                    class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase"
                                >
                                    Amount
                                </th>
                                <th
                                    class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="payment in invoice.payments"
                                :key="payment.id"
                            >
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ formatDate(payment.payment_date) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{
                                        payment.payment_method
                                            ?.replace('_', ' ')
                                            ?.replace(/\b\w/g, (c: string) =>
                                                c.toUpperCase(),
                                            ) ?? '—'
                                    }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ payment.reference ?? '—' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-sm font-medium text-green-600"
                                >
                                    {{ fmt(payment.amount) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        class="cursor-pointer text-xs text-red-600 hover:text-red-800"
                                        @click="
                                            confirmDeletePayment(payment.id)
                                        "
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p v-else class="text-center text-sm text-gray-400">
                    No payments recorded yet.
                </p>
            </div>

            <InvoiceForm
                mode="edit"
                :type="invoice.type"
                :invoice="invoice"
                :customers="customers"
                :organization-locations="organizationLocations"
                :tax-options="taxOptions"
                :numbering-series="numberingSeries"
                :status-options="statusOptions"
                :tax-labels="taxLabels"
                :attachments="attachments"
                :indian-states="indianStates"
            />
        </div>

        <!-- Payment Modal -->
        <PaymentModal
            :show="showPaymentModal"
            :invoice="invoice"
            @close="showPaymentModal = false"
        />

        <!-- Delete Payment Confirmation -->
        <ConfirmationModal
            :show="confirmingPaymentDelete"
            title="Delete Payment"
            message="Are you sure you want to delete this payment? This action cannot be undone."
            confirm-label="Delete"
            :destructive="true"
            @confirm="deletePayment"
            @cancel="
                confirmingPaymentDelete = false;
                paymentToDelete = null;
            "
        />
    </AppLayout>
</template>
