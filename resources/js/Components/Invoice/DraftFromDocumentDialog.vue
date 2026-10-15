<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileText, Paperclip, Sparkles, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogBody,
    DialogFooter,
    DialogHeader,
} from '@/Components/ui/dialog';
import { Textarea } from '@/Components/ui/textarea';

const props = withDefaults(
    defineProps<{ show: boolean; type?: 'invoice' | 'estimate' }>(),
    { type: 'invoice' },
);

const emit = defineEmits<{ close: [] }>();

const form = useForm<{
    type: 'invoice' | 'estimate';
    text: string;
    file: File | null;
}>({
    type: props.type,
    text: '',
    file: null,
});

const placeholder = computed(() =>
    props.type === 'estimate'
        ? 'Could you quote us for redesigning our five-page website? We would like it live by December.'
        : 'Please invoice us for 40 hours of development in October at ₹2,000 an hour. Our PO is 4512.',
);

const description = computed(() =>
    props.type === 'estimate'
        ? "Paste a client's enquiry or attach their brief or request for quote. AI fills in the estimate for you to check."
        : 'Paste a message or attach a purchase order, quote or timesheet. AI fills in the invoice for you to check.',
);

const fileInput = ref<HTMLInputElement | null>(null);

const canDraft = computed(
    () => !form.processing && (form.text.trim() !== '' || form.file !== null),
);

// "draft" carries a failed or rate-limited request; the others are validation.
const error = computed(() => {
    const errors = form.errors as Record<string, string | undefined>;

    return errors.draft ?? errors.text ?? errors.file ?? null;
});

function pickFile(event: Event) {
    form.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function clearFile() {
    form.file = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

// On success the server opens the create form with the draft, which remounts
// this page; only a failure keeps the dialog and what was entered.
function draft() {
    form.post('/invoices/draft', {
        forceFormData: true,
        preserveState: 'errors',
        preserveScroll: 'errors',
    });
}
</script>

<template>
    <Dialog
        :open="show"
        size="md"
        aria-labelledby="draft-from-document-title"
        @close="emit('close')"
    >
        <DialogHeader
            title-id="draft-from-document-title"
            title="Draft from a document"
            :description="description"
            @close="emit('close')"
        >
            <template #icon>
                <Sparkles />
            </template>
        </DialogHeader>

        <DialogBody>
            <div>
                <label
                    for="draft-text"
                    class="mb-1.5 block text-sm font-medium text-soft-foreground"
                >
                    Paste text
                </label>
                <Textarea
                    id="draft-text"
                    v-model="form.text"
                    rows="7"
                    :placeholder="placeholder"
                />
            </div>

            <div>
                <span
                    class="mb-1.5 block text-sm font-medium text-soft-foreground"
                >
                    Or attach a file
                </span>
                <div
                    v-if="form.file"
                    class="flex items-center gap-3 rounded-lg border border-border bg-background px-3 py-2.5 text-sm"
                >
                    <FileText class="size-4 shrink-0 text-primary" />
                    <span class="min-w-0 flex-1 truncate">{{
                        form.file.name
                    }}</span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7 text-muted-foreground"
                        aria-label="Remove file"
                        @click="clearFile"
                    >
                        <X />
                    </Button>
                </div>
                <label
                    v-else
                    for="draft-file"
                    class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-input px-3 py-4 text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                >
                    <Paperclip class="size-4" />
                    PDF up to 10 MB, or an image up to 7 MB
                </label>
                <input
                    id="draft-file"
                    ref="fileInput"
                    type="file"
                    accept=".pdf,.png,.jpg,.jpeg,.webp"
                    class="sr-only"
                    @change="pickFile"
                />
            </div>

            <p v-if="error" class="text-sm text-destructive" role="alert">
                {{ error }}
            </p>
        </DialogBody>

        <DialogFooter>
            <template #meta>
                Read by AI. Nothing is saved until you save the {{ type }}.
            </template>
            <Button variant="outline" @click="emit('close')">Cancel</Button>
            <Button :disabled="!canDraft" @click="draft">
                <Sparkles />
                {{ form.processing ? 'Reading...' : `Draft ${type}` }}
            </Button>
        </DialogFooter>
    </Dialog>
</template>
