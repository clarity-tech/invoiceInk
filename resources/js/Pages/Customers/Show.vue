<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import CustomerForm from '@/Components/CustomerForm.vue';
import LocationModal from '@/Components/LocationModal.vue';
import {
    formatLocationLine,
    formatLocationSummary,
} from '@/composables/useLocationFormat';
import { usePermissions } from '@/composables/usePermissions';
import { taxLabelFor } from '@/composables/useTaxLabel';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Contact, Customer, Location } from '@/types';

const { can } = usePermissions();

const props = defineProps<{
    customer: Customer & {
        locations?: Location[];
        archived_locations?: Location[];
    };
    currencies: Record<string, string>;
    countries: Record<string, string>;
    countryNames: Record<string, string>;
    taxLabels: Record<string, string>;
    defaultPhoneCountry?: string | null;
    indianStates: Record<string, string>;
    gstTreatments: Record<string, string>;
}>();

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

// Edit form
const showForm = ref(false);

const form = useForm({
    name: '',
    phone: '',
    phone_country: '',
    currency: 'INR',
    place_of_supply: '',
    gst_treatment: 'domestic',
    contacts: [{ name: '', email: '' }] as Contact[],
});

function openEdit() {
    form.name = props.customer.name;
    form.phone = props.customer.phone ?? '';
    form.phone_country = props.customer.phone_country ?? '';
    form.currency = props.customer.currency ?? 'INR';
    form.place_of_supply = props.customer.place_of_supply ?? '';
    form.gst_treatment = props.customer.gst_treatment ?? 'domestic';
    form.contacts = props.customer.emails?.length
        ? [...props.customer.emails]
        : [{ name: '', email: '' }];
    form.clearErrors();
    showForm.value = true;
}

function submitCustomer() {
    form.put(`/customers/${props.customer.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
        },
    });
}

// Location management
const showLocationModal = ref(false);
const editingLocation = ref<Location | null>(null);

function openAddLocation() {
    editingLocation.value = null;
    showLocationModal.value = true;
}

function openEditLocation(location: Location) {
    editingLocation.value = location;
    showLocationModal.value = true;
}

function setPrimary(locationId: number) {
    router.post(
        `/customers/${props.customer.id}/primary-location/${locationId}`,
        {},
        { preserveScroll: true },
    );
}

const confirmingLocationDelete = ref(false);
const locationToDelete = ref<number | null>(null);

function confirmDeleteLocation(locationId: number) {
    locationToDelete.value = locationId;
    confirmingLocationDelete.value = true;
}

function deleteLocation() {
    if (locationToDelete.value === null) {
        return;
    }

    router.delete(
        `/customers/${props.customer.id}/locations/${locationToDelete.value}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmingLocationDelete.value = false;
                locationToDelete.value = null;
            },
        },
    );
}

const restoringLocation = ref<number | null>(null);

function restoreLocation(locationId: number) {
    restoringLocation.value = locationId;
    router.post(
        `/customers/${props.customer.id}/locations/${locationId}/restore`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                restoringLocation.value = null;
            },
        },
    );
}

const confirmingLocationForceDelete = ref(false);
const locationToForceDelete = ref<number | null>(null);

function confirmForceDeleteLocation(locationId: number) {
    locationToForceDelete.value = locationId;
    confirmingLocationForceDelete.value = true;
}

function forceDeleteLocation() {
    if (locationToForceDelete.value === null) {
        return;
    }

    const locationId = locationToForceDelete.value;

    confirmingLocationForceDelete.value = false;
    locationToForceDelete.value = null;

    router.delete(
        `/customers/${props.customer.id}/locations/${locationId}/force`,
        {
            preserveScroll: true,
        },
    );
}

const showArchived = ref(false);
</script>

