<script setup lang="ts">
/* eslint-disable vue/no-mutating-props -- InertiaForm is designed to be mutated by child components */
import type { InertiaForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Contact } from '@/types';

const props = defineProps<{
    form: InertiaForm<{
        name: string;
        phone: string;
        currency: string;
        contacts: Contact[];
    }>;
    currencies: Record<string, string>;
    countries: Record<string, string>;
    isEditing: boolean;
}>();

const emit = defineEmits<{
    (e: 'submit'): void;
    (e: 'cancel'): void;
}>();

const contactErrors = ref<Record<string, string>>({});
const nameError = ref('');

function validateEmail(email: string, index: number) {
    const key = `contact_${index}_email`;

    if (!email) {
        delete contactErrors.value[key];

        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(email)) {
        contactErrors.value[key] = 'Please enter a valid email address';
    } else {
        delete contactErrors.value[key];
    }
}

function validateName(index: number) {
    const key = `contact_${index}_name`;
    const contact = props.form.contacts[index];

    if (contact.email && !contact.name) {
        contactErrors.value[key] = 'Name is required when email is provided';
    } else {
        delete contactErrors.value[key];
    }
}

function validateCustomerName() {
    nameError.value = props.form.name.trim() ? '' : 'Customer name is required';
}

function addContact() {
    props.form.contacts.push({ name: '', email: '' });
}

function removeContact(index: number) {
    if (props.form.contacts.length > 1) {
        props.form.contacts.splice(index, 1);
    }
}
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <!-- Name -->
        <div>
            <label
                for="customer-name"
                class="block text-sm font-medium text-gray-700"
                >Name *</label
            >
            <input
                id="customer-name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                :class="nameError ? 'border-red-300' : 'border-gray-300'"
                @blur="validateCustomerName"
            />
            <p v-if="nameError" class="mt-1 text-sm text-red-600">
                {{ nameError }}
            </p>
            <p v-else-if="form.errors.name" class="mt-1 text-sm text-red-600">
                {{ form.errors.name }}
            </p>
        </div>

        <!-- Phone -->
        <div>
            <label
                for="customer-phone"
                class="block text-sm font-medium text-gray-700"
                >Phone</label
            >
            <input
                id="customer-phone"
                v-model="form.phone"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                placeholder="+91 12345 67890"
            />
            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                {{ form.errors.phone }}
            </p>
        </div>

        <!-- Currency -->
        <div>
            <label
                for="customer-currency"
                class="block text-sm font-medium text-gray-700"
                >Currency *</label
            >
            <select
                id="customer-currency"
                v-model="form.currency"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
            >
                <option
                    v-for="(label, value) in currencies"
                    :key="value"
                    :value="value"
                >
                    {{ label }}
                </option>
            </select>
            <p v-if="form.errors.currency" class="mt-1 text-sm text-red-600">
                {{ form.errors.currency }}
            </p>
        </div>

        <!-- Contacts -->
        <div>
            <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-gray-700"
                    >Contacts</label
                >
                <button
                    type="button"
                    class="text-sm text-brand-600 hover:text-brand-700"
                    @click="addContact"
                >
                    + Add Contact
                </button>
            </div>
            <div
                v-for="(contact, index) in form.contacts"
                :key="index"
                class="mt-2 flex items-start gap-3"
            >
                <div class="flex-1">
                    <input
                        v-model="contact.name"
                        type="text"
                        placeholder="Contact name"
                        class="block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                        :class="
                            contactErrors[`contact_${index}_name`]
                                ? 'border-red-300'
                                : 'border-gray-300'
                        "
                        @blur="validateName(index)"
                    />
                    <p
                        v-if="contactErrors[`contact_${index}_name`]"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ contactErrors[`contact_${index}_name`] }}
                    </p>
                </div>
                <div class="flex-1">
                    <input
                        v-model="contact.email"
                        type="email"
                        placeholder="Email *"
                        class="block w-full rounded-md shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                        :class="
                            contactErrors[`contact_${index}_email`]
                                ? 'border-red-300'
                                : 'border-gray-300'
                        "
                        @blur="validateEmail(contact.email, index)"
                    />
                    <p
                        v-if="contactErrors[`contact_${index}_email`]"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ contactErrors[`contact_${index}_email`] }}
                    </p>
                    <p
                        v-if="(form.errors as any)[`contacts.${index}.email`]"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ (form.errors as any)[`contacts.${index}.email`] }}
                    </p>
                </div>
                <button
                    v-if="form.contacts.length > 1"
                    type="button"
                    class="mt-1 text-red-500 hover:text-red-700"
                    @click="removeContact(index)"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
            <button
                type="button"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                @click="emit('cancel')"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 disabled:opacity-50"
                :disabled="form.processing"
            >
                {{
                    form.processing
                        ? 'Saving...'
                        : isEditing
                          ? 'Update Customer'
                          : 'Create Customer'
                }}
            </button>
        </div>
    </form>
</template>
