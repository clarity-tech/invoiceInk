<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Organization, CountryInfo } from '../types';

const props = defineProps<{
    organization: Organization;
    countries: CountryInfo[];
}>();

const recentlySuccessful = ref(false);
const loc = props.organization.primary_location;

const form = useForm({
    location_name: loc?.name ?? '',
    gstin: loc?.gstin ?? '',
    address_line_1: loc?.address_line_1 ?? '',
    address_line_2: loc?.address_line_2 ?? '',
    city: loc?.city ?? '',
    state: loc?.state ?? '',
    country: loc?.country ?? '',
    postal_code: loc?.postal_code ?? '',
});

function submit() {
    form.put(`/organizations/${props.organization.id}/location`, {
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
                Primary Location
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Your main business address used on invoices.
            </p>
        </div>

        <div class="space-y-6 p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="loc-name"
                        class="block text-sm font-medium text-gray-700"
                        >Location Name</label
                    >
                    <input
                        id="loc-name"
                        v-model="form.location_name"
                        type="text"
                        placeholder="e.g., Head Office"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="loc-gstin"
                        class="block text-sm font-medium text-gray-700"
                        >GSTIN / Tax ID</label
                    >
                    <input
                        id="loc-gstin"
                        v-model="form.gstin"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
            </div>

            <div>
                <label
                    for="loc-addr1"
                    class="block text-sm font-medium text-gray-700"
                    >Address Line 1 *</label
                >
                <input
                    id="loc-addr1"
                    v-model="form.address_line_1"
                    type="text"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                />
                <p
                    v-if="form.errors.address_line_1"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.address_line_1 }}
                </p>
            </div>

            <div>
                <label
                    for="loc-addr2"
                    class="block text-sm font-medium text-gray-700"
                    >Address Line 2</label
                >
                <input
                    id="loc-addr2"
                    v-model="form.address_line_2"
                    type="text"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                />
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label
                        for="loc-city"
                        class="block text-sm font-medium text-gray-700"
                        >City *</label
                    >
                    <input
                        id="loc-city"
                        v-model="form.city"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
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
                        for="loc-state"
                        class="block text-sm font-medium text-gray-700"
                        >State *</label
                    >
                    <input
                        id="loc-state"
                        v-model="form.state"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
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
                        for="loc-postal"
                        class="block text-sm font-medium text-gray-700"
                        >Postal Code *</label
                    >
                    <input
                        id="loc-postal"
                        v-model="form.postal_code"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                    <p
                        v-if="form.errors.postal_code"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.postal_code }}
                    </p>
                </div>
            </div>

            <div>
                <label
                    for="loc-country"
                    class="block text-sm font-medium text-gray-700"
                    >Country *</label
                >
                <select
                    id="loc-country"
                    v-model="form.country"
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
                <p v-if="form.errors.country" class="mt-1 text-sm text-red-600">
                    {{ form.errors.country }}
                </p>
            </div>
        </div>

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
                {{ form.processing ? 'Saving...' : 'Save Location' }}
            </button>
        </div>
    </form>
</template>
