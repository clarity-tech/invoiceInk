<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatMoney } from '@/composables/useFormatMoney';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Currency, InvoiceStatus } from '@/types';

interface Stats {
    total_revenue: number;
    total_collected: number;
    total_outstanding: number;
    invoice_count: number;
    paid_count: number;
    overdue_count: number;
    collection_rate: number;
    currency: Currency | null;
}

interface StatusItem {
    status: InvoiceStatus;
    label: string;
    count: number;
    total: number;
}

interface RecentInvoice {
    id: number;
    invoice_number: string;
    status: InvoiceStatus;
    customer_name: string;
    issued_at: string;
    total: number;
    currency: Currency;
}

interface OverdueInvoice {
    id: number;
    invoice_number: string;
    customer_name: string;
    remaining_balance: number;
    currency: Currency;
    due_at_human: string;
}

interface RecentPayment {
    id: number;
    invoice_number: string;
    customer_name: string;
    amount: number;
    currency: Currency;
    payment_method: string | null;
    payment_date: string;
}

interface TopCustomer {
    name: string;
    invoice_count: number;
    total: number;
    paid: number;
    outstanding: number;
}

interface MonthlyTrendItem {
    label: string;
    short: string;
    invoiced: number;
    collected: number;
}

interface EstimateStats {
    count: number;
    total: number;
    accepted: number;
}

const props = defineProps<{
    period: string;
    organizationName: string;
    stats: Stats;
    statusBreakdown: StatusItem[];
    recentInvoices: RecentInvoice[];
    overdueInvoices: OverdueInvoice[];
    recentPayments: RecentPayment[];
    topCustomers: TopCustomer[];
    monthlyTrend: MonthlyTrendItem[];
    customerCount: number;
    estimateStats: EstimateStats;
}>();

const currency = computed(() => props.stats.currency ?? 'INR');

function fmt(amount: number): string {
    return formatMoney(amount, currency.value);
}

const periods = [
    { value: 'this_week', label: 'This Week' },
    { value: 'this_month', label: 'This Month' },
    { value: 'last_month', label: 'Last Month' },
    { value: 'this_quarter', label: 'This Quarter' },
    { value: 'this_year', label: 'This Year' },
    { value: 'all_time', label: 'All Time' },
];

function onPeriodChange(event: Event) {
    const value = (event.target as HTMLSelectElement).value;
    router.reload({
        data: { period: value },
        only: [
            'period',
            'stats',
            'statusBreakdown',
            'recentInvoices',
            'overdueInvoices',
            'topCustomers',
            'monthlyTrend',
            'estimateStats',
        ],
    });
}

const trendMax = computed(() => {
    return Math.max(1, ...props.monthlyTrend.map((m) => m.invoiced));
});

function trendBarHeight(value: number): string {
    const pct =
        trendMax.value > 0 ? Math.max(2, (value / trendMax.value) * 100) : 2;

    return `${pct}%`;
}

