<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { FileCheck, Paperclip, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DocumentOpenActions from '@/Components/Invoice/DocumentOpenActions.vue';
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
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { formatBytes } from '@/composables/useFileAttachments';
import { currencySymbol } from '@/composables/useFormatMoney';
import { formatDate } from '@/composables/useFormatDate';
import { formatLocationLine } from '@/composables/useLocationFormat';
import { overdueLabel } from '@/composables/useOverdueLabel';
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
    placeOfSupplyApplies: boolean;
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

/**
 * Whether money has actually moved against this document, which decides
 * whether Paid and Balance are worth a tile each. Keyed to the amount rather
 * than to the payment rows so a document paid by any other route still splits
 * its figures.
 */
const hasPayments = computed(() => !isEstimate.value && amountPaid.value > 0);

/**
 * The summary strip's tiles, in reading order: what it comes to, what has been
 * paid against it, then when it was raised, on what terms and when it is due.
 * Issued and the terms were rows in Details, which is where you go to look
 * something up -- the dates are what you read the document against, so they
 * belong with the figures.
 *
 * A tile is skipped rather than printed empty, so the count varies. That
 * matters more than it looks: the hairlines between tiles are a `bg-border`
 * grid showing through `gap-px`, so a column short of a full row is not a gap
 * but a grey block the size of a tile. Hence only counts that divide exactly,
 * and literal class strings -- Tailwind cannot see a computed one.
 */
const tileCount = computed(
    () =>
        1 +
        (hasPayments.value ? 2 : 0) +
        (props.invoice.issued_at ? 1 : 0) +
        (props.paymentTermsLabel ? 1 : 0) +
        (props.invoice.due_at ? 1 : 0),
);

const tileColumns = computed(
    () =>
        ({
            1: '',
            2: 'grid-cols-2',
            3: 'sm:grid-cols-3',
            4: 'grid-cols-2 lg:grid-cols-4',
            // One row of six only once there is room for it: below 1280px
            // six tiles squeeze a long total onto two lines. Three keeps the
            // money on one line and the dates on the next, which is how they
            // group anyway.
            6: 'grid-cols-2 lg:grid-cols-3 xl:grid-cols-6',
        })[tileCount.value] ?? 'sm:grid-cols-2',
);

const canRecordPayment = computed(
    () => !isEstimate.value && balanceDue.value > 0 && canUpdate.value,
);

const items = computed(() => props.invoice.items ?? []);
const payments = computed(() => props.invoice.payments ?? []);
const deliveries = computed(() => props.invoice.email_deliveries ?? []);
/**
 * The server stores the GST split as a map of label to amount in cents --
 * `{'CGST @ (9%)': 1800000}` -- which is what the PDF template iterates. A
 * `v-for` over that object would yield bare numbers, so it is normalised to a
 * list here; `.length` then also answers whether a split exists at all.
 */
const taxBreakdown = computed(() =>
    Object.entries(props.invoice.tax_breakdown ?? {}).map(([name, amount]) => ({
        name,
        amount,
    })),
);

const showPaymentModal = ref(false);
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

/**
 * The shipping address, when the document names one of its own. An address
 * equal to the billing one is not a second fact, so it reads as "same as
 * billing" rather than printing the same lines twice under two headings.
 */
const shippingAddressLine = computed(() =>
    props.hasShippingAddress
        ? addressOf(props.invoice.customer_shipping_location)
        : '',
);

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
                class="mt-1 flex min-h-10 flex-wrap items-center justify-between gap-x-4 gap-y-2"
            >
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h1 class="text-lg font-semibold text-gray-900">
                        {{ invoice.invoice_number }}
                    </h1>
                    <StatusBadge :status="invoice.status" />
                    <!--
                        Overdue is derived rather than a status, so it is said
                        separately -- but in the summary strip, under the due
                        date, not as a second badge here.
                    -->
                    <span
                        v-if="invoice.customer?.name"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{ invoice.customer.name }}
                    </span>
                </div>

                <!--
                    Sending is not here: it belongs with the document you are
                    about to send, and the editor's "Save & Send" sends what is
                    on screen rather than a copy saved some time earlier. This
                    row is what you do with the document as it stands.

                    One action leads, quietly. A filled accent button shouted
                    across a row of outlines and over the document it belongs
                    to; secondary keeps it first among equals without being the
                    loudest thing on the page.

                    The row is a fixed height on both this page and the edit
                    page, because the edit page's status control is taller than
                    a badge and the two otherwise shared a right edge at
                    different heights -- following Edit made every control jump.
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
                        variant="secondary"
                        size="sm"
                        @click="showPaymentModal = true"
                    >
                        <Plus />
                        Record payment
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
                        Opening the customer's view and taking the PDF are the
                        same two controls the edit page offers, so they are the
                        same component on both -- see DocumentOpenActions for
                        what that is worth. The public view was a row in
                        Details before, which is a list of facts about the
                        document; opening it is something you do with it.
                    -->
                    <DocumentOpenActions :invoice="invoice" />
                </div>
            </div>
        </template>

        <!--
            The same wrapper as the edit page: one step between sections, and
            the phone gutter the layout stops short of. Without it the cards
            ran edge to edge on a 390px screen.
        -->
        <div class="space-y-5 px-4 sm:px-0">
            <!--
                What the page exists to answer, before any detail: the figures,
                and the dates they fall due against. Paid and Balance only once
                a payment exists: until then Balance is the total restated and
                Paid is a zero, so an unpaid invoice showed one number three
                times. An estimate never has either.
            -->
            <!--
                `overflow-hidden` is the whole fix for this card's corners. The
                hairlines between the figures are gaps in a `bg-border` grid
                showing through, and that grid is a square rectangle: without
                clipping, its grey corners sat inside the card's rounded border
                where the white cells could not reach, so each corner carried a
                small grey wedge and the frame read as uneven.
            -->
            <Card class="overflow-hidden">
                <CardContent
                    class="grid gap-px bg-border p-0"
                    :class="tileColumns"
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
                    <template v-if="hasPayments">
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
                    <div v-if="invoice.issued_at" class="bg-card px-5 py-4">
                        <div class="text-xs text-muted-foreground">Issued</div>
                        <div class="mt-1 text-xl font-semibold">
                            {{ formatDate(invoice.issued_at) }}
                        </div>
                    </div>
                    <div v-if="paymentTermsLabel" class="bg-card px-5 py-4">
                        <div class="text-xs text-muted-foreground">
                            Payment terms
                        </div>
                        <div class="mt-1 text-xl font-semibold">
                            {{ paymentTermsLabel }}
                        </div>
                    </div>
                    <div v-if="invoice.due_at" class="bg-card px-5 py-4">
                        <div class="text-xs text-muted-foreground">
                            {{ isEstimate ? 'Valid until' : 'Due' }}
                        </div>
                        <div class="mt-1 text-xl font-semibold">
                            {{ formatDate(invoice.due_at) }}
                        </div>
                        <!--
                            How late it is, under the date it is late against.
                            This was a filled red badge up in the header, where
                            it was the loudest thing on the page and sat beside
                            a status that disagreed with it. The listing has
                            always stated it as a quiet line; the fact is the
                            same either way, and here it has its date to read
                            it against.
                        -->
                        <div
                            v-if="invoice.is_overdue"
                            class="mt-1 text-xs font-medium text-destructive"
                        >
                            {{ overdueLabel(invoice) }}
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
                                                bare
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
                                                bare
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
                                            bare
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
                                            bare
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
                                            bare
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
                                            bare
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
                                            bare
                                            :amount="invoice.tcs_amount"
                                            :currency="invoice.currency"
                                        />
                                    </dd>
                                </div>
                                <div
                                    class="flex justify-between gap-4 border-t border-border pt-1.5 font-semibold"
                                >
                                    <!--
                                        The one place the currency is named.
                                        Every figure above shares it, and
                                        repeating it per line stops a column of
                                        numbers lining up on its decimal point.
                                    -->
                                    <dt>
                                        Total ({{
                                            currencySymbol(invoice.currency)
                                        }})
                                    </dt>
                                    <dd class="tabular-nums">
                                        <MoneyDisplay
                                            bare
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
                            <!--
                                Label and value share a line. Stacked, each
                                fact cost two lines of a narrow column, and the
                                card ran longer than the items table beside it
                                to hold them all. The label does not wrap; the
                                value takes what is left and is right aligned,
                                so the values read as a column.

                                Every row here is optional now that the dates
                                have gone up to the summary strip, so the list
                                and its rule only appear when a document
                                actually carries one of them -- otherwise the
                                rule sat directly under the title, fencing off
                                nothing.
                            -->
                            <dl class="space-y-2.5 text-sm">
                                <!--
                                    Subject keeps its row even when the
                                    document has none, so the card always says
                                    what a document can carry here rather than
                                    leaving you to wonder whether it is unset
                                    or unsupported. The rest appear only when
                                    they have something to report.
                                -->
                                <div
                                    class="flex items-baseline justify-between gap-4"
                                >
                                    <dt
                                        class="shrink-0 text-xs text-muted-foreground"
                                    >
                                        Subject
                                    </dt>
                                    <dd
                                        class="min-w-0 text-right"
                                        :class="
                                            invoice.subject
                                                ? ''
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ invoice.subject || 'Not set' }}
                                    </dd>
                                </div>
                                <div
                                    v-if="invoice.order_number"
                                    class="flex items-baseline justify-between gap-4"
                                >
                                    <dt
                                        class="shrink-0 text-xs text-muted-foreground"
                                    >
                                        Order number
                                    </dt>
                                    <dd class="min-w-0 text-right">
                                        {{ invoice.order_number }}
                                    </dd>
                                </div>
                                <!--
                                    Shown whenever the document could carry one
                                    -- an Indian organization selling to a
                                    customer that is not overseas -- because
                                    its absence is a fact about the tax: with
                                    no place of supply the document shows a
                                    single tax total and no CGST/SGST/IGST
                                    split. Hidden entirely where it cannot
                                    apply, which is not the same as unset.
                                -->
                                <div
                                    v-if="placeOfSupplyApplies"
                                    class="flex items-baseline justify-between gap-4"
                                >
                                    <dt
                                        class="shrink-0 text-xs text-muted-foreground"
                                    >
                                        Place of supply
                                    </dt>
                                    <dd
                                        class="min-w-0 text-right"
                                        :class="
                                            placeOfSupplyLabel
                                                ? ''
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ placeOfSupplyLabel || 'Not set' }}
                                    </dd>
                                </div>
                            </dl>

                            <!--
                                Who it is for, under the same heading as the
                                rest of what you look up about a document.
                                They were two cards, which made the rail long
                                enough to outrun the items table beside it for
                                the sake of one more title.
                            -->
                            <hr class="my-4 border-border" />

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
                                <!--
                                    Always said, because "nothing here" and
                                    "ships where it bills" are the same picture
                                    and only one of them is true. The row used
                                    to appear only for a separate address, so
                                    the common case looked like a document
                                    nobody had told where to send anything.
                                -->
                                <div>
                                    <dt class="text-xs text-muted-foreground">
                                        Shipped to
                                    </dt>
                                    <dd
                                        v-if="shippingAddressLine"
                                        class="text-muted-foreground"
                                    >
                                        {{ shippingAddressLine }}
                                    </dd>
                                    <dd v-else class="text-muted-foreground">
                                        Same as billing address
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <!--
                        Only once there is a history. An empty card saying
                        "Never sent" held a card's worth of the rail to report
                        the absence of news, where Attachments and Notes &
                        terms simply stay away until they have something.
                    -->
                    <Card v-if="deliveries.length">
                        <CardHeader>
                            <CardTitle>Email history</CardTitle>
                        </CardHeader>
                        <CardContent class="text-sm">
                            <ul class="space-y-3">
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
            <!--
                Only once there is something to list. Recording the first one
                is the header's primary action, which this card's own button
                only repeated; an empty table under a title said less than the
                Balance figure above it already does.
            -->
            <Card v-if="!isEstimate && payments.length">
                <CardHeader>
                    <CardTitle>Payments</CardTitle>
                </CardHeader>
                <CardContent class="px-0 py-0">
                    <Table>
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
                </CardContent>
            </Card>

            <!--
                Below everything, because it is the one control on the page
                that ends it, and rare enough that it should not sit in the row
                you reach for daily. A line rather than a danger-zone card: the
                delete is soft and already behind a confirmation, so a panel
                would overstate it -- and it says so, since "delete" reads as
                final when it is not.
            -->
            <div
                v-if="canDelete"
                class="flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-border pt-4"
            >
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                    :aria-label="`Delete ${invoice.type} ${invoice.invoice_number}`"
                    @click="confirmingDelete = true"
                >
                    <Trash2 />
                    Delete {{ invoice.type }}
                </Button>
                <span class="text-sm text-muted-foreground">
                    Moves it to
                    <Link
                        href="/invoices/trash"
                        class="underline hover:text-foreground"
                    >
                        Trash</Link
                    >, where it can be restored.
                </span>
            </div>
        </div>

        <PaymentModal
            :show="showPaymentModal"
            :invoice="invoice"
            @close="showPaymentModal = false"
        />

        <!--
            It said the deletion could not be undone, which was never true: the
            model soft-deletes, there is a Trash page, and a restore route to
            come back from. The permanent one is a separate action taken from
            Trash. The title matches the control that opened this.
        -->
        <ConfirmationModal
            :show="confirmingDelete"
            :title="`Delete ${invoice.type}`"
            :message="`${invoice.invoice_number} moves to Trash, where you can restore it. Deleting it for good is a separate step taken from there.`"
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
