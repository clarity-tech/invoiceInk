<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import MoneyDisplay from '@/Components/MoneyDisplay.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/composables/useFormatDate';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Invoice } from '@/types';

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
    filters: { type: string | null };
}>();

const activeTab = ref<string>(props.filters.type ?? 'all');

function applyFilters() {
    const params: Record<string, string> = {};

    if (activeTab.value !== 'all') {
        params.type = activeTab.value;
    }

    router.get('/invoices/trash', params, {
        preserveState: true,
        replace: true,
    });
}

function setTab(tab: string) {
    activeTab.value = tab;
    applyFilters();
}

const restoring = ref<number | null>(null);

function restoreInvoice(invoice: Invoice) {
    restoring.value = invoice.id;
    router.post(
        `/invoices/${invoice.id}/restore`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                restoring.value = null;
            },
        },
    );
}

const confirmingForceDelete = ref(false);
const invoiceToForceDelete = ref<Invoice | null>(null);

function confirmForceDelete(invoice: Invoice) {
    invoiceToForceDelete.value = invoice;
    confirmingForceDelete.value = true;
}

function forceDeleteInvoice() {
    if (!invoiceToForceDelete.value) {
        return;
    }

    const id = invoiceToForceDelete.value.id;

    confirmingForceDelete.value = false;
    invoiceToForceDelete.value = null;

    router
        .optimistic((current: any) => ({
            invoices: {
                ...current.invoices,
                data: current.invoices.data.filter((i: Invoice) => i.id !== id),
                total: Math.max(0, current.invoices.total - 1),
            },
        }))
        .delete(`/invoices/${id}/force`, {
            preserveScroll: true,
        });
}

const tabs = [
    { key: 'all', label: 'All' },
    { key: 'invoice', label: 'Invoices' },
    { key: 'estimate', label: 'Estimates' },
];
</script>

<template>
    <AppLayout title="Trash">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Trash
                </h2>
                <a
                    href="/invoices"
                    class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50"
                >
                    Back to Invoices
                </a>
            </div>
        </template>

        <div class="py-4">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex gap-1 rounded-lg bg-gray-100 p-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="cursor-pointer rounded-md px-4 py-2 text-sm font-medium transition-colors"
                        :class="
                            activeTab === tab.key
                                ? 'bg-white text-gray-900 shadow-sm'
                                : 'text-gray-500 hover:text-gray-700'
                        "
                        @click="setTab(tab.key)"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Type
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Number
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Customer
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Total
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Deleted
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
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
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ invoice.invoice_number }}
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                            >
                                {{ invoice.customer?.name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <MoneyDisplay
                                        :amount="invoice.total"
                                        :currency="invoice.currency"
                                    />
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <StatusBadge :status="invoice.status" />
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                            >
                                {{
                                    invoice.deleted_at
                                        ? formatDate(invoice.deleted_at)
                                        : '—'
                                }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-sm whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        type="button"
                                        class="cursor-pointer text-brand-600 hover:text-brand-900 disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="restoring === invoice.id"
                                        @click="restoreInvoice(invoice)"
                                    >
                                        {{
                                            restoring === invoice.id
                                                ? 'Restoring...'
                                                : 'Restore'
                                        }}
                                    </button>
                                    <button
                                        type="button"
                                        class="cursor-pointer text-red-600 hover:text-red-900"
                                        @click="confirmForceDelete(invoice)"
                                    >
                                        Delete Forever
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Trash is empty
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    Deleted invoices and estimates will appear
                                    here.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <nav
                    v-if="invoices.last_page > 1"
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
                            <button
                                v-if="link.url"
                                type="button"
                                class="relative inline-flex cursor-pointer items-center rounded-md border px-4 py-2 text-sm font-medium"
                                :class="
                                    link.active
                                        ? 'border-brand-500 bg-brand-50 text-brand-600'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                "
                                @click="router.get(link.url!)"
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

        <ConfirmationModal
            :show="confirmingForceDelete"
            title="Delete Forever"
            message="Permanently delete this document? This cannot be undone."
            confirm-label="Delete Forever"
            :destructive="true"
            @confirm="forceDeleteInvoice"
            @cancel="confirmingForceDelete = false"
        />
    </AppLayout>
</template>
