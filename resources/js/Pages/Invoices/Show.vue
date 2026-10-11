<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Download,
    ExternalLink,
    FileCheck,
    FileText,
    Mail,
    Paperclip,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import EmailModal from '@/Components/Invoice/EmailModal.vue';
import PaymentModal from '@/Components/Invoice/PaymentModal.vue';
import MoneyDisplay from '@/Components/MoneyDisplay.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Badge } from '@/Components/ui/badge';
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
import { formatBytes } from '@/composables/useFileAttachments';
import { formatDate } from '@/composables/useFormatDate';
import { formatLocationLine } from '@/composables/useLocationFormat';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/Layouts/AppLayout.vue';
import type {
    EmailDelivery,
    Invoice,
    InvoiceAttachment,
    Location,
} from '@/types';

const props = defineProps<{
    invoice: Invoice;
    attachments: InvoiceAttachment[];
    hasShippingAddress: boolean;
    paymentTermsLabel: string | null;
    placeOfSupplyLabel: string | null;
    tdsLabel: string | null;
    tcsLabel: string | null;
    taxLabels: Record<string, string>;
    countryNames: Record<string, string>;
}>();

const { can } = usePermissions();

/**
 * Viewing a document needs only `read`, so a read-only member reaches this page
 * and must not be offered actions the server will refuse. Edit, Send, Convert
 * and recording a payment all authorize `update`; deleting a payment
 * authorizes `delete`. The PDF needs only `read`, so it is never gated here.
 */
const canUpdate = computed(() => can('update'));
const canDelete = computed(() => can('delete'));

const isEstimate = computed(() => props.invoice.type === 'estimate');
const documentLabel = computed(() =>
    isEstimate.value ? 'Estimate' : 'Invoice',
);

/**
 * Server-computed, like the listing's. Deriving it here from total - paid would
 * be a second implementation of the same rule, free to disagree with the one
 * the status is actually recalculated from.
 */
const balanceDue = computed(() => props.invoice.remaining_balance ?? 0);
const amountPaid = computed(() => props.invoice.amount_paid ?? 0);

const canRecordPayment = computed(
    () => !isEstimate.value && balanceDue.value > 0 && canUpdate.value,
);

const items = computed(() => props.invoice.items ?? []);
const payments = computed(() => props.invoice.payments ?? []);
const deliveries = computed(() => props.invoice.email_deliveries ?? []);
const taxBreakdown = computed(() => props.invoice.tax_breakdown ?? []);

/**
 * EmailModal looks the customer up by id out of a list, so the one customer
 * this document belongs to is the whole list it needs.
 */
const customersForEmail = computed(() =>
    props.invoice.customer ? [props.invoice.customer] : [],
);

const showPaymentModal = ref(false);
const showEmailModal = ref(false);
/**
 * Deleting is the one action on this page that destroys the page. The server
 * redirects to the list rather than back here, because the document 404s the
 * moment it is soft-deleted.
 */
const confirmingDelete = ref(false);

function deleteDocument() {
    confirmingDelete.value = false;
    router.delete(`/invoices/${props.invoice.id}`);
}

/** The customer's view of this document; no authentication, shareable. */
const publicUrl = computed(
    () =>
        `/${props.invoice.type === 'invoice' ? 'invoices' : 'estimates'}/view/${props.invoice.ulid}`,
);

const confirmingPaymentDelete = ref(false);
const paymentToDelete = ref<number | null>(null);
const converting = ref(false);

/**
 * An estimate's whole purpose is to become an invoice, so that — not recording
 * a payment it cannot have — is the primary action on one.
 */
function convertEstimate() {
    converting.value = true;
    router.post(
        `/invoices/${props.invoice.id}/convert`,
        {},
        {
            onFinish: () => {
                converting.value = false;
            },
        },
    );
}

function addressOf(location: Location | undefined): string {
    return location ? formatLocationLine(location, props.countryNames) : '';
}

function taxRateOf(rateBasisPoints: number): string {
    return `${rateBasisPoints / 100}%`;
}

function methodOf(payment: { payment_method: string | null }): string {
    if (!payment.payment_method) {
        return '—';
    }

    return payment.payment_method
        .replace('_', ' ')
        .replace(/\b\w/g, (c: string) => c.toUpperCase());
}

