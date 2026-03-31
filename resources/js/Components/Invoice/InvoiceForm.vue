<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onUnmounted } from 'vue';
import EmailModal from './EmailModal.vue';
import ItemRow from './ItemRow.vue';
import { useFormatMoney } from '@/composables/useFormatMoney';
import { useInvoiceCalculator } from '@/composables/useInvoiceCalculator';
import type { LineItem } from '@/composables/useInvoiceCalculator';
import type {
    Invoice,
    Customer,
    Location,
    TaxTemplate,
    InvoiceNumberingSeries,
    Currency,
} from '@/types';

const props = defineProps<{
    mode: 'create' | 'edit';
    type: 'invoice' | 'estimate';
    invoice?: Invoice;
    customers: Customer[];
    organizationLocations: Location[];
    taxTemplates: TaxTemplate[];
    numberingSeries: InvoiceNumberingSeries[];
    statusOptions: Record<string, string>;
    defaults?: {
        organization_id: number | null;
        organization_location_id: number | null;
        invoice_numbering_series_id: number | null;
        issued_at: string;
        due_at: string;
        currency: string;
    };
}>();

const { formatMoney } = useFormatMoney();
const showEmailModal = ref(false);
const downloadingPdf = ref(false);
let pdfTimer: ReturnType<typeof setTimeout> | null = null;

// Reset PDF loading state after download starts
watch(downloadingPdf, (val) => {
    if (val) {
        pdfTimer = setTimeout(() => {
            downloadingPdf.value = false;
        }, 5000);
    }
});

onUnmounted(() => {
    if (pdfTimer) {
        clearTimeout(pdfTimer);
    }
});

// Build initial items
function buildInitialItems(): LineItem[] {
    if (props.mode === 'edit' && props.invoice?.items?.length) {
        return props.invoice.items.map((item) => ({
            description: item.description,
            sac_code: item.sac_code,
            quantity: item.quantity,
            unit_price: item.unit_price,
            tax_rate: item.tax_rate,
        }));
    }

    return [
        {
            description: '',
            sac_code: null,
            quantity: 1,
            unit_price: 0,
            tax_rate: 0,
        },
    ];
}

const items = ref<LineItem[]>(buildInitialItems());
const { totals } = useInvoiceCalculator(items);

// Determine currency from selected customer
const selectedCustomerId = ref<number | null>(
    props.mode === 'edit' ? (props.invoice?.customer_id ?? null) : null,
);

const selectedCustomer = computed(
    () =>
        props.customers.find((c) => c.id === selectedCustomerId.value) ?? null,
);

const customerLocations = computed(
    () => selectedCustomer.value?.locations ?? [],
);

const currentCurrency = computed<Currency>(
    () =>
        selectedCustomer.value?.currency ??
        (props.defaults?.currency as Currency) ??
        'INR',
);

// Form
const form = useForm({
    type: props.type,
    organization_id:
        props.mode === 'edit'
            ? props.invoice!.organization_id
            : (props.defaults?.organization_id ?? null),
    customer_id: selectedCustomerId.value,
    organization_location_id:
        props.mode === 'edit'
            ? (props.invoice!.organization_location?.id ?? null)
            : (props.defaults?.organization_location_id ?? null),
    customer_location_id:
        props.mode === 'edit'
            ? (props.invoice!.customer_location?.id ?? null)
            : (null as number | null),
    customer_shipping_location_id:
        props.mode === 'edit'
            ? (props.invoice!.customer_shipping_location?.id ?? null)
            : (null as number | null),
    status: props.mode === 'edit' ? props.invoice!.status : 'draft',
    issued_at:
        props.mode === 'edit'
            ? (props.invoice!.issued_at?.split('T')[0] ?? '')
            : (props.defaults?.issued_at ?? ''),
    due_at:
        props.mode === 'edit'
            ? (props.invoice!.due_at?.split('T')[0] ?? '')
            : (props.defaults?.due_at ?? ''),
    invoice_numbering_series_id:
        props.defaults?.invoice_numbering_series_id ?? (null as number | null),
    notes: props.mode === 'edit' ? (props.invoice!.notes ?? '') : '',
    items: [] as LineItem[],
});

// Sync items into form before submit
function syncItems() {
    form.items = items.value.map((item) => ({
        ...item,
        tax_rate: item.tax_rate ?? 0,
    }));
}

