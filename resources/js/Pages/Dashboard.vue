<script setup lang="ts">
import { router, usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatMoney } from '@/composables/useFormatMoney';
import AppLayout from '@/Layouts/AppLayout.vue';
import { create as teamsCreate } from '@/routes/teams';
import type { Currency, InvoiceStatus } from '@/types';

const page = usePage();
const hasOrganization = computed(
    () => !!(page.props.auth as { currentTeam?: unknown })?.currentTeam,
);

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

interface CurrencyBreakdownItem {
    currency: Currency;
    invoice_count: number;
    total: number;
    collected: number;
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
    currencyBreakdown: CurrencyBreakdownItem[];
}>();

const currency = computed(() => props.stats.currency ?? 'INR');

/**
 * The KPI tiles are converted into the organization's own currency, so the
 * original amounts need disclosing whenever any invoice was raised in another
 * one. Testing `currencyBreakdown.length > 1` missed the case this exists for:
 * an organization invoicing entirely in one foreign currency sees converted
 * figures and, with a single group, no explanation of where they came from.
 */
const hasConvertedAmounts = computed(() =>
    props.currencyBreakdown.some((row) => row.currency !== currency.value),
);

/**
 * Format a base-currency aggregate. Every caller is a figure the server has
 * already converted, so mark it approximate when any of it was raised in
 * another currency -- an FX estimate should not read like an exact amount.
 * Zero is exact whatever the rate, so it keeps the plain form.
 */
function fmt(amount: number): string {
    const formatted = formatMoney(amount, currency.value);

    return hasConvertedAmounts.value && amount !== 0
        ? `≈ ${formatted}`
        : formatted;
}

/**
 * Several things on this page ignore the period selector, and used to do it
 * silently: `Customer::count()` and the `overdue()` scope are not date-filtered,
 * and the Recent Invoices, Recent Payments and Overdue Invoices panels are
 * deliberately latest-first rather than windowed -- "recent" and "overdue" mean
 * the same thing whatever period is selected.
 *
 * Reading "This Month" at the top while a panel below lists August is only
 * confusing if nothing says so, so each of them carries an (all time) marker.
 * The marker is suppressed when All Time is selected, where it would be noise.
 *
 * The 6-Month Trend is exempt: it has its own fixed window and its title
 * already states it.
 */
const isAllTime = computed(() => props.period === 'all_time');

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
            'currencyBreakdown',
        ],
    });
}

/**
 * The chart scales to the largest value in either series, not just invoiced --
 * collected is drawn on the same axis, so sizing it against a different
 * maximum would make the two bars incomparable.
 */
const trendPeak = computed(() =>
    Math.max(
        0,
        ...props.monthlyTrend.map((m) => Math.max(m.invoiced, m.collected)),
    ),
);

/** Nothing to plot. Six empty bars would imply a measurement that was taken. */
const trendIsEmpty = computed(() => trendPeak.value === 0);

/**
 * Gridline values from zero to the peak. Rendering the amounts on an axis is
 * what makes the bars mean something: before this they were sized purely as a
 * percentage of the tallest month, so a bar could be any amount at all.
 */
const trendTicks = computed(() => {
    const peak = trendPeak.value;

    return [peak, peak / 2, 0];
});

function trendBarHeight(value: number): string {
    if (trendPeak.value === 0) {
        return '0%';
    }

    // A non-zero amount always gets a sliver, so that "small" never renders
    // identically to "none".
    const pct = (value / trendPeak.value) * 100;

    return `${value > 0 ? Math.max(pct, 1.5) : 0}%`;
}

/** The currency symbol on its own, taken from a formatted zero. */
const currencySymbol = computed(() =>
    formatMoney(0, currency.value).replace(/[\d.,\s]/g, ''),
);

/**
 * Axis labels only, where a full amount would not fit. Keeps the magnitude
 * legible without implying the precision the KPI tiles carry.
 */
function fmtCompact(amountInCents: number): string {
    const major = amountInCents / 100;
    const abs = Math.abs(major);
    const symbol = currencySymbol.value;

    if (abs >= 1_000_000) {
        return `${symbol}${(major / 1_000_000).toFixed(1)}M`;
    }

    if (abs >= 1_000) {
        return `${symbol}${(major / 1_000).toFixed(1)}k`;
    }

    return `${symbol}${Math.round(major)}`;
}

