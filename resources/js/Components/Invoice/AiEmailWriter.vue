<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { Loader2, Sparkles, Undo2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    /** Where to ask for the draft, e.g. /invoices/12/email-draft. */
    url: string;
    /** Sent with the tone, e.g. the selected template. */
    payload?: Record<string, unknown>;
}>();

const subject = defineModel<string>('subject', { required: true });
const body = defineModel<string>('body', { required: true });

const emit = defineEmits<{ written: [] }>();

const tones = [
    { value: 'friendly', label: 'Friendly' },
    { value: 'formal', label: 'Formal' },
    { value: 'firm', label: 'Firm' },
] as const;

const http = useHttp<
    { tone: string; note: string },
    { subject: string; body: string }
>({ tone: 'friendly', note: '' });

const failure = ref<string | null>(null);
const previous = ref<{ subject: string; body: string } | null>(null);

const error = computed(
    () =>
        failure.value ??
        (http.errors as Record<string, string | undefined>).draft ??
        null,
);

function write() {
    const before = { subject: subject.value, body: body.value };
    failure.value = null;

    http.transform((data) => ({ ...data, ...props.payload }))
        .post(props.url, {
            onSuccess: (draft) => {
                previous.value = before;
                subject.value = draft.subject;
                body.value = draft.body;
                emit('written');
            },
            onHttpException: () => {
                failure.value =
                    'The email could not be written just now. Try again, or write it yourself.';

                return false;
            },
        })
        .catch(() => {});
}

function undo() {
    if (previous.value) {
        subject.value = previous.value.subject;
        body.value = previous.value.body;
        previous.value = null;
    }
}
</script>

<template>
    <div
        class="rounded-lg border border-primary/25 bg-primary-soft/30 px-3 py-2.5"
    >
        <div class="flex flex-wrap items-center gap-2">
            <span
                class="flex items-center gap-1.5 text-sm font-medium text-foreground"
            >
                <Sparkles class="size-4 text-primary" />
                Write with AI
            </span>
            <div
                role="radiogroup"
                aria-label="Tone"
                class="flex rounded-md border border-input bg-background p-0.5"
            >
                <button
                    v-for="tone in tones"
                    :key="tone.value"
                    type="button"
                    role="radio"
                    :aria-checked="http.tone === tone.value"
                    class="rounded px-2.5 py-1 text-xs font-medium text-muted-foreground transition-colors aria-checked:bg-primary aria-checked:text-primary-foreground"
                    @click="http.tone = tone.value"
                >
                    {{ tone.label }}
                </button>
            </div>
            <input
                v-model="http.note"
                type="text"
                maxlength="500"
                aria-label="Note for AI"
                placeholder="Add a note, e.g. thank them for the quick reply"
                class="min-h-8 min-w-0 flex-1 rounded-md border border-input bg-background px-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:ring-ring"
                @keydown.enter.prevent="write"
            />
            <button
                type="button"
                :disabled="http.processing"
                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90 disabled:opacity-60"
                @click="write"
            >
                <Loader2 v-if="http.processing" class="size-4 animate-spin" />
                {{ http.processing ? 'Writing...' : 'Write' }}
            </button>
        </div>

        <p v-if="error" class="mt-2 text-xs text-destructive" role="alert">
            {{ error }}
        </p>
        <p
            v-else-if="previous"
            class="mt-2 flex items-center gap-2 text-xs text-soft-foreground"
            role="status"
        >
            Written by AI. Check it before sending.
            <button
                type="button"
                class="inline-flex items-center gap-1 font-medium text-primary hover:underline"
                @click="undo"
            >
                <Undo2 class="size-3" />
                Undo
            </button>
        </p>
    </div>
</template>