/**
 * Every attempt gets a badge here, unlike the listing, which stays quiet about
 * a delivered send. This card exists to answer "did it ever reach them", so on
 * it a success is the answer rather than noise.
 */
function deliveryTone(delivery: EmailDelivery): {
    variant: 'muted' | 'destructive' | 'success';
    label: string;
} {
    if (delivery.status === 'failed') {
        return { variant: 'destructive', label: 'Failed' };
    }

    if (delivery.status === 'sent') {
        return { variant: 'success', label: 'Sent' };
    }

    return { variant: 'muted', label: 'Sending…' };
}

function deliveryTime(delivery: EmailDelivery): string | null {
    return delivery.sent_at ?? delivery.failed_at ?? delivery.queued_at ?? null;
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
    <AppLayout :title="`${documentLabel} ${invoice.invoice_number}`">
        <template #header>
            <Breadcrumb>
                <BreadcrumbItem>
                    <BreadcrumbLink href="/invoices"> Invoices </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                    <BreadcrumbPage>{{
                        invoice.invoice_number
                    }}</BreadcrumbPage>
                </BreadcrumbItem>
            </Breadcrumb>

            <div
                class="mt-1 flex flex-wrap items-center justify-between gap-x-4 gap-y-2"
            >
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h1 class="text-lg font-semibold text-gray-900">
                        {{ invoice.invoice_number }}
                    </h1>
                    <StatusBadge :status="invoice.status" />
                    <!--
                        Overdue is derived, not a status, so without this the
                        page could show "Sent" on a document months late.
                    -->
                    <Badge v-if="invoice.is_overdue" variant="destructive">
                        {{
                            invoice.days_overdue === 1
                                ? 'Overdue by 1 day'
                                : `Overdue by ${invoice.days_overdue} days`
                        }}
                    </Badge>
                    <span
                        v-if="invoice.customer?.name"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{ invoice.customer.name }}
                    </span>
                </div>

                <!--
                    One primary action only. Everything else is an outline or a
                    ghost, so the page has a single obvious next step rather
                    than six buttons competing for it.
                -->
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        v-if="isEstimate && canUpdate"
                        size="sm"
                        :disabled="converting"
                        :aria-busy="converting"
                        @click="convertEstimate"
                    >
                        <FileCheck />
                        {{ converting ? 'Converting…' : 'Convert to invoice' }}
                    </Button>
                    <Button
                        v-if="canRecordPayment"
                        size="sm"
                        @click="showPaymentModal = true"
                    >
                        <Plus />
                        Record payment
                    </Button>
                    <Button
                        v-if="canUpdate"
                        variant="outline"
                        size="sm"
                        @click="showEmailModal = true"
                    >
                        <Mail />
                        Send
                    </Button>
                    <Button
                        as="a"
                        variant="outline"
                        size="sm"
                        :href="`/invoices/${invoice.id}/download`"
                    >
                        <Download />
                        PDF
                    </Button>
                    <Button
                        v-if="canUpdate"
                        :as="Link"
                        variant="outline"
                        size="sm"
                        :href="`/invoices/${invoice.id}/edit`"
                    >
                        <Pencil />
                        Edit
                    </Button>
                    <!--
                        Destructive, rarely wanted, and the only control here
                        that ends the page -- so it is an unlabelled icon that
                        stays grey until hovered, rather than a sixth button
                        competing for the eye. The label names the document:
                        the payment rows below carry "Delete" buttons too.
                    -->
                    <Button
                        v-if="canDelete"
                        variant="ghost"
                        size="icon"
                        class="text-muted-foreground hover:text-destructive"
                        :aria-label="`Delete ${invoice.type} ${invoice.invoice_number}`"
                        @click="confirmingDelete = true"
                    >
                        <Trash2 />
                    </Button>
                </div>
            </div>
        </template>

        <div class="space-y-5 py-4">
            <!--
                The three figures the page exists to answer, before any detail.
                An estimate has no payments, so it carries its total alone
                rather than a Paid and Balance of zero.
            -->
            <Card>
                <CardContent
                    class="grid gap-px bg-border p-0 sm:grid-cols-2"
                    :class="isEstimate ? 'lg:grid-cols-2' : 'lg:grid-cols-4'"
                >
                    <div class="bg-card px-5 py-4">
                        <div class="text-xs text-muted-foreground">Total</div>
                        <div class="mt-1 text-xl font-semibold tabular-nums">
                            <MoneyDisplay
                                :amount="invoice.total"
                                :currency="invoice.currency"
                            />
                        </div>
                    </div>
                    <template v-if="!isEstimate">
                        <div class="bg-card px-5 py-4">
                            <div class="text-xs text-muted-foreground">
                                Paid
                            </div>
                            <div
                                class="mt-1 text-xl font-semibold tabular-nums"
                            >
                                <MoneyDisplay
                                    :amount="amountPaid"
                                    :currency="invoice.currency"
                                />
                            </div>
                        </div>
                        <div class="bg-card px-5 py-4">
                            <div class="text-xs text-muted-foreground">
                                Balance due
                            </div>
                            <div
                                class="mt-1 text-xl font-semibold tabular-nums"
                                :class="
                                    balanceDue > 0
                                        ? 'text-foreground'
                                        : 'text-muted-foreground'
                                "
                            >
                                <MoneyDisplay
                                    :amount="balanceDue"
                                    :currency="invoice.currency"
                                />
                            </div>
                        </div>
                    </template>
                    <div class="bg-card px-5 py-4">
                        <div class="text-xs text-muted-foreground">
                            {{ isEstimate ? 'Valid until' : 'Due' }}
                        </div>
                        <div class="mt-1 text-xl font-semibold">
                            {{ formatDate(invoice.due_at) }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!--
                min-w-0 on both columns is load-bearing, not tidying: a grid
                item defaults to min-width:auto, so it refuses to shrink below
                its content. The items and payments tables are wider than a
                phone, so without it the column grows past the viewport and
                drags the whole page sideways -- the table's own scroll
                container never gets the chance to do its job.
            -->
            <div class="grid gap-5 lg:grid-cols-3">
                <div class="min-w-0 space-y-5 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Items</CardTitle>
                        </CardHeader>
                        <CardContent class="px-0 py-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Description</TableHead>
                                        <TableHead class="text-right">
                                            Qty
                                        </TableHead>
                                        <TableHead class="text-right">
                                            Rate
                                        </TableHead>
                                        <TableHead class="text-right">
                                            Tax
                                        </TableHead>
                                        <TableHead class="text-right">
                                            Amount
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="item in items"
                                        :key="item.id"
                                    >
                                        <TableCell>
                                            <div class="font-medium">
                                                {{ item.description }}
                                            </div>
                                            <div
                                                v-if="item.sac_code"
                                                class="mt-0.5 text-xs text-muted-foreground"
                                            >
                                                SAC {{ item.sac_code }}
                                            </div>
                                        </TableCell>
                                        <TableCell
                                            class="text-right tabular-nums"
                                        >
                                            {{ item.quantity }}
                                        </TableCell>
                                        <TableCell
                                            class="text-right tabular-nums"
                                        >
                                            <MoneyDisplay
                                                :amount="item.unit_price"
                                                :currency="invoice.currency"
                                            />
                                        </TableCell>
                                        <TableCell
                                            class="text-right text-muted-foreground tabular-nums"
                                        >
                                            {{ taxRateOf(item.tax_rate) }}
                                        </TableCell>
                                        <TableCell
                                            class="text-right font-medium tabular-nums"
                                        >
                                            <MoneyDisplay
                                                :amount="item.line_total ?? 0"
                                                :currency="invoice.currency"
                                            />
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <!--
                                Right-aligned and narrow so the figures line up
                                under the Amount column they total.
                            -->
                            <dl
                                class="ml-auto w-full max-w-xs space-y-1.5 border-t border-border px-5 py-4 text-sm"
                            >
                                <div class="flex justify-between gap-4">
                                    <dt class="text-muted-foreground">
                                        Subtotal
                                    </dt>
                                    <dd class="tabular-nums">
                                        <MoneyDisplay
                                            :amount="invoice.subtotal"
                                            :currency="invoice.currency"
                                        />
                                    </dd>
                                </div>
                                <div
                                    v-for="tax in taxBreakdown"
                                    :key="tax.name"
                                    class="flex justify-between gap-4"
                                >
                                    <dt class="text-muted-foreground">
                                        {{ tax.name }}
                                    </dt>
                                    <dd class="tabular-nums">
                                        <MoneyDisplay
                                            :amount="tax.amount"
                                            :currency="invoice.currency"
                                        />
                                    </dd>
                                </div>
                                <div
                                    v-if="!taxBreakdown.length && invoice.tax"
                                    class="flex justify-between gap-4"
                                >
                                    <dt class="text-muted-foreground">Tax</dt>
                                    <dd class="tabular-nums">
                                        <MoneyDisplay
                                            :amount="invoice.tax"
                                            :currency="invoice.currency"
                                        />
                                    </dd>
                                </div>
                                <div
                                    v-if="tdsLabel"
                                    class="flex justify-between gap-4"
                                >
                                    <dt class="text-muted-foreground">
                                        {{ tdsLabel }}
                                    </dt>
                                    <dd class="tabular-nums">
                                        −<MoneyDisplay
                                            :amount="invoice.tds_amount"
                                            :currency="invoice.currency"
                                        />
                                    </dd>
                                </div>
                                <div
                                    v-if="tcsLabel"
                                    class="flex justify-between gap-4"
                                >
                                    <dt class="text-muted-foreground">
                                        {{ tcsLabel }}
                                    </dt>
                                    <dd class="tabular-nums">
                                        <MoneyDisplay
                                            :amount="invoice.tcs_amount"
                                            :currency="invoice.currency"
                                        />
                                    </dd>
                                </div>
                                <div
                                    class="flex justify-between gap-4 border-t border-border pt-1.5 font-semibold"
                                >
                                    <dt>Total</dt>
                                    <dd class="tabular-nums">
                                        <MoneyDisplay
                                            :amount="invoice.total"
                                            :currency="invoice.currency"
                                        />
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card v-if="invoice.notes || invoice.terms">
                        <CardHeader>
                            <CardTitle>Notes &amp; terms</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 text-sm">
                            <div v-if="invoice.notes">
                                <div
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Notes
                                </div>
                                <p class="mt-1 whitespace-pre-line">
                                    {{ invoice.notes }}
                                </p>
                            </div>
                            <div v-if="invoice.terms">
                                <div
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Terms
                                </div>
                                <p class="mt-1 whitespace-pre-line">
                                    {{ invoice.terms }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="min-w-0 space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs text-muted-foreground">
                                        Issued
                                    </dt>
                                    <dd>{{ formatDate(invoice.issued_at) }}</dd>
                                </div>
                                <div v-if="paymentTermsLabel">
                                    <dt class="text-xs text-muted-foreground">
                                        Payment terms
                                    </dt>
                                    <dd>{{ paymentTermsLabel }}</dd>
                                </div>
                                <div v-if="invoice.subject">
                                    <dt class="text-xs text-muted-foreground">
                                        Subject
                                    </dt>
                                    <dd>{{ invoice.subject }}</dd>
                                </div>
                                <div v-if="invoice.order_number">
                                    <dt class="text-xs text-muted-foreground">
                                        Order number
                                    </dt>
                                    <dd>{{ invoice.order_number }}</dd>
                                </div>
                                <div v-if="placeOfSupplyLabel">
                                    <dt class="text-xs text-muted-foreground">
                                        Place of supply
                                    </dt>
                                    <dd>{{ placeOfSupplyLabel }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">
                                        Currency
                                    </dt>
                                    <dd>{{ invoice.currency }}</dd>
                                </div>
                                <!--
                                    The customer's own view of this document.
                                    It used to be reachable only from a "View"
                                    link in the list, which said nothing about
                                    which of the two views it opened.
                                -->
                                <div>
                                    <dt class="text-xs text-muted-foreground">
                                        Public link
                                    </dt>
                                    <dd>
                                        <a
                                            :href="publicUrl"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-1 font-medium text-brand-600 hover:text-brand-800 hover:underline"
                                        >
                                            View public
                                            <ExternalLink class="size-3.5" />
                                        </a>
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Addresses</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs text-muted-foreground">
                                        Billed to
                                    </dt>
                                    <dd class="font-medium">
                                        <Link
                                            v-if="invoice.customer"
                                            :href="`/customers/${invoice.customer.id}`"
                                            class="text-primary hover:underline"
                                        >
                                            {{ invoice.customer.name }}
                                        </Link>
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                        >
                                            No customer
                                        </span>
                                    </dd>
                                    <dd
                                        v-if="
                                            addressOf(invoice.customer_location)
                                        "
                                        class="mt-0.5 text-muted-foreground"
                                    >
                                        {{
                                            addressOf(invoice.customer_location)
                                        }}
                                    </dd>
                                </div>
                                <div
                                    v-if="
                                        hasShippingAddress &&
                                        addressOf(
                                            invoice.customer_shipping_location,
                                        )
                                    "
                                >
                                    <dt class="text-xs text-muted-foreground">
                                        Shipped to
                                    </dt>
                                    <dd class="text-muted-foreground">
                                        {{
                                            addressOf(
                                                invoice.customer_shipping_location,
                                            )
                                        }}
                                    </dd>
                                </div>
                                <div
                                    v-if="
                                        addressOf(invoice.organization_location)
                                    "
                                >
                                    <dt class="text-xs text-muted-foreground">
                                        Issued from
                                    </dt>
                                    <dd class="text-muted-foreground">
                                        {{
                                            addressOf(
                                                invoice.organization_location,
                                            )
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Email history</CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm">
                            <ul v-if="deliveries.length" class="space-y-3">
                                <li
                                    v-for="delivery in deliveries"
                                    :key="delivery.id"
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="truncate text-muted-foreground"
                                            :title="
                                                delivery.recipients.join(', ')
                                            "
                                        >
                                            {{ delivery.recipients.join(', ') }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                formatDate(
                                                    deliveryTime(delivery),
                                                )
                                            }}
                                        </div>
                                        <p
                                            v-if="delivery.error"
                                            class="mt-1 text-xs text-destructive"
                                        >
                                            {{ delivery.error }}
                                        </p>
                                    </div>
                                    <Badge
                                        :variant="
                                            deliveryTone(delivery).variant
                                        "
                                    >
                                        {{ deliveryTone(delivery).label }}
                                    </Badge>
                                </li>
                            </ul>
                            <div
                                v-else
                                class="flex items-center gap-2 text-muted-foreground"
                            >
                                <FileText class="size-4" />
                                Never sent.
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="attachments.length">
                        <CardHeader>
                            <CardTitle>Attachments</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-2 text-sm">
                                <li
                                    v-for="attachment in attachments"
                                    :key="attachment.id"
                                >
                                    <a
                                        :href="attachment.url"
                                        class="flex items-center gap-2 text-primary hover:underline"
                                    >
                                        <Paperclip class="size-4 shrink-0" />
                                        <span class="truncate">
                                            {{ attachment.name }}
                                        </span>
                                        <span
                                            class="shrink-0 text-xs text-muted-foreground"
                                        >
                                            {{ formatBytes(attachment.size) }}
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!--
                Payment history is a record of what has already happened to a
                document, not part of reading the document itself, so it sits
                full width below everything it refers to -- the same order the
                edit page settled on in eb500d47. Inside the grid it would have
                stacked above the right-hand rail on a phone, which is mid-page.
            -->
            <Card v-if="!isEstimate">
                <CardHeader>
                    <CardTitle>Payments</CardTitle>
                    <CardAction>
                        <Button
                            v-if="canRecordPayment"
                            variant="outline"
                            size="sm"
                            @click="showPaymentModal = true"
                        >
                            <Plus />
                            Record payment
                        </Button>
                    </CardAction>
                </CardHeader>
                <CardContent :class="payments.length ? 'px-0 py-0' : undefined">
                    <Table v-if="payments.length">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date</TableHead>
                                <TableHead>Method</TableHead>
                                <TableHead>Reference</TableHead>
                                <TableHead class="text-right">
                                    Amount
                                </TableHead>
                                <TableHead class="text-right">
                                    <span class="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="payment in payments"
                                :key="payment.id"
                            >
                                <TableCell>
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
                                        v-if="canDelete"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:bg-destructive/10 hover:text-destructive"
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
                    <p v-else class="text-sm text-muted-foreground">
                        No payments recorded yet.
                    </p>
                </CardContent>
            </Card>
        </div>

        <PaymentModal
            :show="showPaymentModal"
            :invoice="invoice"
            @close="showPaymentModal = false"
        />

        <EmailModal
            :show="showEmailModal"
            :invoice="invoice"
            :customers="customersForEmail"
            :invoice-attachments="attachments"
            @close="showEmailModal = false"
        />

        <ConfirmationModal
            :show="confirmingDelete"
            title="Delete Document"
            message="Are you sure you want to delete this document? This action cannot be undone."
            confirm-label="Delete"
            :destructive="true"
            @confirm="deleteDocument"
            @cancel="confirmingDelete = false"
        />

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
