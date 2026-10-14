<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import MoneyDisplay from '@/Components/MoneyDisplay.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/composables/useFormatDate';
import { overdueLabel } from '@/composables/useOverdueLabel';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Invoice } from '@/types';

const { can } = usePermissions();

interface PaginatedInvoices {
    data: Invoice[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    invoices: PaginatedInvoices;
    filters: { type: string | null; status: string | null };
    statusOptions: Record<string, string>;
}>();

/**
 * Whether the balance is worth stating. Equal to the total it says nothing the
 * line above has not, and at zero the document is settled.
 */
function showsBalance(invoice: Invoice): boolean {
    const balance = invoice.remaining_balance;

    return (
        balance !== undefined &&
        balance !== null &&
        balance > 0 &&
        balance !== invoice.total
    );
}

/**
 * The email send is queued, so its outcome lands after the page did. A pending
 * or failed send is worth reporting; a successful one is already implied by the
 * document status, so it stays quiet rather than adding noise to every row.
 */
function sendState(invoice: Invoice): { label: string; class: string } | null {
    const delivery = invoice.latest_email_delivery;

    if (!delivery) {
        return null;
    }

    if (delivery.status === 'queued' || delivery.status === 'sending') {
        return { label: 'Sending…', class: 'text-faint-foreground' };
    }

    if (delivery.status === 'failed') {
        return { label: 'Send failed', class: 'text-destructive' };
    }

    return null;
}

const activeTab = ref<string>(props.filters.type ?? 'all');
const statusFilter = ref<string>(props.filters.status ?? '');

function applyFilters() {
    const params: Record<string, string> = {};

    if (activeTab.value !== 'all') {
        params.type = activeTab.value;
    }

    if (statusFilter.value) {
        params.status = statusFilter.value;
    }

    router.get('/invoices', params, { preserveState: true, replace: true });
}

function setTab(tab: string) {
    activeTab.value = tab;
    applyFilters();
}

function onStatusChange() {
    applyFilters();
}

/**
 * Row actions are otherwise a page full of identical "View" / "Edit" / "PDF"
 * links. Naming each one with its document makes them distinguishable in a
 * screen reader's element list, where they are read out of context.
 */
function actionLabel(action: string, invoice: Invoice): string {
    return `${action} ${invoice.type} ${invoice.invoice_number}`;
}

/**
 * Paginator labels arrive as markup ("&laquo; Previous", "1"). They are still
 * rendered as-is so the chevrons show, but a link announced as just "1" says
 * nothing about where it goes, so numeric pages get a spoken label.
 */
function pageLinkLabel(label: string): string {
    const text = label
        .replace(/&laquo;|&raquo;|«|»/g, '')
        .replace(/<[^>]*>/g, '')
        .trim();

    return /^\d+$/.test(text) ? `Go to page ${text}` : text;
}

function pdfUrl(invoice: Invoice): string {
    return invoice.type === 'invoice'
        ? `/invoices/${invoice.ulid}/pdf`
        : `/estimates/${invoice.ulid}/pdf`;
}

const tabs = [
    { key: 'all', label: 'All' },
    { key: 'invoice', label: 'Invoices' },
    { key: 'estimate', label: 'Estimates' },
];
</script>

<template>
    <AppLayout title="Invoices & Estimates">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl leading-tight font-semibold text-foreground">
                    Invoices & Estimates
                </h2>
                <div class="flex flex-wrap gap-2">
                    <a
                        href="/invoices/trash"
                        class="rounded-md bg-card px-4 py-2 text-sm font-medium text-soft-foreground shadow-sm ring-1 ring-input hover:bg-muted"
                    >
                        Trash
                    </a>
                    <a
                        v-if="can('create')"
                        href="/estimates/create"
                        class="rounded-md bg-success px-4 py-2 text-sm font-medium text-success-foreground shadow-sm hover:bg-success/90"
                    >
                        Create Estimate
                    </a>
                    <a
                        v-if="can('create')"
                        href="/invoices/create"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                    >
                        Create Invoice
                    </a>
                </div>
            </div>
        </template>

        <div class="px-4 sm:px-0">
            <!-- Filter Tabs and Status -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div
                    role="group"
                    aria-label="Filter by document type"
                    class="flex flex-wrap gap-1 rounded-lg bg-secondary p-1"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        :aria-pressed="activeTab === tab.key"
                        class="cursor-pointer rounded-md px-4 py-2 text-sm font-medium transition-colors"
                        :class="
                            activeTab === tab.key
                                ? 'bg-card text-foreground shadow-sm'
                                : 'text-soft-foreground hover:text-foreground'
                        "
                        @click="setTab(tab.key)"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <label for="status-filter" class="sr-only">
                    Filter by status
                </label>
                <select
                    id="status-filter"
                    v-model="statusFilter"
                    class="w-full max-w-full rounded-md border-input text-sm shadow-sm focus:border-ring focus:ring-ring sm:w-auto"
                    @change="onStatusChange"
                >
                    <option value="">All Statuses</option>
                    <option
                        v-for="(label, value) in statusOptions"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>
            </div>