const totalStatusCount = computed(() =>
    props.statusBreakdown.reduce((sum, s) => sum + s.count, 0),
);
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-foreground">
                Dashboard
            </h2>
        </template>

        <!-- No active organization (e.g. a member removed from every org) -->
        <div v-if="!hasOrganization" class="px-4 py-16 sm:px-0">
            <div
                class="mx-auto max-w-md rounded-xl border-2 border-dashed border-input px-6 py-12 text-center"
            >
                <h3 class="text-base font-semibold text-foreground">
                    You're not part of any organization yet
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Ask an organization owner to invite you, or create your own
                    to get started.
                </p>
                <Link
                    :href="teamsCreate.url()"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90"
                >
                    Create organization
                </Link>
            </div>
        </div>

        <div v-else class="px-4 py-4 sm:px-0">
            <!-- Header with Period Selector -->
            <div
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        {{ organizationName }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Business overview and analytics
                    </p>
                </div>
                <select
                    :value="period"
                    class="rounded-md border-input text-sm shadow-sm focus:border-ring focus:ring-ring"
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
                <div class="rounded-lg bg-card p-5 shadow">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-muted-foreground">
                            Total Invoiced
                        </div>
                        <svg
                            class="size-5 text-primary"
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
                    <div class="mt-2 text-2xl font-bold text-foreground">
                        {{ fmt(stats.total_revenue) }}
                    </div>
                    <div class="mt-1 text-sm text-muted-foreground">
                        {{ stats.invoice_count }}
                        {{ stats.invoice_count === 1 ? 'invoice' : 'invoices' }}
                    </div>
                </div>

                <!-- Collected -->
                <div class="rounded-lg bg-card p-5 shadow">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-muted-foreground">
                            Collected
                        </div>
                        <svg
                            class="size-5 text-success"
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
                    <!--
                        Semantic colour has to follow the number. A flat green
                        made a collected total of zero -- alongside overdue
                        invoices and a 0% collection rate -- look exactly like
                        a healthy figure.
                    -->
                    <div
                        class="mt-2 text-2xl font-bold"
                        :class="
                            stats.total_collected > 0
                                ? 'text-success'
                                : 'text-foreground'
                        "
                    >
                        {{ fmt(stats.total_collected) }}
                    </div>
                    <div class="mt-1 text-sm text-muted-foreground">
                        {{ stats.collection_rate }}% collection rate
                    </div>
                </div>

                <!-- Outstanding -->
                <div class="rounded-lg bg-card p-5 shadow">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-muted-foreground">
                            Outstanding
                        </div>
                        <svg
                            class="size-5 text-warning"
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
                    <!-- Nothing outstanding is good news; amber would warn about it. -->
                    <div
                        class="mt-2 text-2xl font-bold"
                        :class="
                            stats.total_outstanding > 0
                                ? 'text-warning'
                                : 'text-foreground'
                        "
                    >
                        {{ fmt(stats.total_outstanding) }}
                    </div>
                    <div
                        class="mt-1 text-sm"
                        :class="
                            stats.overdue_count > 0
                                ? 'font-medium text-destructive'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ stats.overdue_count }} overdue<span
                            v-if="!isAllTime"
                            class="font-normal text-faint-foreground"
                        >
                            · all time</span
                        >
                    </div>
                </div>

                <!-- Customers & Estimates -->
                <div class="rounded-lg bg-card p-5 shadow">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-muted-foreground">
                            Customers<span
                                v-if="!isAllTime"
                                class="text-faint-foreground"
                            >
                                (all time)</span
                            >
                        </div>
                        <svg
                            class="size-5 text-primary"
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
                    <div class="mt-2 text-2xl font-bold text-foreground">
                        {{ customerCount }}
                    </div>
                    <div class="mt-1 text-sm text-muted-foreground">
                        {{ estimateStats.count }}
                        {{
                            estimateStats.count === 1 ? 'estimate' : 'estimates'
                        }}
                        ({{ estimateStats.accepted }} accepted)
                    </div>
                </div>
            </div>

            <!-- Per-currency amounts, shown whenever the KPIs above involved
                 a conversion -- including a single, wholly foreign currency. -->
            <div
                v-if="hasConvertedAmounts"
                class="mb-8 rounded-lg bg-card p-5 shadow"
            >
                <div class="mb-3 flex items-baseline justify-between gap-2">
                    <h3 class="text-sm font-semibold text-foreground">
                        {{
                            currencyBreakdown.length === 1
                                ? 'Original Currency'
                                : 'Original Currencies'
                        }}
                    </h3>
                    <span class="text-xs text-muted-foreground">
                        KPI totals above are converted to
                        {{ currency }} using the rate at invoice date.
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="row in currencyBreakdown"
                        :key="row.currency"
                        class="rounded-md border border-border bg-muted px-3 py-2"
                    >
                        <div
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            {{ row.currency }} · {{ row.invoice_count }}
                            {{ row.invoice_count === 1 ? 'inv' : 'invs' }}
                        </div>
                        <div class="text-sm font-semibold text-foreground">
                            {{ formatMoney(row.total, row.currency) }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ formatMoney(row.collected, row.currency) }}
                            collected
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Breakdown + Monthly Trend -->
            <!--
                Grid stretches its items by default, so a one-line Status
                Breakdown inherited the height of the trend chart beside it and
                rendered as a card of whitespace. Each card now ends where its
                content does.
            -->
            <div
                class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start"
            >
                <!-- Status Breakdown -->
                <div class="rounded-lg bg-card p-5 shadow">
                    <h3 class="mb-4 text-sm font-semibold text-foreground">
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
                                    <span
                                        class="text-sm text-muted-foreground"
                                        >{{ item.count }}</span
                                    >
                                </div>
                                <span
                                    class="text-sm font-medium text-foreground"
                                    >{{ fmt(item.total) }}</span
                                >
                            </div>
                        </template>
                        <p
                            v-if="totalStatusCount === 0"
                            class="py-4 text-center text-sm text-faint-foreground"
                        >
                            No invoices in this period
                        </p>
                    </div>
                </div>

                <!-- 6-Month Trend -->
                <div class="rounded-lg bg-card p-5 shadow lg:col-span-2">
                    <h3 class="mb-4 text-sm font-semibold text-foreground">
                        6-Month Trend
                    </h3>
                    <!--
                        Bars used to be sized as a percentage of the tallest
                        month with nothing to read them against, so a bar could
                        stand for any amount at all. They now share one axis,
                        scaled to the larger of the two series, with the
                        amounts written next to it.
                    -->
                    <div v-if="trendIsEmpty" class="py-10 text-center">
                        <p class="text-sm text-muted-foreground">
                            Nothing invoiced in the last six months
                        </p>
                        <p class="mt-1 text-xs text-faint-foreground">
                            The chart appears once there is activity to plot.
                        </p>
                    </div>

                    <div v-else class="flex gap-3">
                        <!-- Y axis -->
                        <div
                            class="flex h-40 flex-col justify-between py-0.5 text-right text-[11px] text-faint-foreground tabular-nums"
                            aria-hidden="true"
                        >
                            <span v-for="tick in trendTicks" :key="tick">{{
                                fmtCompact(tick)
                            }}</span>
                        </div>

                        <div class="relative flex-1">
                            <!-- Gridlines, aligned with the axis labels. -->
                            <div
                                class="pointer-events-none absolute inset-x-0 top-0 flex h-40 flex-col justify-between"
                                aria-hidden="true"
                            >
                                <div
                                    v-for="tick in trendTicks"
                                    :key="tick"
                                    class="border-t border-dashed border-border"
                                />
                            </div>

                            <div class="relative flex h-40 items-end gap-2">
                                <div
                                    v-for="month in monthlyTrend"
                                    :key="month.label"
                                    class="flex h-full flex-1 items-end justify-center gap-1"
                                >
                                    <!--
                                        Both bars always render, so a month
                                        that collected nothing is visibly
                                        different from one with no data.
                                    -->
                                    <div
                                        class="w-1/2 max-w-[1.5rem] rounded-t bg-chart-1 transition-all"
                                        :style="{
                                            height: trendBarHeight(
                                                month.invoiced,
                                            ),
                                        }"
                                        :title="`${month.label} invoiced: ${fmt(month.invoiced)}`"
                                    />
                                    <div
                                        class="w-1/2 max-w-[1.5rem] rounded-t bg-chart-2 transition-all"
                                        :style="{
                                            height: trendBarHeight(
                                                month.collected,
                                            ),
                                        }"
                                        :title="`${month.label} collected: ${fmt(month.collected)}`"
                                    />
                                </div>
                            </div>

                            <div class="mt-1 flex gap-2">
                                <div
                                    v-for="month in monthlyTrend"
                                    :key="month.label"
                                    class="flex-1 text-center"
                                >
                                    <div class="text-xs text-muted-foreground">
                                        {{ month.short }}
                                    </div>
                                    <!--
                                        The amounts were reachable only by
                                        hovering, which is no use on a touch
                                        screen and invisible to a screen reader.
                                    -->
                                    <div
                                        class="text-[11px] text-faint-foreground tabular-nums"
                                    >
                                        {{ fmtCompact(month.invoiced) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="mt-3 flex items-center gap-4 text-xs text-muted-foreground"
                    >
                        <span class="flex items-center gap-1"
                            ><span
                                class="inline-block h-3 w-3 rounded bg-chart-1"
                            />
                            Invoiced</span
                        >
                        <span class="flex items-center gap-1"
                            ><span
                                class="inline-block h-3 w-3 rounded bg-chart-2"
                            />
                            Collected</span
                        >
                    </div>
                </div>
            </div>

            <!-- Top Customers + Overdue Invoices -->
            <div
                class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start"
            >
                <!-- Top Customers -->
                <div class="rounded-lg bg-card p-5 shadow">
                    <h3 class="mb-4 text-sm font-semibold text-foreground">
                        Top Customers
                    </h3>
                    <div v-if="topCustomers.length > 0" class="space-y-3">
                        <div
                            v-for="cust in topCustomers"
                            :key="cust.name"
                            class="flex items-center justify-between"
                        >
                            <div>
                                <div
                                    class="text-sm font-medium text-foreground"
                                >
                                    {{ cust.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ cust.invoice_count }}
                                    {{
                                        cust.invoice_count === 1
                                            ? 'invoice'
                                            : 'invoices'
                                    }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div
                                    class="text-sm font-medium text-foreground"
                                >
                                    {{ fmt(cust.total) }}
                                </div>
                                <div
                                    v-if="cust.outstanding > 0"
                                    class="text-xs text-warning"
                                >
                                    {{ fmt(cust.outstanding) }} due
                                </div>
                                <div v-else class="text-xs text-success">
                                    Fully paid
                                </div>
                            </div>
                        </div>
                    </div>
                    <p
                        v-else
                        class="py-4 text-center text-sm text-faint-foreground"
                    >
                        No customer data in this period
                    </p>
                </div>

                <!-- Overdue Invoices -->
                <div class="rounded-lg bg-card p-5 shadow">
                    <h3 class="mb-4 text-sm font-semibold text-foreground">
                        Overdue Invoices<span
                            v-if="!isAllTime"
                            class="font-normal text-faint-foreground"
                        >
                            (all time)</span
                        >
                        <!--
                            The list is capped at five by the controller, so its
                            length is not the overdue count. Using it made the
                            badge disagree with the Outstanding tile on the same
                            screen -- "11 overdue" above, "5" here.
                        -->
                        <span
                            v-if="stats.overdue_count > 0"
                            class="ml-2 inline-flex items-center rounded-full bg-destructive-soft px-2 py-0.5 text-xs font-medium text-destructive-soft-foreground"
                        >
                            {{ stats.overdue_count }}
                        </span>
                    </h3>
                    <div v-if="overdueInvoices.length > 0" class="space-y-3">
                        <div
                            v-for="inv in overdueInvoices"
                            :key="inv.id"
                            class="-mx-2 flex items-center justify-between rounded px-2 py-1 hover:bg-muted"
                        >
                            <div>
                                <div
                                    class="text-sm font-medium text-foreground"
                                >
                                    {{ inv.invoice_number }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ inv.customer_name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div
                                    class="text-sm font-medium text-destructive"
                                >
                                    {{
                                        formatMoney(
                                            inv.remaining_balance,
                                            inv.currency,
                                        )
                                    }}
                                </div>
                                <div class="text-xs text-destructive">
                                    Due {{ inv.due_at_human }}
                                </div>
                            </div>
                        </div>

                        <!--
                            Say so when the list is a subset, rather than
                            leaving the badge and the visible rows to disagree.
                            Deliberately not a link: the invoice list filters by
                            status, and overdue is derived from due_at rather
                            than being one, so there is no URL to send them to.
                        -->
                        <p
                            v-if="stats.overdue_count > overdueInvoices.length"
                            class="pt-1 text-xs text-muted-foreground"
                        >
                            Showing the {{ overdueInvoices.length }} most
                            overdue of {{ stats.overdue_count }}
                        </p>
                    </div>
                    <div v-else class="py-4 text-center">
                        <svg
                            class="mx-auto size-8 text-success"
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
                        <p class="mt-1 text-sm text-faint-foreground">
                            No overdue invoices
                        </p>
                    </div>
                </div>
            </div>

            <!-- Recent Invoices + Recent Payments -->
            <div
                class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start"
            >
                <!-- Recent Invoices -->
                <div class="rounded-lg bg-card p-5 shadow">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-foreground">
                            Recent Invoices<span
                                v-if="!isAllTime"
                                class="font-normal text-faint-foreground"
                            >
                                (all time)</span
                            >
                        </h3>
                    </div>
                    <div v-if="recentInvoices.length > 0" class="space-y-3">
                        <div
                            v-for="inv in recentInvoices"
                            :key="inv.id"
                            class="-mx-2 flex items-center justify-between rounded px-2 py-1 hover:bg-muted"
                        >
                            <div>
                                <div
                                    class="text-sm font-medium text-foreground"
                                >
                                    {{ inv.invoice_number }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ inv.customer_name }} &middot;
                                    {{ inv.issued_at }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div
                                    class="text-sm font-medium text-foreground"
                                >
                                    {{ formatMoney(inv.total, inv.currency) }}
                                </div>
                                <StatusBadge :status="inv.status" />
                            </div>
                        </div>
                    </div>
                    <p
                        v-else
                        class="py-4 text-center text-sm text-faint-foreground"
                    >
                        No invoices yet
                    </p>
                </div>

                <!-- Recent Payments -->
                <div class="rounded-lg bg-card p-5 shadow">
                    <h3 class="mb-4 text-sm font-semibold text-foreground">
                        Recent Payments<span
                            v-if="!isAllTime"
                            class="font-normal text-faint-foreground"
                        >
                            (all time)</span
                        >
                    </h3>
                    <div v-if="recentPayments.length > 0" class="space-y-3">
                        <div
                            v-for="payment in recentPayments"
                            :key="payment.id"
                            class="flex items-center justify-between"
                        >
                            <div>
                                <div
                                    class="text-sm font-medium text-foreground"
                                >
                                    {{ payment.invoice_number }}
                                </div>
                                <div class="text-xs text-muted-foreground">
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
                                <div class="text-sm font-medium text-success">
                                    +{{
                                        formatMoney(
                                            payment.amount,
                                            payment.currency,
                                        )
                                    }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ payment.payment_date }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <p
                        v-else
                        class="py-4 text-center text-sm text-faint-foreground"
                    >
                        No payments recorded yet
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <a
                    href="/invoices/create"
                    class="flex items-center gap-3 rounded-lg bg-card p-4 shadow transition-colors hover:bg-primary-soft/50"
                >
                    <svg
                        class="size-5 text-primary"
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
                    <span class="text-sm font-medium text-soft-foreground"
                        >New Invoice</span
                    >
                </a>
                <a
                    href="/estimates/create"
                    class="flex items-center gap-3 rounded-lg bg-card p-4 shadow transition-colors hover:bg-success-soft/50"
                >
                    <svg
                        class="size-5 text-success"
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
                    <span class="text-sm font-medium text-soft-foreground"
                        >New Estimate</span
                    >
                </a>
                <a
                    href="/customers"
                    class="flex items-center gap-3 rounded-lg bg-card p-4 shadow transition-colors hover:bg-primary-soft/50"
                >
                    <svg
                        class="size-5 text-primary"
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
                    <span class="text-sm font-medium text-soft-foreground"
                        >Customers</span
                    >
                </a>
                <a
                    href="/settings"
                    class="flex items-center gap-3 rounded-lg bg-card p-4 shadow transition-colors hover:bg-muted"
                >
                    <svg
                        class="size-5 text-soft-foreground"
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
                    <span class="text-sm font-medium text-soft-foreground"
                        >Settings</span
                    >
                </a>
            </div>
        </div>
    </AppLayout>
</template>
