<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { cn, isValidEmail } from '@/lib/utils';
import type { Contact } from '@/types';

const props = withDefaults(
    defineProps<{
        // Known contacts to offer as suggestions.
        suggestions?: Contact[];
        placeholder?: string;
        // No box of its own, for a row inside a grouped block.
        bare?: boolean;
    }>(),
    {
        suggestions: () => [],
        placeholder: 'Type email and press Enter',
        bare: false,
    },
);

// Selected email addresses.
const model = defineModel<string[]>({ required: true });

const query = ref('');
const open = ref(false);
const error = ref('');

// Clear the validation error as soon as the user edits the field again.
watch(query, () => {
    error.value = '';
});

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

    if (!model.value.includes(trimmed)) {
        model.value = [...model.value, trimmed];
    }

    query.value = '';
    error.value = '';
}

function addTyped() {
    addEmail(query.value);
}

// Tab commits the typed email (like Enter) when there is text, otherwise it
// falls through to normal focus navigation.
function onTab(event: KeyboardEvent) {
    if (query.value.trim()) {
        event.preventDefault();
        addTyped();
    }
}

// Leaving the field commits whatever was typed. Suggestion items use
// mousedown.prevent, so picking one does not trigger this blur.
function onBlur() {
    open.value = false;

    if (query.value.trim()) {
        addTyped();
    }
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
            :class="
                cn(
                    'flex flex-wrap items-center gap-1.5',
                    bare
                        ? 'min-h-9'
                        : 'min-h-[44px] rounded-md border border-input p-2 focus-within:border-ring focus-within:ring-1 focus-within:ring-ring',
                )
            "
        >
            <div
                v-for="email in model"
                :key="email"
                class="inline-flex items-center gap-1 rounded-full bg-secondary py-0.5 pr-1 pl-2.5"
            >
                <span class="text-sm text-soft-foreground">{{ email }}</span>
                <button
                    type="button"
                    class="rounded-full p-0.5 text-muted-foreground hover:bg-border hover:text-soft-foreground"
                    :aria-label="`Remove ${email}`"
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
                class="min-w-[200px] flex-1 border-none bg-transparent p-1 text-sm outline-none placeholder:text-muted-foreground focus:ring-0 pointer-coarse:text-base"
                @focus="open = true"
                @blur="onBlur"
                @keydown.enter.prevent="addTyped"
                @keydown.tab="onTab"
                @keydown.esc="open = false"
            />
        </div>

        <p v-if="error" class="mt-1 text-xs text-destructive">{{ error }}</p>

        <!-- Suggestions from the contact list (click to add). -->
        <ul
            v-if="open && availableSuggestions.length > 0"
            class="absolute z-10 mt-1 max-h-48 w-full overflow-y-auto rounded-md border border-border bg-popover py-1 text-popover-foreground shadow-lg"
        >
            <li v-for="contact in availableSuggestions" :key="contact.email">
                <!-- mousedown.prevent keeps the input focused so @blur doesn't
                     close the list before the click registers. -->
                <button
                    type="button"
                    class="flex w-full flex-col items-start px-3 py-1.5 text-left hover:bg-muted"
                    @mousedown.prevent
                    @click="selectSuggestion(contact)"
                >
                    <span v-if="contact.name" class="text-sm text-foreground">{{
                        contact.name
                    }}</span>
                    <span class="text-xs text-muted-foreground">{{
                        contact.email
                    }}</span>
                </button>
            </li>
        </ul>
    </div>
</template>
