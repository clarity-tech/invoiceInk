<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Plus } from 'lucide-vue-next';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DocumentOpenActions from '@/Components/Invoice/DocumentOpenActions.vue';
import InvoiceForm from '@/Components/Invoice/InvoiceForm.vue';
import PaymentModal from '@/Components/Invoice/PaymentModal.vue';
import MoneyDisplay from '@/Components/MoneyDisplay.vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { formatDate } from '@/composables/useFormatDate';
import AppLayout from '@/Layouts/AppLayout.vue';
import type {
    Invoice,
    Customer,
    Location,
    Payment,
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

function methodOf(payment: Payment): string {
    return (
        payment.payment_method
            ?.replace('_', ' ')
            ?.replace(/\b\w/g, (c: string) => c.toUpperCase()) ?? '—'
    );
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
                <!--
                    The document itself is now a page, so the trail goes back to
                    it rather than jumping straight to the list. The final crumb
                    keeps its full "Edit Invoice" wording: it is the sentinel
                    several browser tests use to confirm this page loaded.
                -->
                <BreadcrumbItem>
                    <BreadcrumbLink :href="`/invoices/${invoice.id}`">
                        {{ invoice.invoice_number }}
                    </BreadcrumbLink>
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

            <!--
                The document was identified only as "Edit Invoice". On a page
                carrying a total, a payment history and a balance due, the
                number appeared nowhere on screen -- so nothing said which
                invoice was open, or what state it was in.

                View public and Download PDF used to float in a bare row above
                the first card, belonging to no section. They open the document
                as it stands rather than changing it, so they belong up here
                with the rest of the page's identity -- as the same component
                the detail page uses, so the pair cannot drift apart again.
            -->
            <div
                class="mt-1 flex min-h-10 flex-wrap items-center justify-between gap-x-4 gap-y-2"
            >
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ invoice.invoice_number }}
                    </h2>
                    <!--
                        The form teleports its Status field in here. A badge
                        sat here before, reporting a value the user had to
                        scroll down a card to change -- and reporting the saved
                        one, not the one they had just picked.
                    -->
                    <div id="document-status-control" />
                    <span
                        v-if="invoice.customer?.name"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{ invoice.customer.name }}
                    </span>
                </div>

                <DocumentOpenActions v-if="invoice.ulid" :invoice="invoice" />
            </div>
        </template>

        <!--
            One wrapper owns the gutter and the step between sections. The
            layout already supplies the vertical page padding; `px-4 sm:px-0`
            is the phone gutter the layout stops short of.
        -->
        <div class="space-y-5 px-4 sm:px-0">
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

            <!--
                Payment history is reference material about a document already
                saved, so it reads last rather than interrupting the form. The
                detail page orders it the same way.
            -->
            <div v-if="invoice.type === 'invoice'">
                <Card>
                    <CardHeader>
                        <CardTitle>Payments</CardTitle>
                        <CardAction>
                            <Button
                                v-if="balanceDue > 0"
                                variant="outline"
                                size="sm"
                                @click="showPaymentModal = true"
                            >
                                <Plus />
                                Record Payment
                            </Button>
                        </CardAction>
                    </CardHeader>

                    <CardContent class="px-0 py-0">
                        <dl
                            class="grid gap-px border-b border-border bg-border sm:grid-cols-3"
                        >
                            <div class="bg-card px-5 py-3">
                                <dt class="text-xs text-muted-foreground">
                                    Total
                                </dt>
                                <dd class="mt-0.5 text-sm font-semibold">
                                    <MoneyDisplay
                                        :amount="invoice.total ?? 0"
                                        :currency="invoice.currency"
                                    />
                                </dd>
                            </div>
                            <div class="bg-card px-5 py-3">
                                <dt class="text-xs text-muted-foreground">
                                    Paid
                                </dt>
                                <dd class="mt-0.5 text-sm font-semibold">
                                    <MoneyDisplay
                                        :amount="invoice.amount_paid ?? 0"
                                        :currency="invoice.currency"
                                    />
                                </dd>
                            </div>
                            <div class="bg-card px-5 py-3">
                                <dt class="text-xs text-muted-foreground">
                                    Balance Due
                                </dt>
                                <dd
                                    class="mt-0.5 text-sm font-semibold"
                                    :class="
                                        balanceDue > 0 ? 'text-destructive' : ''
                                    "
                                >
                                    <MoneyDisplay
                                        :amount="balanceDue"
                                        :currency="invoice.currency"
                                    />
                                </dd>
                            </div>
                        </dl>

                        <Table
                            v-if="invoice.payments && invoice.payments.length"
                        >
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Method</TableHead>
                                    <TableHead>Reference</TableHead>
                                    <TableHead class="text-right">
                                        Amount
                                    </TableHead>
                                    <TableHead class="w-0" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="payment in invoice.payments"
                                    :key="payment.id"
                                >
                                    <TableCell class="whitespace-nowrap">
                                        {{ formatDate(payment.payment_date) }}
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">
                                        {{ methodOf(payment) }}
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">
                                        {{ payment.reference ?? '—' }}
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-medium tabular-nums"
                                    >
                                        <MoneyDisplay
                                            :amount="payment.amount"
                                            :currency="invoice.currency"
                                        />
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-muted-foreground hover:text-destructive"
                                            @click="
                                                confirmDeletePayment(payment.id)
                                            "
                                        >
                                            Delete
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <p
                            v-else
                            class="px-5 py-6 text-center text-sm text-muted-foreground"
                        >
                            No payments recorded yet.
                        </p>
                    </CardContent>
                </Card>
            </div>
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
