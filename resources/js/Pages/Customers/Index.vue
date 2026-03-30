<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import CustomerForm from '@/Components/CustomerForm.vue';
import LocationModal from '@/Components/LocationModal.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Customer, Location, Contact } from '@/types';

interface PaginatedCustomers {
    data: (Customer & { locations_count: number })[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    customers: PaginatedCustomers;
    currencies: Record<string, string>;
    countries: Record<string, string>;
}>();

// Customer form state
const searchQuery = ref('');

const filteredCustomers = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.customers.data;
    }

    const q = searchQuery.value.toLowerCase();

    return props.customers.data.filter((c) => {
        const email = primaryEmail(c);

        return (
            c.name.toLowerCase().includes(q) ||
            (email && email.toLowerCase().includes(q)) ||
            (c.phone && c.phone.includes(q))
        );
    });
});

const showForm = ref(false);
const editingCustomer = ref<Customer | null>(null);

const form = useForm({
    name: '',
    phone: '',
    currency: 'INR',
    contacts: [{ name: '', email: '' }] as Contact[],
});

function openCreate() {
    editingCustomer.value = null;
    form.reset();
    form.contacts = [{ name: '', email: '' }];
    form.clearErrors();
    showForm.value = true;
}

function openEdit(customer: Customer) {
    editingCustomer.value = customer;
    form.name = customer.name;
    form.phone = customer.phone ?? '';
    form.currency = customer.currency ?? 'INR';
    form.contacts = customer.emails?.length
        ? [...customer.emails]
        : [{ name: '', email: '' }];
    form.clearErrors();
    showForm.value = true;
}

function submitCustomer() {
    if (editingCustomer.value) {
        form.put(`/customers/${editingCustomer.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post('/customers', {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false;
            },
        });
    }
}

function cancelForm() {
    showForm.value = false;
}

// Delete state
const confirmingDelete = ref(false);
const customerToDelete = ref<Customer | null>(null);

function confirmDelete(customer: Customer) {
    customerToDelete.value = customer;
    confirmingDelete.value = true;
}

const deleting = ref(false);

function deleteCustomer() {
    if (!customerToDelete.value) {
        return;
    }

    const id = customerToDelete.value.id;

    confirmingDelete.value = false;
    customerToDelete.value = null;
    deleting.value = true;

    // Optimistic: remove from list immediately, auto-reverts on failure
    router
        .optimistic((page) => {
            const customers = (page.props as any).customers;

            if (customers?.data) {
                customers.data = customers.data.filter((c: any) => c.id !== id);
                customers.total = Math.max(0, customers.total - 1);
            }
        })
        .delete(`/customers/${id}`, {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false;
            },
        });
}

// Location modal state
const showLocationModal = ref(false);
const locationCustomerId = ref<number>(0);
const editingLocation = ref<Location | null>(null);

// Expanded rows for showing locations
const expandedRows = ref<Set<number>>(new Set());

function toggleExpand(customerId: number) {
    if (expandedRows.value.has(customerId)) {
        expandedRows.value.delete(customerId);
    } else {
        expandedRows.value.add(customerId);
        // Reload the page to get fresh location data
        router.reload({ only: ['customers'] });
    }
}

function openAddLocation(customerId: number) {
    locationCustomerId.value = customerId;
    editingLocation.value = null;
    showLocationModal.value = true;
}

function openEditLocation(customerId: number, location: Location) {
    locationCustomerId.value = customerId;
    editingLocation.value = location;
    showLocationModal.value = true;
}

function closeLocationModal() {
    showLocationModal.value = false;
    editingLocation.value = null;
}

const confirmingLocationDelete = ref(false);
const locationToDelete = ref<{ customerId: number; locationId: number } | null>(
    null,
);

function confirmDeleteLocation(customerId: number, locationId: number) {
    locationToDelete.value = { customerId, locationId };
    confirmingLocationDelete.value = true;
}

function deleteLocation() {
    if (!locationToDelete.value) {
        return;
    }

    router.delete(
        `/customers/${locationToDelete.value.customerId}/locations/${locationToDelete.value.locationId}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmingLocationDelete.value = false;
                locationToDelete.value = null;
            },
        },
    );
}

