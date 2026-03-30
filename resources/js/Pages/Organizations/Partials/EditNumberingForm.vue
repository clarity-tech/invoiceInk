<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import type { InvoiceNumberingSeries, ResetFrequency } from '@/types';

const props = defineProps<{
    organizationId: number;
    series: InvoiceNumberingSeries[];
    resetFrequencyOptions: Record<string, string>;
}>();

const showForm = ref(false);
const editingSeries = ref<InvoiceNumberingSeries | null>(null);
const previewNumber = ref('');

const form = useForm({
    organization_id: props.organizationId,
    location_id: null as number | null,
    name: '',
    prefix: 'INV',
    format_pattern: '{PREFIX}-{FY}-{SEQUENCE:4}',
    current_number: 0,
    reset_frequency: 'financial_year' as ResetFrequency,
    is_active: true,
    is_default: false,
});

function openCreate() {
    editingSeries.value = null;
    form.reset();
    form.organization_id = props.organizationId;
    form.prefix = 'INV';
    form.format_pattern = '{PREFIX}-{FY}-{SEQUENCE:4}';
    form.reset_frequency = 'financial_year';
    form.is_active = true;
    form.clearErrors();
    showForm.value = true;
}

function openEdit(s: InvoiceNumberingSeries) {
    editingSeries.value = s;
    form.organization_id = props.organizationId;
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

const confirmingDelete = ref(false);
const seriesToDelete = ref<InvoiceNumberingSeries | null>(null);

function confirmDelete(s: InvoiceNumberingSeries) {
    seriesToDelete.value = s;
    confirmingDelete.value = true;
}

function deleteSeries() {
    if (!seriesToDelete.value) return;
    router.delete(`/numbering-series/${seriesToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            confirmingDelete.value = false;
            seriesToDelete.value = null;
        },
    });
}

const confirmingToggle = ref(false);
const seriesToToggle = ref<InvoiceNumberingSeries | null>(null);

function confirmToggle(s: InvoiceNumberingSeries) {
    seriesToToggle.value = s;
    confirmingToggle.value = true;
}

function toggleActive() {
    if (!seriesToToggle.value) return;
    router.post(
        `/numbering-series/${seriesToToggle.value.id}/toggle-active`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmingToggle.value = false;
                seriesToToggle.value = null;
            },
        },
    );
}

function setAsDefault(s: InvoiceNumberingSeries) {
    router.post(
        `/numbering-series/${s.id}/set-default`,
        {},
        { preserveScroll: true },
    );
}

let previewTimeout: ReturnType<typeof setTimeout> | null = null;

function fetchPreview() {
    if (!form.prefix || !form.format_pattern) {
        previewNumber.value = '';
        return;
    }
    if (previewTimeout) clearTimeout(previewTimeout);
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
            previewNumber.value = '';
        }
    }, 300);
}

watch(
    () => [
        form.prefix,
        form.format_pattern,
        form.current_number,
        form.reset_frequency,
    ],
    fetchPreview,
);

const formatTokens = [
    { token: '{PREFIX}', description: 'Series prefix' },
    { token: '{YEAR}', description: 'Full year (2026)' },
    { token: '{YEAR:2}', description: '2-digit year (26)' },
    { token: '{MONTH}', description: 'Month (01-12)' },
    { token: '{SEQUENCE:4}', description: 'Padded sequence (0001)' },
    { token: '{FY}', description: 'Financial year (2025-26)' },
    { token: '{FY_START}', description: 'FY start year' },
    { token: '{FY_END}', description: 'FY end year' },
];
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h3 class="text-base font-semibold text-gray-900">
                    Numbering Series
                </h3>
                <p class="mt-0.5 text-sm text-gray-500">
                    Configure how invoice and estimate numbers are generated.
                </p>
            </div>
            <button
                v-if="!showForm"
                type="button"
                class="rounded-md bg-brand-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-brand-700"
                @click="openCreate"
            >
                Add Series
            </button>
        </div>

        <div class="p-6">
            <!-- Create/Edit Form -->
            <div v-if="showForm" class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-5">
                <h4 class="mb-4 text-sm font-semibold text-gray-900">
                    {{ editingSeries ? 'Edit Series' : 'New Series' }}
                </h4>
                <form class="space-y-4" @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name *</label>
                            <input
                                id="ns-name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="e.g. Default Invoice Series"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Prefix *</label>
                            <input
                                id="ns-prefix"
                                v-model="form.prefix"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="INV"
                            />
                            <p v-if="form.errors.prefix" class="mt-1 text-xs text-red-600">{{ form.errors.prefix }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Format Pattern *</label>
                            <input
                                id="ns-format_pattern"
                                v-model="form.format_pattern"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="{PREFIX}-{FY}-{SEQUENCE:4}"
                            />
                            <p v-if="form.errors.format_pattern" class="mt-1 text-xs text-red-600">{{ form.errors.format_pattern }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reset Frequency *</label>
                            <select
                                id="ns-reset_frequency"
                                v-model="form.reset_frequency"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                            >
                                <option
                                    v-for="(label, value) in resetFrequencyOptions"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Number</label>
                            <input
                                v-model.number="form.current_number"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                            />
                        </div>
                        <div class="flex items-end gap-4">
                            <label class="inline-flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-brand-600 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                                <span class="ml-2 text-sm text-gray-600">Active</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input
                                    v-model="form.is_default"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-brand-600 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                                <span class="ml-2 text-sm text-gray-600">Default</span>
                            </label>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div v-if="previewNumber" class="rounded-md bg-white p-3 ring-1 ring-gray-200">
                        <span class="text-xs font-medium text-gray-500">Next number preview:</span>
                        <span class="ml-2 font-mono text-sm text-brand-600">{{ previewNumber }}</span>
                    </div>

                    <!-- Token Reference -->
                    <details class="rounded-md border border-gray-200 text-sm">
                        <summary class="cursor-pointer px-3 py-2 font-medium text-gray-700">Format Tokens</summary>
                        <div class="flex flex-wrap gap-2 px-3 pb-3 pt-1">
                            <span
                                v-for="t in formatTokens"
                                :key="t.token"
                                class="text-xs"
                                :title="t.description"
                            >
                                <code class="rounded bg-gray-100 px-1">{{ t.token }}</code>
                            </span>
                        </div>
                    </details>

                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-md bg-gray-200 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-300"
                            @click="cancelForm"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-md bg-brand-600 px-3 py-1.5 text-sm text-white hover:bg-brand-700"
                            :disabled="form.processing"
                        >
                            {{ editingSeries ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Series List -->
            <div v-if="series.length" class="space-y-2">
                <div
                    v-for="s in series"
                    :key="s.id"
                    class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">{{ s.name }}</span>
                            <span
                                v-if="s.is_default"
                                class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-700"
                            >
                                Default
                            </span>
                            <span
                                v-if="!s.is_active"
                                class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700"
                            >
                                Inactive
                            </span>
                        </div>
                        <p class="mt-0.5 font-mono text-xs text-gray-500">
                            {{ s.format_pattern }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="text-sm text-brand-600 hover:text-brand-900"
                            @click="openEdit(s)"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            class="text-sm text-yellow-600 hover:text-yellow-900"
                            @click="confirmToggle(s)"
                        >
                            {{ s.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button
                            v-if="!s.is_default"
                            type="button"
                            class="text-sm text-brand-600 hover:text-brand-900"
                            @click="setAsDefault(s)"
                        >
                            Set Default
                        </button>
                        <button
                            type="button"
                            class="text-sm text-red-600 hover:text-red-900"
                            @click="confirmDelete(s)"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-gray-400 italic">
                No numbering series configured. A default will be created with your first invoice.
            </p>
        </div>

        <ConfirmationModal
            :show="confirmingToggle"
            :title="`${seriesToToggle?.is_active ? 'Deactivate' : 'Activate'} Numbering Series`"
            :message="`Are you sure you want to ${seriesToToggle?.is_active ? 'deactivate' : 'activate'} '${seriesToToggle?.name}'?`"
            :confirm-label="seriesToToggle?.is_active ? 'Deactivate' : 'Activate'"
            :destructive="seriesToToggle?.is_active ?? false"
            @confirm="toggleActive"
            @cancel="confirmingToggle = false"
        />

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
