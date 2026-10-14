<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTaxLabel, type TaxLabels } from '@/composables/useTaxLabel';

interface Location {
    id: number;
    name: string;
    tax_id: string | null;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    state: string;
    country: string;
    postal_code: string;
}

interface BankDetails {
    account_name?: string;
    account_number?: string;
    bank_name?: string;
    ifsc?: string;
    branch?: string;
    swift?: string;
    pan?: string;
}

interface Organization {
    id: number;
    name: string;
    company_name: string | null;
    phone: string | null;
    formatted_phone: string | null;
    emails: { email: string; name: string }[];
    currency: string | null;
    country_code: string | null;
    financial_year_type: string | null;
    tax_number: string | null;
    registration_number: string | null;
    website: string | null;
    notes: string | null;
    bank_details: BankDetails | null;
    logo_url: string | null;
    primary_location: Location | null;
}

interface NumberingSeries {
    id: number;
    name: string;
    prefix: string;
    format_pattern: string;
    current_number: number;
    reset_frequency: string;
    is_active: boolean;
    is_default: boolean;
}

const props = defineProps<{
    organization: Organization;
    numberingSeries: NumberingSeries[];
    canManage: boolean;
    taxLabels: TaxLabels;
}>();

const org = props.organization;

const locationCountry = computed(() => org.primary_location?.country ?? null);
const { label: taxLabel } = useTaxLabel(locationCountry, props.taxLabels);

const hasBank = !!(
    org.bank_details?.bank_name ||
    org.bank_details?.account_number ||
    org.bank_details?.ifsc
);

function previewNumber(series: NumberingSeries): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const months = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec',
    ];
    const nextNum = series.current_number + 1;

    let result = series.format_pattern;
    result = result.replace('{PREFIX}', series.prefix);
    result = result.replace('{YEAR}', String(year));
    result = result.replace('{YEAR:2}', String(year).slice(-2));
    result = result.replace('{MONTH}', month);
    result = result.replace('{MONTH:3}', months[now.getMonth()]);
    result = result.replace('{DAY}', day);

    // Financial year: Apr-Mar by default
    const fyStartYear = now.getMonth() >= 3 ? year : year - 1;
    const fyEndYear = fyStartYear + 1;
    result = result.replace(
        '{FY}',
        `${fyStartYear}-${String(fyEndYear).slice(-2)}`,
    );
    result = result.replace('{FY_START}', String(fyStartYear));
    result = result.replace('{FY_END}', String(fyEndYear));
    result = result.replace(
        '{FY_FULL}',
        `${fyStartYear}-${String(fyEndYear).slice(-2)}`,
    );
    result = result.replace('{FY_RANGE}', `${fyStartYear}-${fyEndYear}`);

    // Sequence with padding
    result = result.replace(/\{SEQUENCE:(\d+)\}/g, (_, pad) =>
        String(nextNum).padStart(Number(pad), '0'),
    );
    result = result.replace('{SEQUENCE}', String(nextNum));

    return result;
}
</script>

