<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { isValidEmail } from '@/lib/utils';
import type { Contact, EmailRecipient } from '@/types';

const props = withDefaults(
    defineProps<{
        // The customer's contacts. Any recipient matching one of these is shown
        // with its name and cannot be deleted (edit it on the customer instead);
        // recipients that aren't a contact are ad-hoc "extras" with a delete button.
        contacts?: Contact[];
        // Extra addresses to offer in the add-email dropdown (e.g. org contacts).
        suggestions?: Contact[];
    }>(),
    {
        contacts: () => [],
        suggestions: () => [],
    },
);

// The full remembered recipient list: contacts (checked/unchecked) plus extras.
const model = defineModel<EmailRecipient[]>({ required: true });

const query = ref('');
const open = ref(false);
const error = ref('');

// Clear the validation error as soon as the user edits the field again.
watch(query, () => {
    error.value = '';
});

const contactByEmail = computed(() => {
    const map = new Map<string, Contact>();

    for (const contact of props.contacts) {
        const email = contact.email?.trim().toLowerCase();

        if (email) {
            map.set(email, contact);
        }
    }

    return map;
});

function isContact(email: string): boolean {
    return contactByEmail.value.has(email.trim().toLowerCase());
}

function displayName(email: string): string {
    return contactByEmail.value.get(email.trim().toLowerCase())?.name ?? '';
}

function initialFor(email: string): string {
    const source = displayName(email) || email;

    return source.trim().charAt(0).toUpperCase() || '?';
}

const checkedCount = computed(
    () => model.value.filter((recipient) => recipient.checked).length,
);

const allChecked = computed(
    () => model.value.length > 0 && checkedCount.value === model.value.length,
);

function toggle(email: string): void {
    model.value = model.value.map((recipient) =>
        recipient.email === email
            ? { ...recipient, checked: !recipient.checked }
            : recipient,
    );
}

function toggleAll(): void {
    const checked = !allChecked.value;

    model.value = model.value.map((recipient) => ({ ...recipient, checked }));
}

function remove(email: string): void {
    model.value = model.value.filter((recipient) => recipient.email !== email);
}

function has(email: string): boolean {
    const key = email.trim().toLowerCase();

    return model.value.some(
        (recipient) => recipient.email.toLowerCase() === key,
    );
}

function addEmail(email: string): void {
    const trimmed = email.trim();

    if (!trimmed) {
        query.value = '';
        error.value = '';

        return;
    }

    if (!isValidEmail(trimmed)) {
        // Keep the text so the user can correct it, and surface the error.
        error.value = 'Please enter a valid email address';

        return;
    }

    if (!has(trimmed)) {
        model.value = [...model.value, { email: trimmed, checked: true }];
    }

    query.value = '';
    error.value = '';
}

function addTyped(): void {
    addEmail(query.value);
}

// Tab commits the typed email (like Enter) when there is text, otherwise it
// falls through to normal focus navigation.
function onTab(event: KeyboardEvent): void {
    if (query.value.trim()) {
        event.preventDefault();
        addTyped();
    }
}

// Leaving the field commits whatever was typed. Suggestion items use
// mousedown.prevent, so picking one does not trigger this blur.
function onBlur(): void {
    open.value = false;

    if (query.value.trim()) {
        addTyped();
    }
}

// Suggestions not already in the list, matching the typed query, deduped by email.
const availableSuggestions = computed(() => {
    const q = query.value.trim().toLowerCase();
    const seen = new Set<string>();

    return props.suggestions.filter((contact) => {
        const email = contact.email?.trim();

        if (!email || has(email) || seen.has(email.toLowerCase())) {
            return false;
        }

        seen.add(email.toLowerCase());

        if (!q) {
            return true;
        }

        return (
            email.toLowerCase().includes(q) ||
            contact.name?.toLowerCase().includes(q)
        );
    });
});
</script>

<template>
    <div>
        <div
            v-if="model.length > 0"
            class="mb-2 flex items-center justify-between"
        >
            <span class="text-xs text-gray-500">
                {{ checkedCount }} of {{ model.length }} selected
            </span>
            <button
                type="button"
                class="text-xs font-medium text-brand-600 hover:text-brand-700"
                @click="toggleAll"
            >
                {{ allChecked ? 'Deselect all' : 'Select all' }}
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <input
                    v-model="query"
                    type="email"
                    placeholder="Add email"
                    class="w-48 rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                    @focus="open = true"
                    @blur="onBlur"
                    @keydown.enter.prevent="addTyped"
                    @keydown.tab="onTab"
                    @keydown.esc="open = false"
                />

                <!-- Known contacts/org addresses not already in the list. -->
                <ul
                    v-if="open && availableSuggestions.length > 0"
                    class="absolute z-10 mt-1 max-h-48 w-56 overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
                >
                    <li
                        v-for="contact in availableSuggestions"
                        :key="contact.email"
                    >
                        <!-- mousedown.prevent keeps focus so @blur doesn't close
                             the list before the click registers. -->
                        <button
                            type="button"
                            class="flex w-full flex-col items-start px-3 py-1.5 text-left hover:bg-gray-50"
                            @mousedown.prevent
                            @click="addEmail(contact.email)"
                        >
                            <span
                                v-if="contact.name"
                                class="text-sm text-gray-900"
                                >{{ contact.name }}</span
                            >
                            <span class="text-xs text-gray-500">{{
                                contact.email
                            }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <label
                v-for="recipient in model"
                :key="recipient.email"
                class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border py-1 pr-2 pl-2.5 text-sm transition-colors"
                :class="
                    recipient.checked
                        ? 'border-brand-300 bg-brand-50'
                        : 'border-gray-300 bg-white'
                "
            >
                <input
                    type="checkbox"
                    :checked="recipient.checked"
                    class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                    @change="toggle(recipient.email)"
                />
                <span
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-200 text-[10px] font-medium text-gray-600"
                    aria-hidden="true"
                >
                    {{ initialFor(recipient.email) }}
                </span>
                <span class="whitespace-nowrap">
                    <span
                        v-if="displayName(recipient.email)"
                        class="font-medium text-gray-900"
                        >{{ displayName(recipient.email) }}</span
                    >
                    <span
                        class="text-gray-500"
                        :class="{ 'ml-1.5': displayName(recipient.email) }"
                        >{{
                            displayName(recipient.email)
                                ? `<${recipient.email}>`
                                : recipient.email
                        }}</span
                    >
                </span>
                <button
                    v-if="!isContact(recipient.email)"
                    type="button"
                    class="ml-0.5 rounded-full p-0.5 text-gray-400 hover:bg-gray-200 hover:text-red-600"
                    :aria-label="`Remove ${recipient.email}`"
                    @click.stop.prevent="remove(recipient.email)"
                >
                    <svg
                        class="h-3.5 w-3.5"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </label>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