            <!-- Table -->
            <div
                tabindex="-1"
                aria-label="Invoices and estimates list"
                class="overflow-x-auto rounded-xl border border-border bg-card shadow-sm focus:ring-2 focus:ring-ring focus:outline-none"
            >
                <table class="min-w-full divide-y divide-border">
                    <caption class="sr-only">
                        Invoices and estimates
                    </caption>
                    <thead class="bg-muted/60">
                        <tr>
                            <th
                                scope="col"
                                class="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground"
                            >
                                Number
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground"
                            >
                                Customer
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-2.5 text-right text-xs font-medium text-muted-foreground"
                            >
                                Total
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground"
                            >
                                Date
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-2.5 text-right text-xs font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr
                            v-for="invoice in invoices.data"
                            :key="invoice.id"
                            class="hover:bg-muted/40"
                        >
                            <td class="px-4 py-4 whitespace-nowrap">
                                <!--
                                    The number is how a row is identified, so it
                                    is also how the document opens. Reading an
                                    invoice previously meant the Edit link --
                                    a form, for a question that only needed
                                    looking at it.
                                -->
                                <Link
                                    :href="`/invoices/${invoice.id}`"
                                    class="text-sm font-medium text-foreground hover:text-primary-soft-foreground hover:underline"
                                    :aria-label="actionLabel('Open', invoice)"
                                >
                                    {{ invoice.invoice_number }}
                                </Link>
                                <!--
                                    Outside the link, not inside it: the link's
                                    aria-label already names the type, and a
                                    badge swallowed by it would be read twice.
                                -->
                                <div class="mt-1">
                                    <Badge
                                        :variant="
                                            invoice.type === 'invoice'
                                                ? 'brandSoft'
                                                : 'infoSoft'
                                        "
                                    >
                                        {{
                                            invoice.type === 'invoice'
                                                ? 'Invoice'
                                                : 'Estimate'
                                        }}
                                    </Badge>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-muted-foreground">
                                <!--
                                    The second way into the document, and the
                                    one people actually aim at: the number is
                                    precise but the customer and the subject
                                    are what a row is scanned for. Both lines
                                    sit inside the link, so its accessible name
                                    reads as the document rather than as a
                                    repeat of the number beside it.
                                -->
                                <Link
                                    :href="`/invoices/${invoice.id}`"
                                    class="block hover:text-primary-soft-foreground"
                                >
                                    <div
                                        class="max-w-[220px] truncate"
                                        :title="invoice.customer?.name ?? 'N/A'"
                                    >
                                        {{ invoice.customer?.name ?? 'N/A' }}
                                    </div>
                                    <!-- Truncated to one line at the same width
                                         as the name above. Unconstrained it
                                         would wrap rather than overflow, so this
                                         is about keeping rows a predictable
                                         height, not about horizontal overflow.
                                         Full text stays on `title` for hover and
                                         assistive tech. -->
                                    <div
                                        v-if="invoice.subject"
                                        class="mt-0.5 max-w-[220px] truncate text-xs text-faint-foreground"
                                        :title="invoice.subject"
                                    >
                                        {{ invoice.subject }}
                                    </div>
                                </Link>
                            </td>
                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                <div
                                    class="text-sm font-medium text-foreground"
                                >
                                    <MoneyDisplay
                                        :amount="invoice.total"
                                        :currency="invoice.currency"
                                    />
                                </div>
                                <div
                                    v-if="invoice.tax > 0"
                                    class="text-xs text-muted-foreground"
                                >
                                    Tax:
                                    <MoneyDisplay
                                        :amount="invoice.tax"
                                        :currency="invoice.currency"
                                    />
                                </div>
                                <!--
                                    Only when it differs from the total. On a
                                    part-paid document the total overstates what
                                    is actually collectable, and that was the
                                    only figure this column showed.
                                -->
                                <div
                                    v-if="showsBalance(invoice)"
                                    class="text-xs font-medium text-warning"
                                >
                                    Due:
                                    <MoneyDisplay
                                        :amount="invoice.remaining_balance!"
                                        :currency="invoice.currency"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <StatusBadge :status="invoice.status" />
                                <!--
                                    Shown under the document status rather than in a
                                    column of its own: the Actions column here was
                                    clipped once already (f6550132) and a seventh
                                    column would put it back at the edge.
                                -->
                                <!--
                                    "Overdue" is not a status -- it is derived
                                    from due_at -- so the badge alone showed an
                                    invoice months late as plainly "Sent".
                                -->
                                <div
                                    v-if="invoice.is_overdue"
                                    class="mt-1 text-xs font-medium text-destructive"
                                >
                                    {{ overdueLabel(invoice) }}
                                </div>
                                <div
                                    v-if="sendState(invoice)"
                                    class="mt-1 text-xs"
                                    :class="sendState(invoice)!.class"
                                >
                                    {{ sendState(invoice)!.label }}
                                </div>
                            </td>
                            <td
                                class="px-4 py-4 text-sm whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDate(invoice.issued_at) }}
                                <!--
                                    The issue date was the only one shown, but
                                    the due date is the one that decides whether
                                    an invoice needs chasing.
                                -->
                                <div
                                    v-if="invoice.due_at"
                                    class="text-xs text-faint-foreground"
                                >
                                    Due {{ formatDate(invoice.due_at) }}
                                </div>
                            </td>
                            <td
                                class="px-4 py-4 text-right text-sm whitespace-nowrap"
                            >
                                <!--
                                    Six coloured links per row, repeated down
                                    the page, made the list read as a control
                                    panel rather than a list of documents.
                                    Opening, editing, sending, converting and
                                    deleting all belong to one document, so
                                    they live on that document's page; the
                                    number in the first column is the way in.

