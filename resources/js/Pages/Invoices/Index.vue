<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import MoneyDisplay from '@/Components/MoneyDisplay.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/composables/useFormatDate';
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
 * How late the document is, in the plainest form. "Overdue" is not a status --
 * it is derived from due_at -- so the status badge showed an invoice months
 * late as plainly "Sent", and the list could not say what needed chasing.
 */
function overdueLabel(invoice: Invoice): string {
    const days = invoice.days_overdue ?? 0;

    if (days < 1) {
        return 'Overdue today';
    }

    return days === 1 ? 'Overdue by 1 day' : `Overdue by ${days} days`;
}

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
        return { label: 'Sending…', class: 'text-gray-400' };
    }

    if (delivery.status === 'failed') {
        return { label: 'Send failed', class: 'text-red-600' };
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

// Delete
const confirmingDelete = ref(false);
const invoiceToDelete = ref<Invoice | null>(null);

function confirmDelete(invoice: Invoice) {
    invoiceToDelete.value = invoice;
    confirmingDelete.value = true;
}

const converting = ref<number | null>(null);

/** Focus lands here after a delete removes the row that was focused. */
const listRegion = ref<HTMLElement | null>(null);

function deleteInvoice() {
    if (!invoiceToDelete.value) {
        return;
    }

    const id = invoiceToDelete.value.id;

    confirmingDelete.value = false;
    invoiceToDelete.value = null;

    // Optimistic: remove from list immediately, auto-reverts on failure
    router
        .optimistic((current: any) => ({
            invoices: {
                ...current.invoices,
                data: current.invoices.data.filter((i: Invoice) => i.id !== id),
                total: Math.max(0, current.invoices.total - 1),
            },
        }))
        .delete(`/invoices/${id}`, {
            preserveScroll: true,
        });
}

function convertEstimate(invoice: Invoice) {
    converting.value = invoice.id;
    router.post(
        `/invoices/${invoice.id}/convert`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                converting.value = null;
            },
        },
    );
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

