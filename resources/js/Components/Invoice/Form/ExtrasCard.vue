<script setup lang="ts">
import { computed, inject } from 'vue';
import { X } from 'lucide-vue-next';
import AttachmentDropzone from '../AttachmentDropzone.vue';
import RecipientEditor from '../RecipientEditor.vue';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Field } from '@/Components/ui/field';
import { Label } from '@/Components/ui/label';
import { Separator } from '@/Components/ui/separator';
import { Textarea } from '@/Components/ui/textarea';
import { formatBytes } from '@/composables/useFileAttachments';
import type { Contact, InvoiceAttachment } from '@/types';
import { InvoiceFormKey } from './formContext';

/**
 * Notes, terms, recipients and files: everything a document is complete
 * without. They were four full-width cards in a row, each holding one field
 * and each carrying the same visual weight as the items table above them.
 */
const props = defineProps<{
    type: 'invoice' | 'estimate';
    /** False until a customer is chosen -- there is nobody to address yet. */
    showRecipients: boolean;
    contacts: Contact[];
    suggestions: Contact[];
    attachments: InvoiceAttachment[];
}>();

const form = inject(InvoiceFormKey)!;

const noun = computed(() =>
    props.type === 'estimate' ? 'estimate' : 'invoice',
);

/** Removal is deferred to save, so a removed file disappears but is not gone. */
const visibleAttachments = computed(() =>
    props.attachments.filter(
        (a) => !form.removed_attachment_ids.includes(a.id),
    ),
);

function removeExistingAttachment(id: number) {
    if (!form.removed_attachment_ids.includes(id)) {
        form.removed_attachment_ids.push(id);
    }
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Notes &amp; attachments</CardTitle>
        </CardHeader>

        <CardContent class="space-y-5">
            <div class="grid gap-5 lg:grid-cols-2 lg:items-start">
                <Field
                    label="Customer Notes"
                    hint="Displayed on the PDF document."
                    :error="form.errors.notes"
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Textarea
                        :id="id"
                        v-model="form.notes"
                        rows="2"
                        placeholder="Enter notes for the customer..."
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    />
                </Field>

                <Field
                    label="Terms &amp; Conditions"
                    :error="form.errors.terms"
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Textarea
                        :id="id"
                        v-model="form.terms"
                        rows="2"
                        placeholder="e.g. Payment due within 7 days..."
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    />
                </Field>
            </div>

            <Separator />

            <!--
                `min-w-0` on both columns is load-bearing. A grid item defaults
                to `min-width: auto`, so it refuses to shrink below its content
                -- and a recipient chip carrying a full "Name <address>" is
                wider than a phone. Without it the column grows past the
                viewport and takes the page with it.
            -->
            <div class="grid gap-5 lg:grid-cols-2 lg:items-start">
                <div v-if="showRecipients" class="min-w-0 space-y-2">
                    <Label>Email Recipients</Label>
                    <p class="text-xs text-muted-foreground">
                        Selected contacts are pre-filled when you email this
                        {{ noun }}. Uncheck to skip a contact, or add extra
                        addresses just for this {{ noun }}.
                    </p>
                    <RecipientEditor
                        v-model="form.email_recipients"
                        :contacts="contacts"
                        :suggestions="suggestions"
                    />
                    <p
                        v-if="form.errors.email_recipients"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.email_recipients }}
                    </p>
                </div>

                <div class="min-w-0 space-y-2">
                    <Label>Attachments</Label>
                    <p class="text-xs text-muted-foreground">
                        Files saved with this {{ noun }}. They can be attached
                        when emailing it.
                    </p>

                    <!-- Existing (saved) attachments -->
                    <ul v-if="visibleAttachments.length > 0" class="space-y-1">
                        <li
                            v-for="attachment in visibleAttachments"
                            :key="attachment.id"
                            class="flex items-center justify-between gap-2 rounded-md border border-border bg-muted/40 px-3 py-2"
                        >
                            <div class="min-w-0 flex-1">
                                <a
                                    :href="attachment.url"
                                    target="_blank"
                                    rel="noopener"
                                    class="truncate text-sm font-medium text-primary hover:text-primary-soft-foreground hover:underline"
                                >
                                    {{ attachment.name }}
                                </a>
                                <div class="text-xs text-muted-foreground">
                                    {{ formatBytes(attachment.size) }}
                                </div>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="text-muted-foreground hover:text-destructive"
                                aria-label="Remove attachment"
                                @click="removeExistingAttachment(attachment.id)"
                            >
                                <X />
                            </Button>
                        </li>
                    </ul>

                    <!-- New files (uploaded on save) -->
                    <AttachmentDropzone v-model="form.attachments" />

                    <p
                        v-if="form.errors.attachments"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.attachments }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