<template>
    <AppLayout :title="org.name">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-foreground">
                {{ org.name }}
            </h2>
        </template>

        <div class="px-4 py-4 sm:px-0">
            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div class="flex items-center gap-5">
                    <div
                        v-if="org.logo_url"
                        class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-border bg-paper shadow-sm"
                    >
                        <img
                            :src="org.logo_url"
                            :alt="org.name"
                            class="size-full object-contain p-1"
                        />
                    </div>
                    <div
                        v-else
                        class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 shadow-sm"
                    >
                        <span class="text-2xl font-bold text-white">{{
                            org.name.charAt(0)
                        }}</span>
                    </div>
                    <div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-foreground"
                        >
                            {{ org.name }}
                        </h1>
                        <p
                            v-if="
                                org.company_name &&
                                org.company_name !== org.name
                            "
                            class="text-sm text-muted-foreground"
                        >
                            {{ org.company_name }}
                        </p>
                        <div
                            class="mt-1 flex items-center gap-3 text-sm text-muted-foreground"
                        >
                            <span
                                v-if="org.currency"
                                class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-soft-foreground"
                                >{{ org.currency }}</span
                            >
                            <span
                                v-if="org.country_code"
                                class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-soft-foreground"
                                >{{ org.country_code }}</span
                            >
                        </div>
                    </div>
                </div>
                <Link
                    v-if="canManage"
                    :href="`/organizations/${org.id}/edit`"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                >
                    <Pencil class="size-4" />
                    Edit Settings
                </Link>
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Contact Info -->
                <section class="rounded-xl border border-border bg-card p-6">
                    <h2
                        class="mb-4 text-sm font-semibold tracking-wider text-faint-foreground uppercase"
                    >
                        Contact
                    </h2>
                    <dl class="space-y-4">
                        <div v-if="org.emails?.length">
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Email
                            </dt>
                            <dd
                                v-for="(contact, i) in org.emails"
                                :key="i"
                                class="text-sm text-foreground"
                            >
                                {{ contact.email }}
                            </dd>
                        </div>
                        <div v-if="org.formatted_phone">
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Phone
                            </dt>
                            <dd class="text-sm text-foreground">
                                {{ org.formatted_phone }}
                            </dd>
                        </div>
                        <div v-if="org.website">
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Website
                            </dt>
                            <dd class="text-sm">
                                <a
                                    :href="org.website"
                                    target="_blank"
                                    class="text-primary hover:underline"
                                    >{{ org.website }}</a
                                >
                            </dd>
                        </div>
                        <div v-if="org.tax_number">
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Tax Number
                            </dt>
                            <dd class="font-mono text-sm text-foreground">
                                {{ org.tax_number }}
                            </dd>
                        </div>
                        <div v-if="org.registration_number">
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Registration Number
                            </dt>
                            <dd class="font-mono text-sm text-foreground">
                                {{ org.registration_number }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <!-- Primary Location -->
                <section class="rounded-xl border border-border bg-card p-6">
                    <h2
                        class="mb-4 text-sm font-semibold tracking-wider text-faint-foreground uppercase"
                    >
                        Primary Location
                    </h2>
                    <div
                        v-if="org.primary_location"
                        class="space-y-1 text-sm text-foreground"
                    >
                        <p v-if="org.primary_location.name" class="font-medium">
                            {{ org.primary_location.name }}
                        </p>
                        <p>{{ org.primary_location.address_line_1 }}</p>
                        <p v-if="org.primary_location.address_line_2">
                            {{ org.primary_location.address_line_2 }}
                        </p>
                        <p>
                            {{ org.primary_location.city }},
                            {{ org.primary_location.state }}
                            {{ org.primary_location.postal_code }}
                        </p>
                        <p
                            v-if="org.primary_location.tax_id"
                            class="mt-2 font-mono text-xs text-muted-foreground"
                        >
                            {{ taxLabel }}: {{ org.primary_location.tax_id }}
                        </p>
                    </div>
                    <p v-else class="text-sm text-faint-foreground italic">
                        No location configured.
                        <Link
                            v-if="canManage"
                            :href="`/organizations/${org.id}/edit?tab=location`"
                            class="text-primary not-italic hover:underline"
                            >Add one</Link
                        >
                    </p>
                </section>

                <!-- Bank Details -->
                <section class="rounded-xl border border-border bg-card p-6">
                    <h2
                        class="mb-4 text-sm font-semibold tracking-wider text-faint-foreground uppercase"
                    >
                        Bank Details
                    </h2>
                    <div v-if="hasBank" class="space-y-3 text-sm">
                        <div v-if="org.bank_details?.bank_name">
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Bank
                            </dt>
                            <dd class="text-foreground">
                                {{ org.bank_details.bank_name }}
                            </dd>
                        </div>
                        <div v-if="org.bank_details?.account_number">
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Account
                            </dt>
                            <dd class="font-mono text-foreground">
                                {{ org.bank_details.account_name }} &mdash;
                                {{ org.bank_details.account_number }}
                            </dd>
                        </div>
                        <div v-if="org.bank_details?.ifsc">
                            <dt
                                class="text-xs font-medium text-muted-foreground"
                            >
                                IFSC
                            </dt>
                            <dd class="font-mono text-foreground">
                                {{ org.bank_details.ifsc }}
                            </dd>
                        </div>
                    </div>
                    <p v-else class="text-sm text-faint-foreground italic">
                        No bank details configured.
                        <Link
                            v-if="canManage"
                            :href="`/organizations/${org.id}/edit?tab=bank`"
                            class="text-primary not-italic hover:underline"
                            >Add details</Link
                        >
                    </p>
                </section>

                <!-- Numbering Series -->
                <section class="rounded-xl border border-border bg-card p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h2
                            class="text-sm font-semibold tracking-wider text-faint-foreground uppercase"
                        >
                            Numbering Series
                        </h2>
                        <Link
                            v-if="canManage"
                            :href="`/organizations/${org.id}/edit?tab=numbering`"
                            class="text-xs font-medium text-primary hover:text-primary-soft-foreground"
                        >
                            Manage
                        </Link>
                    </div>
                    <div v-if="numberingSeries.length" class="space-y-3">
                        <div
                            v-for="series in numberingSeries"
                            :key="series.id"
                            class="flex items-center justify-between rounded-lg bg-muted px-4 py-3"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                    >
                                        {{ series.name }}
                                    </span>
                                    <span
                                        v-if="series.is_default"
                                        class="rounded-full bg-primary-soft px-2 py-0.5 text-xs font-medium text-primary-soft-foreground"
                                    >
                                        Default
                                    </span>
                                    <span
                                        v-if="!series.is_active"
                                        class="rounded-full bg-destructive-soft px-2 py-0.5 text-xs font-medium text-destructive-soft-foreground"
                                    >
                                        Inactive
                                    </span>
                                </div>
                                <p class="mt-1 font-mono text-xs text-primary">
                                    Next: {{ previewNumber(series) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-faint-foreground">
                                    Resets:
                                    {{
                                        series.reset_frequency.replace('_', ' ')
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-faint-foreground italic">
                        No numbering series configured. One will be created
                        automatically with your first invoice.
                    </p>
                </section>

                <!-- Notes -->
                <section
                    v-if="org.notes"
                    class="rounded-xl border border-border bg-card p-6"
                >
                    <h2
                        class="mb-4 text-sm font-semibold tracking-wider text-faint-foreground uppercase"
                    >
                        Notes
                    </h2>
                    <p class="text-sm whitespace-pre-wrap text-soft-foreground">
                        {{ org.notes }}
                    </p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
