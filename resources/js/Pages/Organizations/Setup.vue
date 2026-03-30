<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

interface CountryInfo {
    value: string;
    label: string;
    currency: string;
    financial_year_options: Record<string, string>;
    default_financial_year: string;
    supported_currencies: Record<string, string>;
    tax_system: { name: string; rates: string[] };
    recommended_numbering: string;
}

interface LocationData {
    name: string;
    gstin: string;
    address_line_1: string;
    address_line_2: string;
    city: string;
    state: string;
    postal_code: string;
}

interface OrganizationData {
    id: number;
    name: string;
    company_name: string;
    tax_number: string;
    registration_number: string;
    website: string;
    notes: string;
    phone: string;
    emails: string[];
    currency: string;
    country_code: string;
    financial_year_type: string;
    financial_year_start_month: number;
    financial_year_start_day: number;
    primary_location: LocationData | null;
}

const props = defineProps<{
    organization: OrganizationData;
    countries: CountryInfo[];
    currencies: Record<string, string>;
}>();

const currentStep = ref(1);
const totalSteps = 4;

const steps = [
    { title: 'Company Information', description: 'Basic company details' },
    { title: 'Primary Location', description: 'Main business address' },
    { title: 'Configuration', description: 'Currency & financial settings' },
    { title: 'Contact Details', description: 'Email and phone information' },
];

const form = useForm({
    step: 1,
    company_name: props.organization.company_name || '',
    tax_number: props.organization.tax_number || '',
    registration_number: props.organization.registration_number || '',
    website: props.organization.website || '',
    notes: props.organization.notes || '',
    location_name: props.organization.primary_location?.name || '',
    gstin: props.organization.primary_location?.gstin || '',
    address_line_1: props.organization.primary_location?.address_line_1 || '',
    address_line_2: props.organization.primary_location?.address_line_2 || '',
    city: props.organization.primary_location?.city || '',
    state: props.organization.primary_location?.state || '',
    postal_code: props.organization.primary_location?.postal_code || '',
    currency: props.organization.currency || '',
    country_code: props.organization.country_code || '',
    financial_year_type: props.organization.financial_year_type || '',
    financial_year_start_month:
        props.organization.financial_year_start_month ?? 4,
    financial_year_start_day: props.organization.financial_year_start_day ?? 1,
    emails: (props.organization.emails?.length
        ? props.organization.emails
        : ['']) as string[],
    phone: props.organization.phone || '',
});

const selectedCountry = computed(() => {
    if (!form.country_code) {
        return null;
    }

    return props.countries.find((c) => c.value === form.country_code) ?? null;
});

const availableCurrencies = computed(() => {
    return selectedCountry.value
        ? selectedCountry.value.supported_currencies
        : props.currencies;
});

watch(
    () => form.country_code,
    (newCode) => {
        if (!newCode) {
            return;
        }

        const country = props.countries.find((c) => c.value === newCode);

        if (country) {
            form.currency = country.currency;
            form.financial_year_type = country.default_financial_year;
        }
    },
);

function addEmail(): void {
    form.emails.push('');
}

function removeEmail(index: number): void {
    if (form.emails.length > 1) {
        form.emails.splice(index, 1);
    }
}

function submitStep(): void {
    form.step = currentStep.value;
    form.post(`/organization/setup/${props.organization.id}/step`, {
        preserveScroll: true,
        onSuccess: () => {
            if (currentStep.value < totalSteps) {
                currentStep.value++;
                form.clearErrors();
            } else {
                router.visit('/dashboard');
            }
        },
    });
}

function previousStep(): void {
    if (currentStep.value > 1) {
        currentStep.value--;
        form.clearErrors();
    }
}

function goToStep(step: number): void {
    if (step <= currentStep.value) {
        currentStep.value = step;
        form.clearErrors();
    }
}
</script>

