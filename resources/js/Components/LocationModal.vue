<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import type { Location } from '@/types';
import { useTaxLabel, type TaxLabels } from '@/composables/useTaxLabel';

const props = defineProps<{
    show: boolean;
    customerId: number;
    location?: Location | null;
    countries: Record<string, string>;
    taxLabels: TaxLabels;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const form = useForm({
    name: '',
    gstin: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    country: '',
    postal_code: '',
});

const fieldErrors = ref<Record<string, string>>({});

const formCountry = computed(() => form.country);
const { label: taxLabel, isIndia } = useTaxLabel(formCountry, props.taxLabels);

function validateGstin() {
    const val = form.gstin.trim();

    if (!val) {
        delete fieldErrors.value.gstin;

        return;
    }

    if (isIndia.value) {
        const gstinRegex =
            /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;

        if (!gstinRegex.test(val.toUpperCase())) {
            fieldErrors.value.gstin =
                'Invalid GSTIN format. Expected: 29ABCDE1234F1Z5 (15 characters)';

            return;
        }
    }

    delete fieldErrors.value.gstin;
}

function validateRequired(field: string, label: string) {
    const val = (form as any)[field]?.trim?.() ?? '';

    if (!val) {
        fieldErrors.value[field] = `${label} is required`;
    } else {
        delete fieldErrors.value[field];
    }
}

watch(
    () => props.show,
    (val) => {
        if (val && props.location) {
            form.name = props.location.name ?? '';
            form.gstin = props.location.gstin ?? '';
            form.address_line_1 = props.location.address_line_1;
            form.address_line_2 = props.location.address_line_2 ?? '';
            form.city = props.location.city;
            form.state = props.location.state;
            form.country = props.location.country;
            form.postal_code = props.location.postal_code ?? '';
        } else if (val) {
            form.reset();
        }
    },
);

const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
});

function submit() {
    if (props.location?.id) {
        form.put(
            `/customers/${props.customerId}/locations/${props.location.id}`,
            {
                preserveScroll: true,
                onSuccess: () => emit('close'),
            },
        );
    } else {
        form.post(`/customers/${props.customerId}/locations`, {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    }
}
</script>

<template>
    <Teleport v-if="isMounted && show" to="body">
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div
                class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
            >
                <div
                    class="bg-opacity-75 fixed inset-0 bg-gray-500 transition-opacity"
                    @click="emit('close')"
                />
                <span
                    class="hidden sm:inline-block sm:h-screen sm:align-middle"
                    aria-hidden="true"
                    >&#8203;</span
                >
                <div
                    class="relative inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="
                        location?.id ? 'Edit Location' : 'Add Location'
                    "
                >
                    <form @submit.prevent="submit">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <h3
                                class="mb-4 text-lg leading-6 font-medium text-gray-900"
                            >
                                {{
                                    location?.id
                                        ? 'Edit Location'
                                        : 'Add Location'
                                }}
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Location Name *</label
                                    >
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                        :class="
                                            fieldErrors.name
                                                ? 'border-red-300'
                                                : 'border-gray-300'
                                        "
                                        placeholder="e.g., Head Office"
                                        @blur="
                                            validateRequired(
                                                'name',
                                                'Location name',
                                            )
                                        "
                                    />
                                    <p
                                        v-if="fieldErrors.name"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ fieldErrors.name }}
                                    </p>
                                    <p
                                        v-else-if="form.errors.name"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Country *</label
                                    >
                                    <select
                                        v-model="form.country"
                                        class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                        :class="
                                            fieldErrors.country
                                                ? 'border-red-300'
                                                : 'border-gray-300'
                                        "
                                        @change="
                                            validateRequired(
                                                'country',
                                                'Country',
                                            )
                                        "
                                    >
                                        <option value="">Select country</option>
                                        <option
                                            v-for="(label, value) in countries"
                                            :key="value"
                                            :value="value"
                                        >
                                            {{ label }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="fieldErrors.country"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ fieldErrors.country }}
                                    </p>
                                    <p
                                        v-else-if="form.errors.country"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.country }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >{{ taxLabel }}</label
                                    >
                                    <input
                                        v-model="form.gstin"
                                        type="text"
                                        class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                        :class="
                                            fieldErrors.gstin
                                                ? 'border-red-300'
                                                : 'border-gray-300'
                                        "
                                        :placeholder="
                                            isIndia
                                                ? 'e.g., 29ABCDE1234F1Z5'
                                                : ''
                                        "
                                        :maxlength="isIndia ? 15 : 50"
                                        @blur="validateGstin"
                                        @input="
                                            form.gstin = isIndia
                                                ? (
                                                      $event.target as HTMLInputElement
                                                  ).value.toUpperCase()
                                                : (
                                                      $event.target as HTMLInputElement
                                                  ).value
                                        "
                                    />
                                    <p
                                        v-if="fieldErrors.gstin"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ fieldErrors.gstin }}
                                    </p>
                                    <p
                                        v-else-if="form.errors.gstin"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.gstin }}
                                    </p>
                                    <p
                                        v-else-if="isIndia"
                                        class="mt-1 text-xs text-gray-400"
                                    >
                                        15-character alphanumeric (e.g.,
                                        29ABCDE1234F1Z5)
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Address Line 1 *</label
                                    >
                                    <input
                                        v-model="form.address_line_1"
                                        type="text"
                                        class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                        :class="
                                            fieldErrors.address_line_1
                                                ? 'border-red-300'
                                                : 'border-gray-300'
                                        "
                                        @blur="
                                            validateRequired(
                                                'address_line_1',
                                                'Address',
                                            )
                                        "
                                    />
                                    <p
                                        v-if="fieldErrors.address_line_1"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ fieldErrors.address_line_1 }}
                                    </p>
                                    <p
                                        v-else-if="form.errors.address_line_1"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ form.errors.address_line_1 }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Address Line 2</label
                                    >
                                    <input
                                        v-model="form.address_line_2"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                    />
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >City *</label
                                        >
                                        <input
                                            v-model="form.city"
                                            type="text"
                                            class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                            :class="
                                                fieldErrors.city
                                                    ? 'border-red-300'
                                                    : 'border-gray-300'
                                            "
                                            @blur="
                                                validateRequired('city', 'City')
                                            "
                                        />
                                        <p
                                            v-if="fieldErrors.city"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ fieldErrors.city }}
                                        </p>
                                        <p
                                            v-else-if="form.errors.city"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ form.errors.city }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >State *</label
                                        >
                                        <input
                                            v-model="form.state"
                                            type="text"
                                            class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                            :class="
                                                fieldErrors.state
                                                    ? 'border-red-300'
                                                    : 'border-gray-300'
                                            "
                                            @blur="
                                                validateRequired(
                                                    'state',
                                                    'State',
                                                )
                                            "
                                        />
                                        <p
                                            v-if="fieldErrors.state"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ fieldErrors.state }}
                                        </p>
                                        <p
                                            v-else-if="form.errors.state"
                                            class="mt-1 text-sm text-red-600"
                                        >
                                            {{ form.errors.state }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >Postal Code</label
                                        >
                                        <input
                                            v-model="form.postal_code"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
                        >
                            <button
                                type="submit"
                                class="inline-flex w-full justify-center rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 disabled:opacity-50 sm:ml-3 sm:w-auto"
                                :disabled="form.processing"
                            >
                                {{
                                    form.processing
                                        ? 'Saving...'
                                        : (location?.id ? 'Update' : 'Add') +
                                          ' Location'
                                }}
                            </button>
                            <button
                                type="button"
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto"
                                @click="emit('close')"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>