const totalStatusCount = computed(() =>
    props.statusBreakdown.reduce((sum, s) => sum + s.count, 0),
);
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="px-4 py-4 sm:px-0">
            <!-- Header with Period Selector -->
            <div
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ organizationName }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Business overview and analytics
                    </p>
                </div>
                <select
                    :value="period"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    @change="onPeriodChange"
                >
                    <option
                        v-for="p in periods"
                        :key="p.value"
                        :value="p.value"
                    >
                        {{ p.label }}
                    </option>
                </select>
            </div>

            <!-- KPI Cards -->
            <div
                class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <!-- Total Invoiced -->
                <div class="rounded-lg bg-white p-5 shadow">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-500">
                            Total Invoiced
                        </div>
                        <svg
                            class="size-5 text-brand-500"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-gray-900">
                        {{ fmt(stats.total_revenue) }}
                    </div>
                    <div class="mt-1 text-sm text-gray-500">
                        {{ stats.invoice_count }}
                        {{ stats.invoice_count === 1 ? 'invoice' : 'invoices' }}
                    </div>
                </div>

                <!-- Collected -->
                <div class="rounded-lg bg-white p-5 shadow">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-500">
                            Collected
                        </div>
                        <svg
                            class="size-5 text-green-500"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-green-600">
                        {{ fmt(stats.total_collected) }}
                    </div>
                    <div class="mt-1 text-sm text-gray-500">
                        {{ stats.collection_rate }}% collection rate
                    </div>
                </div>

                <!-- Outstanding -->
                <div class="rounded-lg bg-white p-5 shadow">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-500">
                            Outstanding
                        </div>
                        <svg
                            class="size-5 text-amber-500"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-amber-600">
                        {{ fmt(stats.total_outstanding) }}
                    </div>
                    <div
                        class="mt-1 text-sm"
                        :class="
                            stats.overdue_count > 0
                                ? 'font-medium text-red-600'
                                : 'text-gray-500'
                        "
                    >
                        {{ stats.overdue_count }} overdue
                    </div>
                </div>

                <!-- Customers & Estimates -->
                <div class="rounded-lg bg-white p-5 shadow">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-500">
                            Customers
                        </div>
                        <svg
                            class="size-5 text-purple-500"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-4.5 0 2.625 2.625 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                            />
                        </svg>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-gray-900">
                        {{ customerCount }}
                    </div>
                    <div class="mt-1 text-sm text-gray-500">
                        {{ estimateStats.count }}
                        {{
                            estimateStats.count === 1 ? 'estimate' : 'estimates'
                        }}
                        ({{ estimateStats.accepted }} accepted)
                    </div>
                </div>
            </div>

            <!-- Status Breakdown + Monthly Trend -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Status Breakdown -->
                <div class="rounded-lg bg-white p-5 shadow">
                    <h3 class="mb-4 text-sm font-semibold text-gray-900">
                        Invoice Status Breakdown
                    </h3>
                    <div class="space-y-3">
                        <template
                            v-for="item in statusBreakdown"
                            :key="item.status"
                        >
                            <div
                                v-if="item.count > 0"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-2">
                                    <StatusBadge :status="item.status" />
                                    <span class="text-sm text-gray-500">{{
                                        item.count
                                    }}</span>
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-900"
                                    >{{ fmt(item.total) }}</span
                                >
                            </div>
                        </template>
                        <p
                            v-if="totalStatusCount === 0"
                            class="py-4 text-center text-sm text-gray-400"
                        >
                            No invoices in this period
                        </p>
                    </div>
                </div>

                <!-- 6-Month Trend -->
                <div class="rounded-lg bg-white p-5 shadow lg:col-span-2">
                    <h3 class="mb-4 text-sm font-semibold text-gray-900">
                        6-Month Trend
                    </h3>
                    <div class="flex h-40 items-end justify-between gap-2">
                        <div
                            v-for="month in monthlyTrend"
                            :key="month.label"
                            class="flex flex-1 flex-col items-center gap-1"
                        >
                            <div
                                class="flex h-28 w-full flex-col items-center justify-end gap-0.5"
                            >
                                <div
                                    class="w-full max-w-[2rem] rounded-t bg-brand-200 transition-all"
                                    :style="{
                                        height: trendBarHeight(month.invoiced),
                                    }"
                                    :title="'Invoiced: ' + fmt(month.invoiced)"
                                />
                                <div
                                    v-if="month.collected > 0"
                                    class="w-full max-w-[2rem] rounded-t bg-green-400 transition-all"
                                    :style="{
                                        height: trendBarHeight(month.collected),
                                    }"
                                    :title="
                                        'Collected: ' + fmt(month.collected)
                                    "
                                />
                            </div>
                            <span class="text-xs text-gray-500">{{
                                month.short
                            }}</span>
                        </div>
                    </div>
                    <div
                        class="mt-3 flex items-center gap-4 text-xs text-gray-500"
                    >
                        <span class="flex items-center gap-1"
                            ><span
                                class="inline-block h-3 w-3 rounded bg-brand-200"
                            />
                            Invoiced</span
                        >
                        <span class="flex items-center gap-1"
                            ><span
                                class="inline-block h-3 w-3 rounded bg-green-400"
                            />
                            Collected</span
                        >
                    </div>
                </div>
            </div>

            <!-- Top Customers + Overdue Invoices -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Top Customers -->
                <div class="rounded-lg bg-white p-5 shadow">
                    <h3 class="mb-4 text-sm font-semibold text-gray-900">
                        Top Customers
                    </h3>
                    <div v-if="topCustomers.length > 0" class="space-y-3">
                        <div
                            v-for="cust in topCustomers"
                            :key="cust.name"
                            class="flex items-center justify-between"
                        >
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ cust.name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ cust.invoice_count }}
                                    {{
                                        cust.invoice_count === 1
                                            ? 'invoice'
                                            : 'invoices'
                                    }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ fmt(cust.total) }}
                                </div>
                                <div
                                    v-if="cust.outstanding > 0"
                                    class="text-xs text-amber-600"
                                >
                                    {{ fmt(cust.outstanding) }} due
                                </div>
                                <div v-else class="text-xs text-green-600">
                                    Fully paid
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-4 text-center text-sm text-gray-400">
                        No customer data in this period
                    </p>
                </div>

                <!-- Overdue Invoices -->
                <div class="rounded-lg bg-white p-5 shadow">
                    <h3 class="mb-4 text-sm font-semibold text-gray-900">
                        Overdue Invoices
                        <span
                            v-if="overdueInvoices.length > 0"
                            class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700"
                        >
                            {{ overdueInvoices.length }}
                        </span>
                    </h3>
                    <div v-if="overdueInvoices.length > 0" class="space-y-3">
                        <div
                            v-for="inv in overdueInvoices"
                            :key="inv.id"
                            class="-mx-2 flex items-center justify-between rounded px-2 py-1 hover:bg-gray-50"
                        >
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ inv.invoice_number }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ inv.customer_name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-red-600">
                                    {{
                                        formatMoney(
                                            inv.remaining_balance,
                                            inv.currency,
                                        )
                                    }}
                                </div>
                                <div class="text-xs text-red-500">
                                    Due {{ inv.due_at_human }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-4 text-center">
                        <svg
                            class="mx-auto size-8 text-green-300"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                        <p class="mt-1 text-sm text-gray-400">
                            No overdue invoices
                        </p>
                    </div>
                </div>
            </div>

            <!-- Recent Invoices + Recent Payments -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Recent Invoices -->
                <div class="rounded-lg bg-white p-5 shadow">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Recent Invoices
                        </h3>
                    </div>
                    <div v-if="recentInvoices.length > 0" class="space-y-3">
                        <div
                            v-for="inv in recentInvoices"
                            :key="inv.id"
                            class="-mx-2 flex items-center justify-between rounded px-2 py-1 hover:bg-gray-50"
                        >
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ inv.invoice_number }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ inv.customer_name }} &middot;
                                    {{ inv.issued_at }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ formatMoney(inv.total, inv.currency) }}
                                </div>
                                <StatusBadge :status="inv.status" />
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-4 text-center text-sm text-gray-400">
                        No invoices yet
                    </p>
                </div>

                <!-- Recent Payments -->
                <div class="rounded-lg bg-white p-5 shadow">
                    <h3 class="mb-4 text-sm font-semibold text-gray-900">
                        Recent Payments
                    </h3>
                    <div v-if="recentPayments.length > 0" class="space-y-3">
                        <div
                            v-for="payment in recentPayments"
                            :key="payment.id"
                            class="flex items-center justify-between"
                        >
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ payment.invoice_number }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ payment.customer_name }}
                                    <template v-if="payment.payment_method">
                                        &middot;
                                        {{
                                            payment.payment_method.replace(
                                                '_',
                                                ' ',
                                            )
                                        }}
                                    </template>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-green-600">
                                    +{{
                                        formatMoney(
                                            payment.amount,
                                            payment.currency,
                                        )
                                    }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ payment.payment_date }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-4 text-center text-sm text-gray-400">
                        No payments recorded yet
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <a
                    href="/invoices/create"
                    class="flex items-center gap-3 rounded-lg bg-white p-4 shadow transition-colors hover:bg-brand-50"
                >
                    <svg
                        class="size-5 text-brand-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15"
                        />
                    </svg>
                    <span class="text-sm font-medium text-gray-700"
                        >New Invoice</span
                    >
                </a>
                <a
                    href="/estimates/create"
                    class="flex items-center gap-3 rounded-lg bg-white p-4 shadow transition-colors hover:bg-green-50"
                >
                    <svg
                        class="size-5 text-green-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15"
                        />
                    </svg>
                    <span class="text-sm font-medium text-gray-700"
                        >New Estimate</span
                    >
                </a>
                <a
                    href="/customers"
                    class="flex items-center gap-3 rounded-lg bg-white p-4 shadow transition-colors hover:bg-purple-50"
                >
                    <svg
                        class="size-5 text-purple-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                        />
                    </svg>
                    <span class="text-sm font-medium text-gray-700"
                        >Customers</span
                    >
                </a>
                <a
                    href="/numbering-series"
                    class="flex items-center gap-3 rounded-lg bg-white p-4 shadow transition-colors hover:bg-gray-50"
                >
                    <svg
                        class="size-5 text-gray-600"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                        />
                    </svg>
                    <span class="text-sm font-medium text-gray-700"
                        >Settings</span
                    >
                </a>
            </div>
        </div>
    </AppLayout>
</template>