function publicUrl(invoice: Invoice): string {
    return invoice.type === 'invoice'
        ? `/invoices/view/${invoice.ulid}`
        : `/estimates/view/${invoice.ulid}`;
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
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Invoices & Estimates
                </h2>
                <div class="flex flex-wrap gap-2">
                    <a
                        href="/invoices/trash"
                        class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50"
                    >
                        Trash
                    </a>
                    <a
                        v-if="can('create')"
                        href="/estimates/create"
                        class="rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-800"
                    >
                        Create Estimate
                    </a>
                    <a
                        v-if="can('create')"
                        href="/invoices/create"
                        class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                    >
                        Create Invoice
                    </a>
                </div>
            </div>
        </template>

        <div class="py-4">
            <!-- Filter Tabs and Status -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div
                    role="group"
                    aria-label="Filter by document type"
                    class="flex flex-wrap gap-1 rounded-lg bg-gray-100 p-1"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        :aria-pressed="activeTab === tab.key"
                        class="cursor-pointer rounded-md px-4 py-2 text-sm font-medium transition-colors"
                        :class="
                            activeTab === tab.key
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-600 hover:text-gray-900'
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
                    class="w-full max-w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-auto"
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
                ref="listRegion"
                tabindex="-1"
                aria-label="Invoices and estimates list"
                class="overflow-x-auto bg-white shadow-sm focus:ring-2 focus:ring-brand-500 focus:outline-none sm:rounded-lg"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <caption class="sr-only">
                        Invoices and estimates
                    </caption>
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Type
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Number
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Customer
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Total
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Date
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="invoice in invoices.data"
                            :key="invoice.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="
                                        invoice.type === 'invoice'
                                            ? 'bg-brand-100 text-brand-800'
                                            : 'bg-green-100 text-green-800'
                                    "
                                >
                                    {{ invoice.type.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ invoice.invoice_number }}
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <div
                                    class="max-w-[220px] truncate"
                                    :title="invoice.customer?.name ?? 'N/A'"
                                >
                                    {{ invoice.customer?.name ?? 'N/A' }}
                                </div>
                                <!-- Truncated to one line at the same width as
                                     the name above. Unconstrained it would wrap
                                     rather than overflow, so this is about
                                     keeping rows a predictable height, not about
                                     horizontal overflow. Full text stays on
                                     `title` for hover and assistive tech. -->
                                <div
                                    v-if="invoice.subject"
                                    class="mt-0.5 max-w-[220px] truncate text-xs text-gray-400"
                                    :title="invoice.subject"
                                >
                                    {{ invoice.subject }}
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <MoneyDisplay
                                        :amount="invoice.total"
                                        :currency="invoice.currency"
                                    />
                                </div>
                                <div
                                    v-if="invoice.tax > 0"
                                    class="text-xs text-gray-500"
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
                                    class="text-xs font-medium text-amber-700"
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
                                    class="mt-1 text-xs font-medium text-red-600"
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
                                class="px-4 py-4 text-sm whitespace-nowrap text-gray-500"
                            >
                                {{ formatDate(invoice.issued_at) }}
                                <!--
                                    The issue date was the only one shown, but
                                    the due date is the one that decides whether
                                    an invoice needs chasing.
                                -->
                                <div
                                    v-if="invoice.due_at"
                                    class="text-xs text-gray-400"
                                >
                                    Due {{ formatDate(invoice.due_at) }}
                                </div>
                            </td>
                            <td
                                class="px-4 py-4 text-right text-sm whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <a
                                        :href="publicUrl(invoice)"
                                        target="_blank"
                                        :aria-label="`${actionLabel('View', invoice)} (opens in a new tab)`"
                                        class="text-green-700 hover:text-green-900"
                                        >View</a
                                    >
                                    <a
                                        v-if="can('update')"
                                        :href="`/invoices/${invoice.id}/edit`"
                                        :aria-label="
                                            actionLabel('Edit', invoice)
                                        "
                                        class="text-brand-600 hover:text-brand-900"
                                        >Edit</a
                                    >
                                    <a
                                        :href="pdfUrl(invoice)"
                                        :aria-label="`Download PDF of ${invoice.type} ${invoice.invoice_number}`"
                                        class="text-red-600 hover:text-red-900"
                                        >PDF</a
                                    >
                                    <!-- Opens a pre-filled create form; nothing
                                         is saved until the user submits it. -->
                                    <a
                                        v-if="can('create')"
                                        :href="`/invoices/${invoice.id}/duplicate`"
                                        :aria-label="
                                            actionLabel('Duplicate', invoice)
                                        "
                                        class="text-gray-600 hover:text-gray-900"
                                        >Duplicate</a
                                    >
                                    <button
                                        v-if="
                                            invoice.type === 'estimate' &&
                                            can('create')
                                        "
                                        type="button"
                                        class="cursor-pointer text-purple-600 hover:text-purple-900 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="converting === invoice.id"
                                        :aria-label="`Convert ${invoice.type} ${invoice.invoice_number} to an invoice`"
                                        :aria-busy="converting === invoice.id"
                                        @click="convertEstimate(invoice)"
                                    >
                                        {{
                                            converting === invoice.id
                                                ? 'Converting...'
                                                : 'Convert'
                                        }}
                                    </button>
                                    <button
                                        v-if="can('delete')"
                                        type="button"
                                        :aria-label="
                                            actionLabel('Delete', invoice)
                                        "
                                        class="cursor-pointer text-red-600 hover:text-red-900"
                                        @click="confirmDelete(invoice)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg
                                    class="mx-auto h-12 w-12 text-gray-300"
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
                                    class="mt-2 text-sm font-semibold text-gray-900"
                                >
                                    No documents yet
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Get started by creating your first invoice
                                    or estimate.
                                </p>
                                <div
                                    v-if="can('create')"
                                    class="mt-4 flex justify-center gap-3"
                                >
                                    <a
                                        href="/invoices/create"
                                        class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                                        >Create Invoice</a
                                    >
                                    <a
                                        href="/estimates/create"
                                        class="rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-800"
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
                    class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                >
                    <div class="hidden sm:block">
                        <p class="text-sm text-gray-700">
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
                                        ? 'border-brand-500 bg-brand-50 text-brand-600'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                "
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="relative inline-flex items-center rounded-md border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Delete Confirmation -->
        <ConfirmationModal
            :restore-focus-to="listRegion"
            :show="confirmingDelete"
            title="Delete Document"
            message="Are you sure you want to delete this document? This action cannot be undone."
            confirm-label="Delete"
            :destructive="true"
            @confirm="deleteInvoice"
            @cancel="confirmingDelete = false"
        />
    </AppLayout>
</template>
