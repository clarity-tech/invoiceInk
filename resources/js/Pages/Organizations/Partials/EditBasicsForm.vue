<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PhoneInput from '@/Components/PhoneInput.vue';
import { Checkbox } from '@/Components/ui/checkbox';
import type { Organization, CountryInfo } from '../types';

const props = defineProps<{
    organization: Organization;
    countries: CountryInfo[];
    currencies: Record<string, string>;
}>();

const recentlySuccessful = ref(false);

const form = useForm({
    name: props.organization.name ?? '',
    company_name: props.organization.company_name ?? '',
    phone: props.organization.phone ?? '',
    phone_country: props.organization.phone_country ?? '',
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
    ai_drafting_enabled: props.organization.ai_drafting_enabled ?? false,
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
        class="rounded-xl border border-border bg-card"
    >
        <div class="border-b border-border px-6 py-4">
            <h2 class="text-lg font-semibold text-foreground">
                General Information
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Basic details about your organization.
            </p>
        </div>

        <div class="space-y-6 p-6">
            <!-- Organization name (switcher label) -->
            <div>
                <label
                    for="org-name"
                    class="block text-sm font-medium text-soft-foreground"
                    >Organization name *</label
                >
                <input
                    id="org-name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                />
                <p class="mt-1 text-xs text-muted-foreground">
                    Shown in the organization switcher. Internal label only.
                </p>
                <p
                    v-if="form.errors.name"
                    class="mt-1 text-sm text-destructive"
                >
                    {{ form.errors.name }}
                </p>
            </div>

            <!-- Company name (legal name on documents) -->
            <div>
                <label
                    for="org-company-name"
                    class="block text-sm font-medium text-soft-foreground"
                    >Company name</label
                >
                <input
                    id="org-company-name"
                    v-model="form.company_name"
                    type="text"
                    class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                />
                <p class="mt-1 text-xs text-muted-foreground">
                    Your legal/business name shown on invoices and documents.
                </p>
                <p
                    v-if="form.errors.company_name"
                    class="mt-1 text-sm text-destructive"
                >
                    {{ form.errors.company_name }}
                </p>
            </div>

            <!-- Phone + Tax Number -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="org-phone"
                        class="block text-sm font-medium text-soft-foreground"
                        >Phone</label
                    >
                    <PhoneInput
                        id="org-phone"
                        v-model="form.phone"
                        v-model:country="form.phone_country"
                        class="mt-1"
                        :default-country="form.country_code || 'US'"
                        :error="form.errors.phone"
                    />
                </div>
                <div>
                    <label
                        for="org-tax"
                        class="block text-sm font-medium text-soft-foreground"
                        >Tax Number</label
                    >
                    <input
                        id="org-tax"
                        v-model="form.tax_number"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                </div>
            </div>

            <!-- Registration + Website -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="org-reg"
                        class="block text-sm font-medium text-soft-foreground"
                        >Registration Number</label
                    >
                    <input
                        id="org-reg"
                        v-model="form.registration_number"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="org-web"
                        class="block text-sm font-medium text-soft-foreground"
                        >Website</label
                    >
                    <input
                        id="org-web"
                        v-model="form.website"
                        type="text"
                        placeholder="example.com"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                    <p
                        v-if="form.errors.website"
                        class="mt-1 text-sm text-destructive"
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
                        class="block text-sm font-medium text-soft-foreground"
                        >Country *</label
                    >
                    <select
                        id="org-country"
                        v-model="form.country_code"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
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
                        class="mt-1 text-sm text-destructive"
                    >
                        {{ form.errors.country_code }}
                    </p>
                </div>
                <div>
                    <label
                        for="org-currency"
                        class="block text-sm font-medium text-soft-foreground"
                        >Currency *</label
                    >
                    <select
                        id="org-currency"
                        v-model="form.currency"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
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
                        class="mt-1 text-sm text-destructive"
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
                        class="block text-sm font-medium text-soft-foreground"
                        >Financial Year</label
                    >
                    <select
                        id="org-fy"
                        v-model="form.financial_year_type"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
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
                        class="rounded-lg bg-info-soft/50 p-3 text-sm text-info-soft-foreground"
                    >
                        <strong>Tax:</strong>
                        {{ selectedCountry.tax_system.name }}
                        <span class="ml-2 text-info"
                            >({{
                                selectedCountry.tax_system.rates.join(', ')
                            }})</span
                        >
                    </div>
                </div>
            </div>

            <!-- Emails -->
            <div>
                <label class="block text-sm font-medium text-soft-foreground"
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
                        class="block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                    <button
                        v-if="index > 0"
                        type="button"
                        class="shrink-0 rounded-lg p-2 text-faint-foreground transition hover:bg-destructive-soft/50 hover:text-destructive"
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
                    class="mt-2 text-sm font-medium text-primary hover:text-primary/80"
                    @click="addEmail"
                >
                    + Add email
                </button>
                <p
                    v-if="form.errors['emails.0']"
                    class="mt-1 text-sm text-destructive"
                >
                    {{ form.errors['emails.0'] }}
                </p>
                <p
                    v-if="form.errors.emails"
                    class="mt-1 text-sm text-destructive"
                >
                    {{ form.errors.emails }}
                </p>
            </div>

            <!-- Notes -->
            <div>
                <label
                    for="org-notes"
                    class="block text-sm font-medium text-soft-foreground"
                    >Notes</label
                >
                <textarea
                    id="org-notes"
                    v-model="form.notes"
                    rows="3"
                    class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                />
            </div>

            <!-- AI drafting -->
            <div class="flex items-start gap-3">
                <Checkbox
                    id="org-ai-drafting"
                    v-model="form.ai_drafting_enabled"
                    class="mt-0.5"
                />
                <label for="org-ai-drafting" class="text-sm">
                    <span class="font-medium text-soft-foreground"
                        >Use AI to draft invoices, estimates and emails</span
                    >
                    <span class="mt-0.5 block text-muted-foreground">
                        Text, files and invoice details you use it with are sent
                        to an AI service. Nothing is saved or sent until someone
                        reviews it.
                    </span>
                </label>
            </div>
        </div>

        <!-- Footer -->
        <div
            class="flex items-center justify-end gap-3 border-t border-border bg-muted/50 px-6 py-4"
        >
            <span v-show="recentlySuccessful" class="text-sm text-success"
                >Saved.</span
            >
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-50"
            >
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
        </div>
    </form>
</template>
