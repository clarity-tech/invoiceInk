<script setup lang="ts">
import { Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import CustomerForm from '@/Components/CustomerForm.vue';
import { formatLocationSummary } from '@/composables/useLocationFormat';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Customer, Contact } from '@/types';

const { can } = usePermissions();

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
    countryNames: Record<string, string>;
    taxLabels: Record<string, string>;
    defaultPhoneCountry?: string | null;
    indianStates: Record<string, string>;
    gstTreatments: Record<string, string>;
    filters: { search: string };
}>();

const isMounted = ref(false);

// Focus lands here after a confirmed delete: the row that opened the
// dialog is removed, so the dialog cannot hand focus back to it.
const listRegion = ref<HTMLElement | null>(null);
onMounted(() => {
    isMounted.value = true;
});

/**
 * Search runs on the server so it spans every page, not just the ten rows the
 * current page happens to hold. Debounced so typing does not fire a request per
 * keystroke; `preserveState` keeps the input focused across the round trip.
 */
const searchQuery = ref(props.filters.search ?? '');

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(searchQuery, (value) => {
    // Skip when the box already agrees with the server, which is the case for
    // the props-driven sync below. Without this, restoring the input would fire
    // a redundant request for results the page is already showing.
    if (value.trim() === (props.filters.search ?? '')) {
        return;
    }

    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        router.get('/customers', value.trim() ? { search: value.trim() } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['customers', 'filters'],
        });
    }, 300);
});

// Inertia reuses this component for same-page visits, so setup does not re-run.
// Navigating back to an unfiltered /customers would otherwise leave the previous
// term sitting in the box above a full, unfiltered list.
watch(
    () => props.filters.search,
    (value) => {
        searchQuery.value = value ?? '';
    },
);

onUnmounted(() => {
    clearTimeout(searchTimeout);
});

const showForm = ref(false);
const editingCustomer = ref<Customer | null>(null);

const form = useForm({
    name: '',
    phone: '',
    phone_country: '',
    currency: 'INR',
    place_of_supply: '',
    gst_treatment: 'domestic',
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
    form.phone_country = customer.phone_country ?? '';
    form.currency = customer.currency ?? 'INR';
    form.place_of_supply = customer.place_of_supply ?? '';
    form.gst_treatment = customer.gst_treatment ?? 'domestic';
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
            // Creating redirects to the new customer's detail page so the
            // "now add a location" prompt is actionable. Scroll is only worth
            // preserving when validation sends us back to this same listing.
            preserveScroll: 'errors',
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
        .optimistic((props: any) => ({
            customers: {
                ...props.customers,
                data: props.customers.data.filter((c: any) => c.id !== id),
                total: Math.max(0, props.customers.total - 1),
            },
        }))
        .delete(`/customers/${id}`, {
            preserveScroll: true,
            onFinish: () => {
                deleting.value = false;
            },
        });
}

function primaryEmail(customer: Customer): string {
    return customer.emails?.[0]?.email ?? '';
}

function locationSummary(customer: Customer): string {
    const loc = customer.primary_location;

    if (!loc) {
        return '-';
    }

    return formatLocationSummary(loc, props.countryNames) || '-';
}
</script>

<template>
    <AppLayout title="Customers">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Customers
                </h2>
                <div class="flex gap-2">
                    <a
                        href="/customers/trash"
                        class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50"
                    >
                        Trash
                    </a>
                    <button
                        v-if="can('create')"
                        type="button"
                        class="cursor-pointer rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                        @click="openCreate"
                    >
                        Add Customer
                    </button>
                </div>
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
                        placeholder="Search by name, email, or phone..."
                        class="block w-full rounded-md border-gray-300 pl-10 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
            </div>

            <!-- Customer Form Modal -->
            <Teleport v-if="isMounted && showForm" to="body">
                <div class="fixed inset-0 z-50 overflow-y-auto">
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
                                    :default-country="
                                        defaultPhoneCountry ?? 'US'
                                    "
                                    :indian-states="indianStates"
                                    :gst-treatments="gstTreatments"
                                    @submit="submitCustomer"
                                    @cancel="cancelForm"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Customer Table -->
            <div
                ref="listRegion"
                tabindex="-1"
                aria-label="Customers list"
                class="overflow-x-auto bg-white shadow-sm focus:ring-2 focus:ring-brand-500 focus:outline-none sm:rounded-lg"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Name
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Email
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Phone
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Currency
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Location
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template
                            v-for="customer in customers.data"
                            :key="customer.id"
                        >
                            <tr class="hover:bg-gray-50">
                                <td
                                    class="px-4 py-4 text-sm font-medium text-gray-900"
                                >
                                    <Link
                                        :href="`/customers/${customer.id}`"
                                        class="block max-w-[260px] truncate text-brand-600 hover:text-brand-900"
                                        :title="customer.name"
                                    >
                                        {{ customer.name }}
                                    </Link>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500">
                                    <div
                                        class="max-w-[260px] truncate"
                                        :title="primaryEmail(customer)"
                                    >
                                        {{ primaryEmail(customer) }}
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-4 text-sm whitespace-nowrap text-gray-500"
                                >
                                    {{ customer.formatted_phone ?? '-' }}
                                </td>
                                <td
                                    class="px-4 py-4 text-sm whitespace-nowrap text-gray-500"
                                >
                                    {{ customer.currency ?? '-' }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500">
                                    <div
                                        class="max-w-[200px] truncate"
                                        :title="locationSummary(customer)"
                                    >
                                        {{ locationSummary(customer) }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-400">
                                        {{ customer.locations_count }}
                                        {{
                                            customer.locations_count === 1
                                                ? 'location'
                                                : 'locations'
                                        }}
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-4 text-right text-sm whitespace-nowrap"
                                >
                                    <button
                                        v-if="can('update')"
                                        type="button"
                                        class="mr-3 cursor-pointer text-brand-600 hover:text-brand-900"
                                        @click="openEdit(customer)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="can('delete')"
                                        type="button"
                                        class="cursor-pointer text-red-600 hover:text-red-900"
                                        @click="confirmDelete(customer)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="6" class="px-4 py-12 text-center">
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
                                    <div v-if="can('create')" class="mt-4">
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

        <!-- Delete Confirmation -->
        <ConfirmationModal
            :restore-focus-to="listRegion"
            :show="confirmingDelete"
            title="Delete Customer"
            message="Delete this customer? You can restore them from Trash later."
            confirm-label="Delete"
            :destructive="true"
            @confirm="deleteCustomer"
            @cancel="confirmingDelete = false"
        />
    </AppLayout>
</template>