// Customer change: auto-select primary location
watch(selectedCustomerId, (newId) => {
    form.customer_id = newId;

    if (!newId) {
        form.customer_location_id = null;
        form.customer_shipping_location_id = null;

        return;
    }

    const customer = props.customers.find((c) => c.id === newId);

    if (customer) {
        const primaryId = customer.primary_location_id;
        const locs = customer.locations ?? [];
        const fallback = primaryId ?? locs[0]?.id ?? null;
        form.customer_location_id = fallback;
        form.customer_shipping_location_id = fallback;
    }
});

function addItem() {
    items.value.push({
        description: '',
        sac_code: null,
        quantity: 1,
        unit_price: 0,
        tax_rate: 0,
    });
}

function removeItem(index: number) {
    if (items.value.length > 1) {
        items.value.splice(index, 1);
    }
}

function updateItem(index: number, item: LineItem) {
    items.value[index] = item;
}

function submit() {
    syncItems();

    if (props.mode === 'create') {
        form.post('/invoices', {
            preserveScroll: true,
        });
    } else {
        form.put(`/invoices/${props.invoice!.id}`, {
            preserveScroll: true,
        });
    }
}

function cancel() {
    router.get('/invoices');
}

const selectedBillingLocation = computed(() =>
    customerLocations.value.find((l) => l.id === form.customer_location_id),
);

const selectedShippingLocation = computed(() =>
    customerLocations.value.find(
        (l) => l.id === form.customer_shipping_location_id,
    ),
);

const page = usePage();
const flash = computed(() => (page.props as any).flash ?? {});
</script>