                                    What is left is the pair that are useful
                                    *without* opening anything: the PDF a row
                                    is often chased for, and the duplicate that
                                    starts the next document from this one.
                                -->
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Button
                                        as="a"
                                        variant="ghost"
                                        size="sm"
                                        class="text-muted-foreground hover:text-foreground"
                                        :href="pdfUrl(invoice)"
                                        :aria-label="`Download PDF of ${invoice.type} ${invoice.invoice_number}`"
                                    >
                                        PDF
                                    </Button>
                                    <!-- Opens a pre-filled create form; nothing
                                         is saved until the user submits it. -->
                                    <Button
                                        v-if="can('create')"
                                        as="a"
                                        variant="ghost"
                                        size="sm"
                                        class="text-muted-foreground hover:text-foreground"
                                        :href="`/invoices/${invoice.id}/duplicate`"
                                        :aria-label="
                                            actionLabel('Duplicate', invoice)
                                        "
                                    >
                                        Duplicate
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg
                                    class="mx-auto h-12 w-12 text-faint-foreground"
                                    aria-hidden="true"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"
                                    />
                                </svg>
                                <h3
                                    class="mt-2 text-sm font-semibold text-foreground"
                                >
                                    No documents yet
                                </h3>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Get started by creating your first invoice
                                    or estimate.
                                </p>
                                <div
                                    v-if="can('create')"
                                    class="mt-4 flex justify-center gap-3"
                                >
                                    <a
                                        href="/invoices/create"
                                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                                        >Create Invoice</a
                                    >
                                    <a
                                        href="/estimates/create"
                                        class="rounded-md bg-success px-4 py-2 text-sm font-medium text-success-foreground shadow-sm hover:bg-success/90"
                                        >Create Estimate</a
                                    >
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <nav
                    v-if="invoices.last_page > 1"
                    aria-label="Pagination"
                    class="flex items-center justify-between border-t border-border bg-card px-4 py-3 sm:px-6"
                >
                    <div class="hidden sm:block">
                        <p class="text-sm text-soft-foreground">
                            Showing page
                            <span class="font-medium">{{
                                invoices.current_page
                            }}</span>
                            of
                            <span class="font-medium">{{
                                invoices.last_page
                            }}</span>
                            ({{ invoices.total }} total)
                        </p>
                    </div>
                    <div
                        class="flex flex-1 justify-between gap-2 sm:justify-end"
                    >
                        <template
                            v-for="link in invoices.links"
                            :key="link.label"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :aria-current="link.active ? 'page' : undefined"
                                :aria-label="pageLinkLabel(link.label)"
                                class="relative inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium"
                                :class="
                                    link.active
                                        ? 'border-primary bg-primary-soft/50 text-primary'
                                        : 'border-input bg-card text-soft-foreground hover:bg-muted'
                                "
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="relative inline-flex items-center rounded-md border border-border bg-secondary px-4 py-2 text-sm font-medium text-faint-foreground"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
