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
    tax_id: '',
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
    const val = form.tax_id.trim();

    if (!val) {
        delete fieldErrors.value.tax_id;

        return;
    }

    if (isIndia.value) {
        const gstinRegex =
            /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;

        if (!gstinRegex.test(val.toUpperCase())) {
            fieldErrors.value.tax_id =
                'Invalid GSTIN format. Expected: 29ABCDE1234F1Z5 (15 characters)';

            return;
        }
    }

    delete fieldErrors.value.tax_id;
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
            form.tax_id = props.location.tax_id ?? '';
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
                    class="fixed inset-0 bg-overlay transition-opacity"
                    @click="emit('close')"
                />
                <span
                    class="hidden sm:inline-block sm:h-screen sm:align-middle"
                    aria-hidden="true"
                    >&#8203;</span
                >
                <div
                    class="relative inline-block transform overflow-hidden rounded-lg bg-card text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="
                        location?.id ? 'Edit Location' : 'Add Location'
                    "
                >
                    <form @submit.prevent="submit">
                        <div class="bg-card px-4 pt-5 pb-4 sm:p-6">
                            <h3
                                class="mb-4 text-lg leading-6 font-medium text-foreground"
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
                                        for="location-name"
                                        class="block text-sm font-medium text-soft-foreground"
                                        >Location Name *</label
                                    >
                                    <input
                                        id="location-name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                                        :class="
                                            fieldErrors.name
                                                ? 'border-destructive/40'
                                                : 'border-input'
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
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ fieldErrors.name }}
                                    </p>
                                    <p
                                        v-else-if="form.errors.name"
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        for="location-country"
                                        class="block text-sm font-medium text-soft-foreground"
                                        >Country *</label
                                    >
                                    <select
                                        id="location-country"
                                        v-model="form.country"
                                        class="mt-1 block w-full rounded-md shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                                        :class="
                                            fieldErrors.country
                                                ? 'border-destructive/40'
                                                : 'border-input'
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
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ fieldErrors.country }}
                                    </p>
                                    <p
                                        v-else-if="form.errors.country"
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ form.errors.country }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        for="location-tax-id"
                                        class="block text-sm font-medium text-soft-foreground"
                                        >{{ taxLabel }}</label
                                    >
                                    <input
                                        id="location-tax-id"
                                        v-model="form.tax_id"
                                        type="text"
                                        class="mt-1 block w-full rounded-md shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                                        :class="
                                            fieldErrors.tax_id
                                                ? 'border-destructive/40'
                                                : 'border-input'
                                        "
                                        :placeholder="
                                            isIndia
                                                ? 'e.g., 29ABCDE1234F1Z5'
                                                : ''
                                        "
                                        :maxlength="isIndia ? 15 : 50"
                                        @blur="validateGstin"
                                        @input="
                                            form.tax_id = isIndia
                                                ? (
                                                      $event.target as HTMLInputElement
                                                  ).value.toUpperCase()
                                                : (
                                                      $event.target as HTMLInputElement
                                                  ).value
                                        "
                                    />
                                    <p
                                        v-if="fieldErrors.tax_id"
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ fieldErrors.tax_id }}
                                    </p>
                                    <p
                                        v-else-if="form.errors.tax_id"
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ form.errors.tax_id }}
                                    </p>
                                    <p
                                        v-else-if="isIndia"
                                        class="mt-1 text-xs text-faint-foreground"
                                    >
                                        15-character alphanumeric (e.g.,
                                        29ABCDE1234F1Z5)
                                    </p>
                                </div>
                                <div>
                                    <label
                                        for="location-address-line-1"
                                        class="block text-sm font-medium text-soft-foreground"
                                        >Address Line 1 *</label
                                    >
                                    <input
                                        id="location-address-line-1"
                                        v-model="form.address_line_1"
                                        type="text"
                                        class="mt-1 block w-full rounded-md shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                                        :class="
                                            fieldErrors.address_line_1
                                                ? 'border-destructive/40'
                                                : 'border-input'
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
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ fieldErrors.address_line_1 }}
                                    </p>
                                    <p
                                        v-else-if="form.errors.address_line_1"
                                        class="mt-1 text-sm text-destructive"
                                    >
                                        {{ form.errors.address_line_1 }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        for="location-address-line-2"
                                        class="block text-sm font-medium text-soft-foreground"
                                        >Address Line 2</label
                                    >
                                    <input
                                        id="location-address-line-2"
                                        v-model="form.address_line_2"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                                    />
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label
                                            for="location-city"
                                            class="block text-sm font-medium text-soft-foreground"
                                            >City *</label
                                        >
                                        <input
                                            id="location-city"
                                            v-model="form.city"
                                            type="text"
                                            class="mt-1 block w-full rounded-md shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                                            :class="
                                                fieldErrors.city
                                                    ? 'border-destructive/40'
                                                    : 'border-input'
                                            "
                                            @blur="
                                                validateRequired('city', 'City')
                                            "
                                        />
                                        <p
                                            v-if="fieldErrors.city"
                                            class="mt-1 text-sm text-destructive"
                                        >
                                            {{ fieldErrors.city }}
                                        </p>
                                        <p
                                            v-else-if="form.errors.city"
                                            class="mt-1 text-sm text-destructive"
                                        >
                                            {{ form.errors.city }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="location-state"
                                            class="block text-sm font-medium text-soft-foreground"
                                            >State *</label
                                        >
                                        <input
                                            id="location-state"
                                            v-model="form.state"
                                            type="text"
                                            class="mt-1 block w-full rounded-md shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                                            :class="
                                                fieldErrors.state
                                                    ? 'border-destructive/40'
                                                    : 'border-input'
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
                                            class="mt-1 text-sm text-destructive"
                                        >
                                            {{ fieldErrors.state }}
                                        </p>
                                        <p
                                            v-else-if="form.errors.state"
                                            class="mt-1 text-sm text-destructive"
                                        >
                                            {{ form.errors.state }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            for="location-postal-code"
                                            class="block text-sm font-medium text-soft-foreground"
                                            >Postal Code</label
                                        >
                                        <input
                                            id="location-postal-code"
                                            v-model="form.postal_code"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="bg-muted px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
                        >
                            <button
                                type="submit"
                                class="inline-flex w-full justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 disabled:opacity-50 sm:ml-3 sm:w-auto"
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
                                class="mt-3 inline-flex w-full justify-center rounded-md border border-input bg-card px-4 py-2 text-sm font-medium text-soft-foreground shadow-sm hover:bg-muted sm:mt-0 sm:ml-3 sm:w-auto"
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