<template>
    <AppLayout :title="customer.name">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        href="/customers"
                        class="text-sm text-gray-500 hover:text-gray-700"
                    >
                        &larr; Customers
                    </Link>
                    <h2
                        class="text-xl leading-tight font-semibold text-gray-800"
                    >
                        {{ customer.name }}
                    </h2>
                </div>
                <button
                    v-if="can('update')"
                    type="button"
                    class="cursor-pointer rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                    @click="openEdit"
                >
                    Edit Customer
                </button>
            </div>
        </template>

        <div class="space-y-6 py-4">
            <!-- Details -->
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="mb-4 text-sm font-medium text-gray-900">Details</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ customer.formatted_phone ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">
                            Currency
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ customer.currency ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">
                            Billing address
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{
                                formatLocationSummary(
                                    customer.primary_location ?? null,
                                    countryNames,
                                ) || '-'
                            }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-6">
                    <dt class="text-xs text-gray-500 uppercase">Contacts</dt>
                    <dd class="mt-2 space-y-1">
                        <div
                            v-for="(contact, index) in customer.emails ?? []"
                            :key="index"
                            class="text-sm text-gray-900"
                        >
                            <span v-if="contact.name" class="text-gray-500"
                                >{{ contact.name }} &middot; </span
                            >{{ contact.email }}
                        </div>
                        <div
                            v-if="!customer.emails?.length"
                            class="text-sm text-gray-500"
                        >
                            No contacts
                        </div>
                    </dd>
                </div>
            </div>

            <!-- Locations -->
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div
                    class="flex items-center justify-between border-b border-gray-200 px-6 py-4"
                >
                    <h3 class="text-sm font-medium text-gray-900">
                        Locations ({{ customer.locations?.length ?? 0 }})
                    </h3>
                    <button
                        v-if="can('update')"
                        type="button"
                        class="cursor-pointer text-sm font-medium text-brand-600 hover:text-brand-900"
                        @click="openAddLocation"
                    >
                        + Add location
                    </button>
                </div>

                <div
                    v-if="customer.locations?.length"
                    class="divide-y divide-gray-100"
                >
                    <div
                        v-for="loc in customer.locations"
                        :key="loc.id"
                        class="flex items-start justify-between px-6 py-4"
                    >
                        <div class="text-sm">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{
                                    loc.name
                                }}</span>
                                <span
                                    v-if="
                                        customer.primary_location_id === loc.id
                                    "
                                    class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
                                    >Primary</span
                                >
                            </div>
                            <div class="mt-1 text-gray-500">
                                {{ formatLocationLine(loc, countryNames) }}
                            </div>
                            <div v-if="loc.tax_id" class="mt-1 text-gray-400">
                                {{ taxLabelFor(loc.country, taxLabels) }}:
                                {{ loc.tax_id }}
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-3">
                            <button
                                v-if="
                                    can('update') &&
                                    customer.primary_location_id !== loc.id
                                "
                                type="button"
                                class="cursor-pointer text-xs text-brand-600 hover:text-brand-900"
                                @click="setPrimary(loc.id)"
                            >
                                Set Primary
                            </button>
                            <button
                                v-if="can('update')"
                                type="button"
                                class="cursor-pointer text-xs text-brand-600 hover:text-brand-900"
                                @click="openEditLocation(loc)"
                            >
                                Edit
                            </button>
                            <button
                                v-if="can('delete')"
                                type="button"
                                class="cursor-pointer text-xs text-red-600 hover:text-red-900"
                                @click="confirmDeleteLocation(loc.id)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
                <p v-else class="px-6 py-8 text-center text-sm text-gray-500">
                    No locations yet. An invoice needs a billing address, so add
                    one here.
                </p>
            </div>

            <!-- Archived locations -->
            <div
                v-if="customer.archived_locations?.length"
                class="bg-white shadow-sm sm:rounded-lg"
            >
                <button
                    type="button"
                    class="flex w-full cursor-pointer items-center gap-2 px-6 py-4 text-left"
                    :aria-expanded="showArchived"
                    @click="showArchived = !showArchived"
                >
                    <svg
                        class="h-3 w-3 text-gray-400 transition-transform"
                        :class="{ 'rotate-90': showArchived }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                    <span class="text-sm font-medium text-gray-500"
                        >Archived ({{
                            customer.archived_locations.length
                        }})</span
                    >
                </button>

                <div
                    v-if="showArchived"
                    class="divide-y divide-gray-100 border-t border-gray-200"
                >
                    <div
                        v-for="loc in customer.archived_locations"
                        :key="loc.id"
                        class="flex items-start justify-between px-6 py-4 text-gray-400"
                    >
                        <div class="text-sm">
                            <div class="font-medium">{{ loc.name }}</div>
                            <div class="mt-1">
                                {{ formatLocationLine(loc, countryNames) }}
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-3">
                            <button
                                v-if="can('update')"
                                type="button"
                                class="cursor-pointer text-xs text-brand-600 hover:text-brand-900 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="restoringLocation === loc.id"
                                @click="restoreLocation(loc.id)"
                            >
                                {{
                                    restoringLocation === loc.id
                                        ? 'Restoring...'
                                        : 'Restore'
                                }}
                            </button>
                            <button
                                v-if="can('delete')"
                                type="button"
                                class="cursor-pointer text-xs text-red-600 hover:text-red-900"
                                @click="confirmForceDeleteLocation(loc.id)"
                            >
                                Delete Forever
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Customer Modal -->
        <Teleport v-if="isMounted && showForm" to="body">
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div
                    class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
                >
                    <div
                        class="bg-opacity-75 fixed inset-0 bg-gray-500 transition-opacity"
                        @click="showForm = false"
                    />
                    <span
                        class="hidden sm:inline-block sm:h-screen sm:align-middle"
                        aria-hidden="true"
                        >&#8203;</span
                    >
                    <div
                        class="relative inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:align-middle"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Edit Customer"
                    >
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <h3
                                class="mb-4 text-lg leading-6 font-medium text-gray-900"
                            >
                                Edit Customer
                            </h3>
                            <CustomerForm
                                :form="form"
                                :currencies="currencies"
                                :countries="countries"
                                :is-editing="true"
                                :default-country="defaultPhoneCountry ?? 'US'"
                                :indian-states="indianStates"
                                :gst-treatments="gstTreatments"
                                @submit="submitCustomer"
                                @cancel="showForm = false"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <LocationModal
            :show="showLocationModal"
            :customer-id="customer.id"
            :location="editingLocation"
            :countries="countries"
            :tax-labels="taxLabels"
            @close="
                showLocationModal = false;
                editingLocation = null;
            "
        />

        <ConfirmationModal
            :show="confirmingLocationDelete"
            title="Delete Location"
            message="Delete this location? It will be archived and can be restored later. Invoices that already use it will still display the address."
            confirm-label="Delete"
            :destructive="true"
            @confirm="deleteLocation"
            @cancel="confirmingLocationDelete = false"
        />

        <ConfirmationModal
            :show="confirmingLocationForceDelete"
            title="Delete Location Forever"
            message="Permanently delete this archived location? This cannot be undone. If any invoice references it, the deletion will be blocked."
            confirm-label="Delete Forever"
            :destructive="true"
            @confirm="forceDeleteLocation"
            @cancel="confirmingLocationForceDelete = false"
        />
    </AppLayout>
</template>
