<script setup lang="ts">
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';

defineOptions({ layout: SettingsLayout });
import type {
    InvoiceNumberingSeries,
    Organization,
    ResetFrequency,
} from '@/types';

interface PaginatedSeries {
    data: (InvoiceNumberingSeries & { organization?: Organization })[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    series: PaginatedSeries;
    organizations: Organization[];
    resetFrequencyOptions: Record<string, string>;
}>();

const showForm = ref(false);
const editingSeries = ref<InvoiceNumberingSeries | null>(null);
const previewNumber = ref('');

const form = useForm({
    organization_id: null as number | null,
    location_id: null as number | null,
    name: '',
    prefix: 'INV',
    format_pattern: '{PREFIX}{YEAR}{MONTH}{SEQUENCE:4}',
    current_number: 0,
    reset_frequency: 'yearly' as ResetFrequency,
    is_active: true,
    is_default: false,
});

function openCreate() {
    editingSeries.value = null;
    form.reset();
    form.prefix = 'INV';
    form.format_pattern = '{PREFIX}{YEAR}{MONTH}{SEQUENCE:4}';
    form.reset_frequency = 'yearly';
    form.is_active = true;
    form.clearErrors();
    showForm.value = true;
}

function openEdit(s: InvoiceNumberingSeries) {
    editingSeries.value = s;
    form.organization_id = s.organization_id;
    form.location_id = s.location_id;
    form.name = s.name;
    form.prefix = s.prefix;
    form.format_pattern = s.format_pattern;
    form.current_number = s.current_number;
    form.reset_frequency = s.reset_frequency;
    form.is_active = s.is_active;
    form.is_default = s.is_default;
    form.clearErrors();
    showForm.value = true;
    fetchPreview();
}

function submitForm() {
    if (editingSeries.value) {
        form.put(`/numbering-series/${editingSeries.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false;
            },
        });
    } else {
        form.post('/numbering-series', {
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

// Delete
const confirmingDelete = ref(false);
const seriesToDelete = ref<InvoiceNumberingSeries | null>(null);

function confirmDelete(s: InvoiceNumberingSeries) {
    seriesToDelete.value = s;
    confirmingDelete.value = true;
}

function deleteSeries() {
    if (!seriesToDelete.value) {
        return;
    }

    router.delete(`/numbering-series/${seriesToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDelete.value = false;
            seriesToDelete.value = null;
        },
    });
}

function toggleActive(s: InvoiceNumberingSeries) {
    router.post(
        `/numbering-series/${s.id}/toggle-active`,
        {},
        { preserveScroll: true },
    );
}

function setAsDefault(s: InvoiceNumberingSeries) {
    router.post(
        `/numbering-series/${s.id}/set-default`,
        {},
        { preserveScroll: true },
    );
}

// Live preview
let previewTimeout: ReturnType<typeof setTimeout> | null = null;

function fetchPreview() {
    if (!form.organization_id || !form.prefix || !form.format_pattern) {
        previewNumber.value = '';

        return;
    }

    if (previewTimeout) {
        clearTimeout(previewTimeout);
    }

    previewTimeout = setTimeout(async () => {
        try {
            const response = await fetch('/numbering-series/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement
                        )?.content ?? '',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    organization_id: form.organization_id,
                    prefix: form.prefix,
                    format_pattern: form.format_pattern,
                    current_number: form.current_number,
                    reset_frequency: form.reset_frequency,
                }),
            });
            const data = await response.json();
            previewNumber.value = data.preview ?? '';
        } catch {
            previewNumber.value = 'Error loading preview';
        }
    }, 300);
}

watch(
    () => [
        form.organization_id,
        form.prefix,
        form.format_pattern,
        form.current_number,
        form.reset_frequency,
    ],
    fetchPreview,
);