<template>
    <AppLayout
        :title="`Organization Setup - Step ${currentStep} of ${totalSteps}`"
    >
        <div class="min-h-screen bg-gray-50 py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-bold text-gray-900">
                        Organization Setup
                    </h1>
                    <p class="mt-2 text-lg text-gray-600">
                        Let's configure your business
                    </p>
                </div>

                <!-- Progress Bar -->
                <div class="mb-8">
                    <!-- Mobile -->
                    <div class="mb-4 sm:hidden">
                        <p
                            class="text-center text-sm font-medium text-blue-600"
                        >
                            Step {{ currentStep }} of {{ totalSteps }}:
                            {{ steps[currentStep - 1].title }}
                        </p>
                        <div class="mt-2 flex gap-1">
                            <div
                                v-for="(step, i) in steps"
                                :key="i"
                                class="h-2 flex-1 rounded-full"
                                :class="
                                    i + 1 <= currentStep
                                        ? 'bg-blue-600'
                                        : 'bg-gray-300'
                                "
                            />
                        </div>
                    </div>

                    <!-- Desktop -->
                    <div
                        class="mb-2 hidden items-center justify-between sm:flex"
                    >
                        <div
                            v-for="(step, i) in steps"
                            :key="i"
                            class="flex items-center"
                            :class="i < steps.length - 1 ? 'flex-1' : ''"
                        >
                            <div class="relative">
                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-full border-2"
                                    :class="
                                        i + 1 <= currentStep
                                            ? 'border-blue-600 bg-blue-600 text-white'
                                            : 'border-gray-300 bg-white text-gray-500'
                                    "
                                    @click="goToStep(i + 1)"
                                >
                                    {{ i + 1 }}
                                </button>
                                <div
                                    class="absolute top-12 left-1/2 -translate-x-1/2 whitespace-nowrap"
                                >
                                    <p
                                        class="text-sm font-medium"
                                        :class="
                                            i + 1 <= currentStep
                                                ? 'text-blue-600'
                                                : 'text-gray-500'
                                        "
                                    >
                                        {{ step.title }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ step.description }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="i < steps.length - 1"
                                class="mx-4 h-0.5 flex-1"
                                :class="
                                    i + 1 < currentStep
                                        ? 'bg-blue-600'
                                        : 'bg-gray-300'
                                "
                            />
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="rounded-lg bg-white shadow-lg">
                    <div class="px-6 py-8">
                        <!-- Step 1: Company Information -->
                        <div v-if="currentStep === 1" class="space-y-6">
                            <div>
                                <h2
                                    class="mb-4 text-2xl font-semibold text-gray-900"
                                >
                                    Company Information
                                </h2>
                                <p class="mb-6 text-gray-600">
                                    Tell us about your company
                                </p>
                            </div>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Company Name *</label
                                    >
                                    <input
                                        id="company_name"
                                        v-model="form.company_name"
                                        type="text"
                                        placeholder="Your company name"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.company_name"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.company_name }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Tax Number</label
                                    >
                                    <input
                                        id="tax_number"
                                        v-model="form.tax_number"
                                        type="text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Registration Number</label
                                    >
                                    <input
                                        id="registration_number"
                                        v-model="form.registration_number"
                                        type="text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Website</label
                                    >
                                    <input
                                        id="website"
                                        v-model="form.website"
                                        type="url"
                                        placeholder="https://example.com"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.website"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.website }}
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Notes</label
                                    >
                                    <textarea
                                        id="notes"
                                        v-model="form.notes"
                                        rows="3"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Primary Location -->
                        <div v-if="currentStep === 2" class="space-y-6">
                            <div>
                                <h2
                                    class="mb-4 text-2xl font-semibold text-gray-900"
                                >
                                    Primary Location
                                </h2>
                                <p class="mb-6 text-gray-600">
                                    Your main business address
                                </p>
                            </div>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Location Name</label
                                    >
                                    <input
                                        id="location_name"
                                        v-model="form.location_name"
                                        type="text"
                                        placeholder="e.g. Head Office, Main Office"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >GSTIN / Tax ID</label
                                    >
                                    <input
                                        id="gstin"
                                        v-model="form.gstin"
                                        type="text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Address Line 1 *</label
                                    >
                                    <input
                                        id="address_line_1"
                                        v-model="form.address_line_1"
                                        type="text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.address_line_1"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.address_line_1 }}
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Address Line 2</label
                                    >
                                    <input
                                        id="address_line_2"
                                        v-model="form.address_line_2"
                                        type="text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >City *</label
                                    >
                                    <input
                                        id="city"
                                        v-model="form.city"
                                        type="text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.city"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.city }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >State / Province *</label
                                    >
                                    <input
                                        id="state"
                                        v-model="form.state"
                                        type="text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.state"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.state }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Postal Code *</label
                                    >
                                    <input
                                        id="postal_code"
                                        v-model="form.postal_code"
                                        type="text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.postal_code"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.postal_code }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Configuration -->
                        <div v-if="currentStep === 3" class="space-y-6">
                            <div>
                                <h2
                                    class="mb-4 text-2xl font-semibold text-gray-900"
                                >
                                    Configuration
                                </h2>
                                <p class="mb-6 text-gray-600">
                                    Set your currency and financial year
                                </p>
                            </div>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Country *</label
                                    >
                                    <select
                                        id="country_code"
                                        v-model="form.country_code"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    >
                                        <option value="">
                                            Select your country
                                        </option>
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
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Currency *</label
                                    >
                                    <select
                                        id="currency"
                                        v-model="form.currency"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    >
                                        <option value="">
                                            Select currency
                                        </option>
                                        <option
                                            v-for="(
                                                label, code
                                            ) in availableCurrencies"
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
                                <div v-if="selectedCountry">
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Financial Year</label
                                    >
                                    <select
                                        v-model="form.financial_year_type"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
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
                                <div
                                    v-if="selectedCountry"
                                    class="rounded-md bg-blue-50 p-4 md:col-span-2"
                                >
                                    <h4
                                        class="mb-2 text-sm font-medium text-blue-900"
                                    >
                                        Country Info
                                    </h4>
                                    <div
                                        class="space-y-1 text-sm text-blue-800"
                                    >
                                        <p>
                                            <strong>Tax System:</strong>
                                            {{
                                                selectedCountry.tax_system.name
                                            }}
                                        </p>
                                        <p>
                                            <strong>Common Rates:</strong>
                                            {{
                                                selectedCountry.tax_system.rates.join(
                                                    ', ',
                                                )
                                            }}
                                        </p>
                                        <p>
                                            <strong>Recommended Format:</strong>
                                            {{
                                                selectedCountry.recommended_numbering
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Contact Details -->
                        <div v-if="currentStep === 4" class="space-y-6">
                            <div>
                                <h2
                                    class="mb-4 text-2xl font-semibold text-gray-900"
                                >
                                    Contact Details
                                </h2>
                                <p class="mb-6 text-gray-600">
                                    Your email and phone information
                                </p>
                            </div>
                            <div class="space-y-6">
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Email Addresses *</label
                                    >
                                    <div
                                        v-for="(email, index) in form.emails"
                                        :key="index"
                                        class="mb-2 flex items-center gap-2"
                                    >
                                        <input
                                            v-model="form.emails[index]"
                                            :aria-describedby="`error-emails-${index}`"
                                            type="email"
                                            placeholder="email@company.com"
                                            class="flex-1 rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                        />
                                        <button
                                            v-if="index > 0"
                                            type="button"
                                            class="text-red-600 hover:text-red-800"
                                            @click="removeEmail(index)"
                                        >
                                            <svg
                                                class="h-5 w-5"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-800"
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
                                    <p
                                        v-if="form.errors.emails"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.emails }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700"
                                        >Phone Number</label
                                    >
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="+1-555-0123"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <p
                                        v-if="form.errors.phone"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.phone }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div
                        class="flex justify-between border-t border-gray-200 bg-gray-50 px-6 py-4"
                    >
                        <button
                            v-if="currentStep > 1"
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                            @click="previousStep"
                        >
                            Previous
                        </button>
                        <div v-else />

                        <button
                            type="button"
                            :disabled="form.processing"
                            class="rounded-md border border-transparent px-4 py-2 text-sm font-medium text-white shadow-sm focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            :class="
                                currentStep < totalSteps
                                    ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
                                    : 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
                            "
                            @click="submitStep"
                        >
                            {{
                                form.processing
                                    ? 'Processing...'
                                    : currentStep < totalSteps
                                      ? 'Next'
                                      : 'Complete Setup'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
