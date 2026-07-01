<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Contact } from '@/types';

const props = withDefaults(
    defineProps<{
        // Known contacts to offer as suggestions.
        suggestions?: Contact[];
        placeholder?: string;
    }>(),
    {
        suggestions: () => [],
        placeholder: 'Type email and press Enter',
    },
);

// Selected email addresses.
const model = defineModel<string[]>({ required: true });

const query = ref('');
const open = ref(false);

// Suggestions not already selected, matching the typed query. Deduped by email
// so a contact known to both the customer and the org appears once.
const availableSuggestions = computed(() => {
    const q = query.value.trim().toLowerCase();
    const seen = new Set<string>();

    return props.suggestions.filter((contact) => {
        const email = contact.email?.trim();

        if (!email || model.value.includes(email) || seen.has(email)) {
            return false;
        }

        seen.add(email);

        if (!q) {
            return true;
        }

        return (
            email.toLowerCase().includes(q) ||
            contact.name?.toLowerCase().includes(q)
        );
    });
});

function addEmail(email: string) {
    const trimmed = email.trim();

    if (trimmed && !model.value.includes(trimmed)) {
        model.value = [...model.value, trimmed];
    }

    query.value = '';
}

function addTyped() {
    addEmail(query.value);
}

function selectSuggestion(contact: Contact) {
    addEmail(contact.email);
}

function removeEmail(email: string) {
    model.value = model.value.filter((e) => e !== email);
}
</script>

<template>
    <div class="relative">
        <div
            class="flex min-h-[44px] flex-wrap items-center gap-2 rounded-md border border-gray-300 p-2 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500"
        >
            <div
                v-for="email in model"
                :key="email"
                class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1"
            >
                <span class="text-sm text-gray-700">{{ email }}</span>
                <button
                    type="button"
                    class="text-gray-500 hover:text-gray-700"
                    @click="removeEmail(email)"
                >
                    <svg
                        class="h-4 w-4"
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
            </div>
            <input
                v-model="query"
                type="email"
                :placeholder="placeholder"
                class="min-w-[200px] flex-1 border-none p-1 text-sm outline-none focus:ring-0"
                @focus="open = true"
                @blur="open = false"
                @keydown.enter.prevent="addTyped"
                @keydown.esc="open = false"
            />
        </div>

        <!-- Suggestions from the contact list (click to add). -->
        <ul
            v-if="open && availableSuggestions.length > 0"
            class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
        >
            <li v-for="contact in availableSuggestions" :key="contact.email">
                <!-- mousedown.prevent keeps the input focused so @blur doesn't
                     close the list before the click registers. -->
                <button
                    type="button"
                    class="flex w-full flex-col items-start px-3 py-1.5 text-left hover:bg-gray-50"
                    @mousedown.prevent
                    @click="selectSuggestion(contact)"
                >
                    <span v-if="contact.name" class="text-sm text-gray-900">{{
                        contact.name
                    }}</span>
                    <span class="text-xs text-gray-500">{{
                        contact.email
                    }}</span>
                </button>
            </li>
        </ul>
    </div>
</template>