<template>
    <div class="px-4 py-6 sm:px-0">
        <!-- Action buttons (edit mode only) -->
        <div
            v-if="mode === 'edit' && invoice?.ulid"
            class="mb-6 flex justify-end"
        >
            <div class="flex space-x-2">
                <button
                    type="button"
                    class="rounded bg-purple-500 px-4 py-2 font-bold text-white hover:bg-purple-700"
                    @click="showEmailModal = true"
                >
                    Send Email
                </button>
                <a
                    :href="`/${invoice.type === 'invoice' ? 'invoices' : 'estimates'}/view/${invoice.ulid}`"
                    target="_blank"
                    class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700"
                >
                    View Public
                </a>
                <a
                    :href="`/${invoice.type === 'invoice' ? 'invoices' : 'estimates'}/${invoice.ulid}/pdf`"
                    class="inline-flex items-center gap-2 rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700"
                    @click="downloadingPdf = true"
                >
                    <svg
                        v-if="downloadingPdf"
                        class="h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                        ></path>
                    </svg>
                    {{ downloadingPdf ? 'Generating...' : 'Download PDF' }}
                </a>
            </div>
        </div>

        <!-- Flash message -->
        <div
            v-if="flash.success"
            class="mb-4 rounded border border-green-300 bg-green-100 p-4 text-green-700"
        >
            {{ flash.success }}
        </div>
        <div
            v-if="flash.error"
            class="mb-4 rounded border border-red-300 bg-red-100 p-4 text-red-700"
        >
            {{ flash.error }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Organization Location Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">
                    Organization Location
                </h2>
                <div v-if="organizationLocations.length > 0" class="max-w-md">
                    <label class="mb-1 block text-sm font-medium text-gray-700"
                        >Location *</label
                    >
                    <select
                        v-model="form.organization_location_id"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                    >
                        <option :value="null">Select location</option>
                        <option
                            v-for="loc in organizationLocations"
                            :key="loc.id"
                            :value="loc.id"
                        >
                            {{ loc.name }} - {{ loc.city }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.organization_location_id"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.organization_location_id }}
                    </p>
                </div>
                <div
                    v-else
                    class="max-w-md rounded-md border border-yellow-200 bg-yellow-50 p-3"
                >
                    <p class="text-sm text-yellow-700">
                        No locations found.
                        <a
                            href="/organizations"
                            class="font-medium underline"
                            target="_blank"
                            >Add a location</a
                        >
                    </p>
                </div>
            </div>

            <!-- Invoice Details Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">
                    Invoice Details
                </h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Document Type</label
                        >
                        <select
                            v-model="form.type"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            :disabled="mode === 'edit'"
                        >
                            <option value="invoice">Invoice</option>
                            <option value="estimate">Estimate</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Status *</label
                        >
                        <select
                            v-model="form.status"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        >
                            <option
                                v-for="(label, value) in statusOptions"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.status"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.status }}
                        </p>
                    </div>

                    <div
                        v-if="
                            type === 'invoice' &&
                            numberingSeries.length > 0 &&
                            mode === 'create'
                        "
                    >
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Numbering Series</label
                        >
                        <select
                            v-model="form.invoice_numbering_series_id"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        >
                            <option :value="null">Auto-select</option>
                            <option
                                v-for="series in numberingSeries"
                                :key="series.id"
                                :value="series.id"
                            >
                                {{ series.name }}
                                {{
                                    series.location
                                        ? `(${series.location.name})`
                                        : '(Org-wide)'
                                }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.invoice_numbering_series_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.invoice_numbering_series_id }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Issue Date</label
                        >
                        <input
                            v-model="form.issued_at"
                            type="date"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        />
                        <p
                            v-if="form.errors.issued_at"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.issued_at }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-medium text-gray-700"
                            >Due Date</label
                        >
                        <input
                            v-model="form.due_at"
                            type="date"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                        />
                        <p
                            v-if="form.errors.due_at"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.due_at }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Customer & Address Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Left: Customer & Billing -->
                    <div class="space-y-4">
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-gray-700"
                                >Customer *</label
                            >
                            <select
                                v-model="selectedCustomerId"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            >
                                <option :value="null">Select customer</option>
                                <option
                                    v-for="customer in customers"
                                    :key="customer.id"
                                    :value="customer.id"
                                >
                                    {{ customer.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.customer_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.customer_id }}
                            </p>

                            <div
                                v-if="customers.length === 0"
                                class="mt-2 rounded-md border border-brand-200 bg-brand-50 p-3"
                            >
                                <p class="text-sm text-brand-700">
                                    No customers found.
                                    <a
                                        href="/customers"
                                        class="font-medium underline"
                                        target="_blank"
                                        >Create your first customer</a
                                    >
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="
                                selectedCustomerId &&
                                customerLocations.length > 0
                            "
                            class="rounded-md border border-gray-200 bg-gray-50 p-4"
                        >
                            <h3
                                class="mb-2 text-sm font-semibold text-gray-700"
                            >
                                Billing Address
                            </h3>
                            <select
                                v-model="form.customer_location_id"
                                class="mb-3 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            >
                                <option :value="null">Select location</option>
                                <option
                                    v-for="loc in customerLocations"
                                    :key="loc.id"
                                    :value="loc.id"
                                >
                                    {{ loc.name }} - {{ loc.city
                                    }}{{
                                        selectedCustomer?.primary_location_id ===
                                        loc.id
                                            ? ' (Primary)'
                                            : ''
                                    }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.customer_location_id"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.customer_location_id }}
                            </p>

                            <div
                                v-if="selectedBillingLocation"
                                class="space-y-1 text-sm text-gray-600"
                            >
                                <p
                                    v-if="
                                        selectedBillingLocation.address_line_1
                                    "
                                >
                                    {{ selectedBillingLocation.address_line_1 }}
                                </p>
                                <p
                                    v-if="
                                        selectedBillingLocation.address_line_2
                                    "
                                >
                                    {{ selectedBillingLocation.address_line_2 }}
                                </p>
                                <p>
                                    {{ selectedBillingLocation.city }}
                                    <span v-if="selectedBillingLocation.state"
                                        >,
                                        {{
                                            selectedBillingLocation.state
                                        }}</span
                                    >
                                    <span
                                        v-if="
                                            selectedBillingLocation.postal_code
                                        "
                                    >
                                        -
                                        {{
                                            selectedBillingLocation.postal_code
                                        }}</span
                                    >
                                </p>
                                <p v-if="selectedBillingLocation.country">
                                    {{ selectedBillingLocation.country }}
                                </p>
                                <p
                                    v-if="selectedBillingLocation.gstin"
                                    class="mt-1 font-medium text-gray-700"
                                >
                                    <span class="text-gray-500">GSTIN:</span>
                                    {{ selectedBillingLocation.gstin }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Shipping -->
                    <div class="space-y-4">
                        <div
                            v-if="
                                selectedCustomerId &&
                                customerLocations.length > 0
                            "
                            class="mt-20 rounded-md border border-gray-200 bg-gray-50 p-4"
                        >
                            <h3
                                class="mb-2 text-sm font-semibold text-gray-700"
                            >
                                Shipping Address
                            </h3>
                            <select
                                v-model="form.customer_shipping_location_id"
                                class="mb-3 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                            >
                                <option :value="null">Select location</option>
                                <option
                                    v-for="loc in customerLocations"
                                    :key="loc.id"
                                    :value="loc.id"
                                >
                                    {{ loc.name }} - {{ loc.city
                                    }}{{
                                        selectedCustomer?.primary_location_id ===
                                        loc.id
                                            ? ' (Primary)'
                                            : ''
                                    }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.customer_shipping_location_id"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.customer_shipping_location_id }}
                            </p>

                            <div
                                v-if="selectedShippingLocation"
                                class="space-y-1 text-sm text-gray-600"
                            >
                                <p
                                    v-if="
                                        selectedShippingLocation.address_line_1
                                    "
                                >
                                    {{
                                        selectedShippingLocation.address_line_1
                                    }}
                                </p>
                                <p
                                    v-if="
                                        selectedShippingLocation.address_line_2
                                    "
                                >
                                    {{
                                        selectedShippingLocation.address_line_2
                                    }}
                                </p>
                                <p>
                                    {{ selectedShippingLocation.city }}
                                    <span v-if="selectedShippingLocation.state"
                                        >,
                                        {{
                                            selectedShippingLocation.state
                                        }}</span
                                    >
                                    <span
                                        v-if="
                                            selectedShippingLocation.postal_code
                                        "
                                    >
                                        -
                                        {{
                                            selectedShippingLocation.postal_code
                                        }}</span
                                    >
                                </p>
                                <p v-if="selectedShippingLocation.country">
                                    {{ selectedShippingLocation.country }}
                                </p>
                                <p
                                    v-if="selectedShippingLocation.gstin"
                                    class="mt-1 font-medium text-gray-700"
                                >
                                    <span class="text-gray-500">GSTIN:</span>
                                    {{ selectedShippingLocation.gstin }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Line Items
                    </h2>
                    <button
                        type="button"
                        class="rounded bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600"
                        @click="addItem"
                    >
                        Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Description *
                                </th>
                                <th
                                    class="w-24 px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Qty *
                                </th>
                                <th
                                    class="w-32 px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Unit Price
                                </th>
                                <th
                                    class="w-32 px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Tax Rate
                                </th>
                                <th
                                    class="w-32 px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Amount
                                </th>
                                <th
                                    class="w-16 px-4 py-3 text-center text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <ItemRow
                                v-for="(item, index) in items"
                                :key="index"
                                :item="item"
                                :index="index"
                                :currency="currentCurrency"
                                :tax-templates="taxTemplates"
                                :can-remove="items.length > 1"
                                :errors="form.errors"
                                @update="updateItem(index, $event)"
                                @remove="removeItem(index)"
                            />
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="mt-6 flex justify-end">
                    <div class="w-full space-y-2 md:w-1/3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium text-gray-900">{{
                                formatMoney(totals.subtotal, currentCurrency)
                            }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Tax:</span>
                            <span class="font-medium text-gray-900">{{
                                formatMoney(totals.tax, currentCurrency)
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between border-t border-gray-300 pt-2"
                        >
                            <span class="text-lg font-bold text-gray-900"
                                >Total:</span
                            >
                            <span class="text-lg font-bold text-brand-600">{{
                                formatMoney(totals.total, currentCurrency)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="rounded-lg bg-white p-6 shadow">
                <label class="mb-2 block text-sm font-medium text-gray-700"
                    >Customer Notes</label
                >
                <textarea
                    v-model="form.notes"
                    rows="4"
                    placeholder="Enter notes for the customer..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                />
                <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                    {{ form.errors.notes }}
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    Displayed on the PDF document.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4">
                <button
                    type="button"
                    class="rounded-md border border-gray-300 px-6 py-2 font-medium text-gray-700 hover:bg-gray-50"
                    @click="cancel"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-md bg-brand-600 px-6 py-2 font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{
                        form.processing
                            ? 'Saving...'
                            : mode === 'edit'
                              ? 'Update'
                              : 'Create'
                    }}
                    {{ type === 'estimate' ? 'Estimate' : 'Invoice' }}
                </button>
            </div>
        </form>

        <!-- Email Modal -->
        <EmailModal
            v-if="mode === 'edit' && invoice"
            :show="showEmailModal"
            :invoice="invoice"
            :customers="customers"
            @close="showEmailModal = false"
        />
    </div>
</template>