const formatTokens = [
    { token: '{PREFIX}', description: 'Series prefix (INV, EST)' },
    { token: '{YEAR}', description: 'Full year (2026)' },
    { token: '{YEAR:2}', description: '2-digit year (26)' },
    { token: '{MONTH}', description: 'Month (01-12)' },
    { token: '{MONTH:3}', description: 'Month abbreviation (Jan)' },
    { token: '{DAY}', description: 'Day of month (01-31)' },
    { token: '{SEQUENCE}', description: 'Sequential number' },
    { token: '{SEQUENCE:4}', description: 'Padded sequence (0001)' },
    { token: '{FY}', description: 'Financial year (2025-26)' },
    { token: '{FY_START}', description: 'FY start year (2025)' },
    { token: '{FY_END}', description: 'FY end year (2026)' },
];
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between px-4 sm:px-0">
            <h2 class="text-xl font-semibold text-gray-900">
                Numbering Series
            </h2>
            <button
                v-if="!showForm"
                type="button"
                class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                @click="openCreate"
            >
                Create New Series
            </button>
        </div>

        <!-- Create/Edit Form -->
        <div
            v-if="showForm"
            class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg"
        >
            <div class="border-b border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-medium text-gray-900">
                    {{
                        editingSeries
                            ? 'Edit Numbering Series'
                            : 'Create New Numbering Series'
                    }}
                </h3>

                <form class="space-y-6" @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Organization -->
                        <div>
                            <label
                                for="organization_id"
                                class="block text-sm font-medium text-gray-700"
                                >Organization *</label
                            >
                            <select
                                id="organization_id"
                                v-model="form.organization_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option :value="null">
                                    Select Organization
                                </option>
                                <option
                                    v-for="org in organizations"
                                    :key="org.id"
                                    :value="org.id"
                                >
                                    {{ org.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.organization_id"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.organization_id }}
                            </p>
                        </div>

                        <!-- Name -->
                        <div>
                            <label
                                for="name"
                                class="block text-sm font-medium text-gray-700"
                                >Series Name *</label
                            >
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="e.g. Default Invoice Series"
                            />
                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Prefix -->
                        <div>
                            <label
                                for="prefix"
                                class="block text-sm font-medium text-gray-700"
                                >Prefix *</label
                            >
                            <input
                                id="prefix"
                                v-model="form.prefix"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="INV"
                            />
                            <p
                                v-if="form.errors.prefix"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.prefix }}
                            </p>
                        </div>

                        <!-- Format Pattern -->
                        <div>
                            <label
                                for="format_pattern"
                                class="block text-sm font-medium text-gray-700"
                                >Format Pattern *</label
                            >
                            <input
                                id="format_pattern"
                                v-model="form.format_pattern"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="{PREFIX}{YEAR}{MONTH}{SEQUENCE:4}"
                            />
                            <p
                                v-if="form.errors.format_pattern"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.format_pattern }}
                            </p>
                        </div>

                        <!-- Current Number -->
                        <div>
                            <label
                                for="current_number"
                                class="block text-sm font-medium text-gray-700"
                                >Current Number *</label
                            >
                            <input
                                id="current_number"
                                v-model.number="form.current_number"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
                            <p
                                v-if="form.errors.current_number"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.current_number }}
                            </p>
                        </div>

                        <!-- Reset Frequency -->
                        <div>
                            <label
                                for="reset_frequency"
                                class="block text-sm font-medium text-gray-700"
                                >Reset Frequency *</label
                            >
                            <select
                                id="reset_frequency"
                                v-model="form.reset_frequency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option
                                    v-for="(
                                        label, value
                                    ) in resetFrequencyOptions"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.reset_frequency"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.reset_frequency }}
                            </p>
                        </div>

                        <!-- Checkboxes -->
                        <div class="md:col-span-2">
                            <div class="flex items-center gap-6">
                                <label class="inline-flex items-center">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-brand-600 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-600"
                                        >Active</span
                                    >
                                </label>
                                <label class="inline-flex items-center">
                                    <input
                                        v-model="form.is_default"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-brand-600 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-600"
                                        >Default Series</span
                                    >
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div v-if="previewNumber" class="rounded-md bg-gray-50 p-4">
                        <h4 class="text-sm font-medium text-gray-900">
                            Next Number Preview
                        </h4>
                        <p class="font-mono text-lg text-brand-600">
                            {{ previewNumber }}
                        </p>
                    </div>

                    <!-- Format Tokens Reference -->
                    <details class="rounded-md border border-gray-200">
                        <summary
                            class="cursor-pointer px-4 py-2 text-sm font-medium text-gray-700"
                        >
                            Format Token Reference
                        </summary>
                        <div
                            class="grid grid-cols-2 gap-2 px-4 pt-2 pb-4 md:grid-cols-3"
                        >
                            <div
                                v-for="t in formatTokens"
                                :key="t.token"
                                class="text-sm"
                            >
                                <code
                                    class="rounded bg-gray-100 px-1 text-xs"
                                    >{{ t.token }}</code
                                >
                                <span class="ml-1 text-gray-500">{{
                                    t.description
                                }}</span>
                            </div>
                        </div>
                    </details>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-md bg-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-400"
                            @click="cancelForm"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-md bg-brand-600 px-4 py-2 text-sm text-white hover:bg-brand-700"
                            :disabled="form.processing"
                        >
                            {{ editingSeries ? 'Update' : 'Create' }} Series
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Series Table -->
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                        >
                            Organization
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                        >
                            Name
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                        >
                            Format
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                        >
                            Current #
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                        >
                            Status
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr
                        v-for="s in series.data"
                        :key="s.id"
                        class="hover:bg-gray-50"
                    >
                        <td
                            class="px-6 py-4 text-sm whitespace-nowrap text-gray-900"
                        >
                            {{ s.organization?.name ?? '-' }}
                        </td>
                        <td
                            class="px-6 py-4 text-sm whitespace-nowrap text-gray-900"
                        >
                            {{ s.name }}
                            <span
                                v-if="s.is_default"
                                class="ml-2 inline-flex items-center rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-800"
                                >Default</span
                            >
                        </td>
                        <td
                            class="px-6 py-4 font-mono text-sm whitespace-nowrap text-gray-900"
                        >
                            {{ s.format_pattern }}
                        </td>
                        <td
                            class="px-6 py-4 text-sm whitespace-nowrap text-gray-900"
                        >
                            {{ s.current_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    s.is_active
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                "
                            >
                                {{ s.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td
                            class="px-6 py-4 text-right text-sm whitespace-nowrap"
                        >
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="text-brand-600 hover:text-brand-900"
                                    @click="openEdit(s)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="text-yellow-600 hover:text-yellow-900"
                                    @click="toggleActive(s)"
                                >
                                    {{
                                        s.is_active ? 'Deactivate' : 'Activate'
                                    }}
                                </button>
                                <button
                                    v-if="!s.is_default"
                                    type="button"
                                    class="text-brand-600 hover:text-brand-900"
                                    @click="setAsDefault(s)"
                                >
                                    Set Default
                                </button>
                                <button
                                    type="button"
                                    class="text-red-600 hover:text-red-900"
                                    @click="confirmDelete(s)"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="series.data.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <p class="text-sm font-medium text-gray-900">
                                No numbering series found
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                A default series will be created automatically
                                when you create your first invoice.
                            </p>
                            <button
                                v-if="!showForm"
                                type="button"
                                class="mt-4 rounded-md bg-brand-600 px-4 py-2 text-sm text-white hover:bg-brand-700"
                                @click="openCreate"
                            >
                                Create Custom Series
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav
                v-if="series.last_page > 1"
                class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
            >
                <div class="hidden sm:block">
                    <p class="text-sm text-gray-700">
                        Showing page
                        <span class="font-medium">{{
                            series.current_page
                        }}</span>
                        of
                        <span class="font-medium">{{ series.last_page }}</span>
                    </p>
                </div>
                <div class="flex flex-1 justify-between gap-2 sm:justify-end">
                    <template v-for="link in series.links" :key="link.label">
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

        <!-- Delete Confirmation -->
        <ConfirmationModal
            :show="confirmingDelete"
            title="Delete Numbering Series"
            message="Are you sure you want to delete this numbering series? This action cannot be undone."
            confirm-label="Delete"
            :destructive="true"
            @confirm="deleteSeries"
            @cancel="confirmingDelete = false"
        />
    </div>
</template>
