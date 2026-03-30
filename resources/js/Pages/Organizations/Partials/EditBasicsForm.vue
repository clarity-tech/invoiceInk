<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { Organization, CountryInfo } from '../types';

const props = defineProps<{
    organization: Organization;
    countries: CountryInfo[];
    currencies: Record<string, string>;
}>();

const recentlySuccessful = ref(false);

const form = useForm({
    name: props.organization.name ?? '',
    phone: props.organization.phone ?? '',
    emails:
        (props.organization.emails?.map((e) => e.email) ?? ['']).length > 0
            ? (props.organization.emails?.map((e) => e.email) ?? [''])
            : [''],
    currency: props.organization.currency ?? '',
    country_code: props.organization.country_code ?? '',
    financial_year_type: props.organization.financial_year_type ?? '',
    financial_year_start_month:
        props.organization.financial_year_start_month ?? 4,
    financial_year_start_day: props.organization.financial_year_start_day ?? 1,
    tax_number: props.organization.tax_number ?? '',
    registration_number: props.organization.registration_number ?? '',
    website: props.organization.website ?? '',
    notes: props.organization.notes ?? '',
});

const selectedCountry = computed(
    () => props.countries.find((c) => c.value === form.country_code) ?? null,
);

const availableCurrencies = computed(() =>
    selectedCountry.value
        ? selectedCountry.value.supported_currencies
        : props.currencies,
);

watch(
    () => form.country_code,
    (newCode) => {
        if (!newCode) return;
        const country = props.countries.find((c) => c.value === newCode);
        if (country) {
            form.currency = country.currency;
            form.financial_year_type = country.default_financial_year;
        }
    },
);

function addEmail() {
    form.emails.push('');
}

function removeEmail(index: number) {
    if (form.emails.length > 1) {
        form.emails.splice(index, 1);
    }
}

function submit() {
    form.put(`/organizations/${props.organization.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            recentlySuccessful.value = true;
            setTimeout(() => (recentlySuccessful.value = false), 2000);
        },
    });
}
</script>

<template>
    <form
        @submit.prevent="submit"
        class="rounded-xl border border-gray-200 bg-white"
    >
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">
                General Information
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Basic details about your organization.
            </p>
        </div>

        <div class="space-y-6 p-6">
            <!-- Name -->
            <div>
                <label
                    for="org-name"
                    class="block text-sm font-medium text-gray-700"
                    >Name *</label
                >
                <input
                    id="org-name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                    {{ form.errors.name }}
                </p>
            </div>

            <!-- Phone + Tax Number -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="org-phone"
                        class="block text-sm font-medium text-gray-700"
                        >Phone</label
                    >
                    <input
                        id="org-phone"
                        v-model="form.phone"
                        type="tel"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                    <p
                        v-if="form.errors.phone"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.phone }}
                    </p>
                </div>
                <div>
                    <label
                        for="org-tax"
                        class="block text-sm font-medium text-gray-700"
                        >Tax Number</label
                    >
                    <input
                        id="org-tax"
                        v-model="form.tax_number"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
            </div>

            <!-- Registration + Website -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="org-reg"
                        class="block text-sm font-medium text-gray-700"
                        >Registration Number</label
                    >
                    <input
                        id="org-reg"
                        v-model="form.registration_number"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="org-web"
                        class="block text-sm font-medium text-gray-700"
                        >Website</label
                    >
                    <input
                        id="org-web"
                        v-model="form.website"
                        type="url"
                        placeholder="https://example.com"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                    <p
                        v-if="form.errors.website"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.website }}
                    </p>
                </div>
            </div>

            <!-- Country + Currency -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="org-country"
                        class="block text-sm font-medium text-gray-700"
                        >Country *</label
                    >
                    <select
                        id="org-country"
                        v-model="form.country_code"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    >
                        <option value="">Select country</option>
                        <option
                            v-for="c in countries"
                            :key="c.value"
                            :value="c.value"
                        >
                            {{ c.label }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.country_code"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.country_code }}
                    </p>
                </div>
                <div>
                    <label
                        for="org-currency"
                        class="block text-sm font-medium text-gray-700"
                        >Currency *</label
                    >
                    <select
                        id="org-currency"
                        v-model="form.currency"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    >
                        <option value="">Select currency</option>
                        <option
                            v-for="(label, code) in availableCurrencies"
                            :key="code"
                            :value="code"
                        >
                            {{ label }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.currency"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.currency }}
                    </p>
                </div>
            </div>

            <!-- Financial Year (conditional) -->
            <div
                v-if="selectedCountry"
                class="grid grid-cols-1 gap-6 md:grid-cols-2"
            >
                <div>
                    <label
                        for="org-fy"
                        class="block text-sm font-medium text-gray-700"
                        >Financial Year</label
                    >
                    <select
                        id="org-fy"
                        v-model="form.financial_year_type"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    >
                        <option
                            v-for="(
                                label, value
                            ) in selectedCountry.financial_year_options"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>
                <div class="flex items-end">
                    <div
                        class="rounded-lg bg-blue-50 p-3 text-sm text-blue-800"
                    >
                        <strong>Tax:</strong>
                        {{ selectedCountry.tax_system.name }}
                        <span class="ml-2 text-blue-600"
                            >({{
                                selectedCountry.tax_system.rates
                                    .slice(0, 3)
                                    .join(', ')
                            }})</span
                        >
                    </div>
                </div>
            </div>

            <!-- Emails -->
            <div>
                <label class="block text-sm font-medium text-gray-700"
                    >Email Addresses *</label
                >
                <div
                    v-for="(email, index) in form.emails"
                    :key="index"
                    class="mt-2 flex items-center gap-2"
                >
                    <input
                        v-model="form.emails[index]"
                        type="email"
                        placeholder="email@company.com"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                    <button
                        v-if="index > 0"
                        type="button"
                        class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                        @click="removeEmail(index)"
                    >
                        <svg
                            class="size-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <button
                    type="button"
                    class="mt-2 text-sm font-medium text-brand-600 hover:text-brand-500"
                    @click="addEmail"
                >
                    + Add email
                </button>
                <p
                    v-if="form.errors['emails.0']"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors['emails.0'] }}
                </p>
                <p v-if="form.errors.emails" class="mt-1 text-sm text-red-600">
                    {{ form.errors.emails }}
                </p>
            </div>

            <!-- Notes -->
            <div>
                <label
                    for="org-notes"
                    class="block text-sm font-medium text-gray-700"
                    >Notes</label
                >
                <textarea
                    id="org-notes"
                    v-model="form.notes"
                    rows="3"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                />
            </div>
        </div>

        <!-- Footer -->
        <div
            class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4"
        >
            <span v-show="recentlySuccessful" class="text-sm text-green-600"
                >Saved.</span
            >
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
            >
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
        </div>
    </form>
</template>