function setPrimary(customerId: number, locationId: number) {
    router.post(
        `/customers/${customerId}/primary-location/${locationId}`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function primaryEmail(customer: Customer): string {
    return customer.emails?.[0]?.email ?? '';
}

function locationSummary(customer: Customer): string {
    const loc = customer.primary_location;

    if (!loc) {
        return '-';
    }

    return [loc.city, loc.state].filter(Boolean).join(', ');
}
</script>

<template>
    <AppLayout title="Customers">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Customers
                </h2>
                <button
                    type="button"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                    @click="openCreate"
                >
                    Add Customer
                </button>
            </div>
        </template>

        <div class="py-4">
            <!-- Search -->
            <div class="mb-4">
                <div class="relative max-w-sm">
                    <svg
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                        />
                    </svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Filter this page by name, email, or phone..."
                        class="block w-full rounded-md border-gray-300 pl-10 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
            </div>

            <!-- Customer Form Modal -->
            <Teleport to="body">
                <div v-if="showForm" class="fixed inset-0 z-50 overflow-y-auto">
                    <div
                        class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
                    >
                        <div
                            class="bg-opacity-75 fixed inset-0 bg-gray-500 transition-opacity"
                            @click="cancelForm"
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
                            :aria-label="
                                editingCustomer
                                    ? 'Edit Customer'
                                    : 'Add Customer'
                            "
                        >
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                                <h3
                                    class="mb-4 text-lg leading-6 font-medium text-gray-900"
                                >
                                    {{
                                        editingCustomer
                                            ? 'Edit Customer'
                                            : 'Add Customer'
                                    }}
                                </h3>
                                <CustomerForm
                                    :form="form"
                                    :currencies="currencies"
                                    :countries="countries"
                                    :is-editing="!!editingCustomer"
                                    @submit="submitCustomer"
                                    @cancel="cancelForm"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Customer Table -->
            <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-8 px-3 py-3"></th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Email
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Phone
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Currency
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Location
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template
                            v-for="customer in filteredCustomers"
                            :key="customer.id"
                        >
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-4">
                                    <button
                                        v-if="customer.locations_count > 0"
                                        type="button"
                                        class="text-gray-400 hover:text-gray-600"
                                        @click="toggleExpand(customer.id)"
                                    >
                                        <svg
                                            class="h-4 w-4 transition-transform"
                                            :class="{
                                                'rotate-90': expandedRows.has(
                                                    customer.id,
                                                ),
                                            }"
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
                                    </button>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900"
                                >
                                    {{ customer.name }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                >
                                    {{ primaryEmail(customer) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                >
                                    {{ customer.phone ?? '-' }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                >
                                    {{ customer.currency ?? '-' }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                >
                                    {{ locationSummary(customer) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-right text-sm whitespace-nowrap"
                                >
                                    <button
                                        type="button"
                                        class="mr-3 text-brand-600 hover:text-brand-900"
                                        @click="openAddLocation(customer.id)"
                                    >
                                        + Location
                                    </button>
                                    <button
                                        type="button"
                                        class="mr-3 text-brand-600 hover:text-brand-900"
                                        @click="openEdit(customer)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="text-red-600 hover:text-red-900"
                                        @click="confirmDelete(customer)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <!-- Expanded locations -->
                            <tr
                                v-if="
                                    expandedRows.has(customer.id) &&
                                    customer.locations?.length
                                "
                            >
                                <td colspan="7" class="bg-gray-50 px-6 py-3">
                                    <div
                                        class="mb-2 text-xs font-medium text-gray-500 uppercase"
                                    >
                                        Locations ({{
                                            customer.locations.length
                                        }})
                                    </div>
                                    <div
                                        v-for="loc in customer.locations"
                                        :key="loc.id"
                                        class="flex items-center justify-between border-b border-gray-100 py-2 last:border-0"
                                    >
                                        <div class="text-sm">
                                            <span
                                                class="font-medium text-gray-900"
                                                >{{ loc.name }}</span
                                            >
                                            <span
                                                v-if="
                                                    customer.primary_location_id ===
                                                    loc.id
                                                "
                                                class="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
                                                >Primary</span
                                            >
                                            <span class="ml-2 text-gray-500"
                                                >{{ loc.address_line_1 }},
                                                {{ loc.city }},
                                                {{ loc.state }}</span
                                            >
                                            <span
                                                v-if="loc.gstin"
                                                class="ml-2 text-gray-400"
                                                >GSTIN: {{ loc.gstin }}</span
                                            >
                                        </div>
                                        <div class="flex gap-2">
                                            <button
                                                v-if="
                                                    customer.primary_location_id !==
                                                    loc.id
                                                "
                                                type="button"
                                                class="text-xs text-brand-600 hover:text-brand-900"
                                                @click="
                                                    setPrimary(
                                                        customer.id,
                                                        loc.id,
                                                    )
                                                "
                                            >
                                                Set Primary
                                            </button>
                                            <button
                                                type="button"
                                                class="text-xs text-brand-600 hover:text-brand-900"
                                                @click="
                                                    openEditLocation(
                                                        customer.id,
                                                        loc,
                                                    )
                                                "
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                class="text-xs text-red-600 hover:text-red-900"
                                                @click="
                                                    confirmDeleteLocation(
                                                        customer.id,
                                                        loc.id,
                                                    )
                                                "
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="filteredCustomers.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <template v-if="searchQuery">
                                    <svg
                                        class="mx-auto h-12 w-12 text-gray-300"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                                        />
                                    </svg>
                                    <h3
                                        class="mt-2 text-sm font-semibold text-gray-900"
                                    >
                                        No results found
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        No customers match "{{ searchQuery }}".
                                        Try a different search term.
                                    </p>
                                </template>
                                <template v-else>
                                    <svg
                                        class="mx-auto h-12 w-12 text-gray-300"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-4.5 0 2.625 2.625 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                                        />
                                    </svg>
                                    <h3
                                        class="mt-2 text-sm font-semibold text-gray-900"
                                    >
                                        No customers yet
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Add your first customer to start
                                        creating invoices.
                                    </p>
                                    <div class="mt-4">
                                        <button
                                            type="button"
                                            class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                                            @click="openCreate"
                                        >
                                            Add Customer
                                        </button>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <nav
                    v-if="customers.last_page > 1"
                    class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                >
                    <div class="hidden sm:block">
                        <p class="text-sm text-gray-700">
                            Showing page
                            <span class="font-medium">{{
                                customers.current_page
                            }}</span>
                            of
                            <span class="font-medium">{{
                                customers.last_page
                            }}</span>
                            ({{ customers.total }} total)
                        </p>
                    </div>
                    <div
                        class="flex flex-1 justify-between gap-2 sm:justify-end"
                    >
                        <template
                            v-for="link in customers.links"
                            :key="link.label"
                        >
                            <button
                                v-if="link.url"
                                type="button"
                                class="relative inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium"
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

        <!-- Location Modal -->
        <LocationModal
            :show="showLocationModal"
            :customer-id="locationCustomerId"
            :location="editingLocation"
            :countries="countries"
            @close="closeLocationModal"
        />

        <!-- Delete Confirmation -->
        <ConfirmationModal
            :show="confirmingDelete"
            title="Delete Customer"
            message="Are you sure you want to delete this customer? This action cannot be undone."
            confirm-label="Delete"
            :destructive="true"
            @confirm="deleteCustomer"
            @cancel="confirmingDelete = false"
        />

        <!-- Location Delete Confirmation -->
        <ConfirmationModal
            :show="confirmingLocationDelete"
            title="Delete Location"
            message="Are you sure you want to delete this location? This action cannot be undone."
            confirm-label="Delete"
            :destructive="true"
            @confirm="deleteLocation"
            @cancel="confirmingLocationDelete = false"
        />
    </AppLayout>
</template>
