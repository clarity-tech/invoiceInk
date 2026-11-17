<script setup lang="ts">
import { BellRing, Layers, Loader2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import {
    customerReminderDialog,
    emailModalDialog,
} from '@/Components/lazyDialogs';
import { Button } from '@/Components/ui/button';
import { useLazyDialog } from '@/composables/useLazyDialog';
import type {
    CustomerReminderDraft,
    Invoice,
    InvoiceAttachment,
} from '@/types';

const props = defineProps<{
    invoice: Invoice;
    attachments: InvoiceAttachment[];
    templateType: string;
    /** Set when the customer owes on other invoices too. */
    customerReminder: CustomerReminderDraft | null;
    /** Open on arrival when the URL carries ?remind=1, as the listing's link does. */
    openFromUrl?: boolean;
}>();

// Both dialogs carry the email editor, so they load on first use rather than
// with the invoice page. See useLazyDialog.
const EmailModal = emailModalDialog.component;
const CustomerReminderModal = customerReminderDialog.component;
const emailDialog = useLazyDialog(emailModalDialog);
const reminderDialog = useLazyDialog(customerReminderDialog);

const showReminderModal = ref(false);
const showCustomerReminderModal = ref(false);

function remind() {
    emailDialog.open(() => {
        showReminderModal.value = true;
    });
}

function remindAboutAll() {
    reminderDialog.open(() => {
        showReminderModal.value = false;
        showCustomerReminderModal.value = true;
    });
}

onMounted(() => {
    if (
        props.openFromUrl &&
        new URLSearchParams(window.location.search).get('remind') === '1'
    ) {
        remind();
    }
});
</script>

<template>
    <div class="contents">
        <Button
            variant="outline"
            size="sm"
            :aria-busy="emailDialog.busy"
            v-on="emailDialog.intent"
            @click="remind"
        >
            <Loader2 v-if="emailDialog.busy" class="animate-spin" />
            <BellRing v-else />
            Remind
        </Button>

        <Teleport to="body">
            <EmailModal
                v-if="emailDialog.loaded"
                :show="showReminderModal"
                :invoice="invoice"
                :customers="invoice.customer ? [invoice.customer] : []"
                :invoice-attachments="attachments"
                :template-type="templateType"
                @close="showReminderModal = false"
            >
                <template v-if="customerReminder" #notice>
                    <div
                        class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-lg border border-primary/25 bg-primary-soft/40 px-3 py-2.5 text-sm"
                    >
                        <span
                            class="flex items-center gap-2 text-soft-foreground"
                        >
                            <Layers class="size-4 shrink-0 text-primary" />
                            {{ invoice.customer?.name }} has
                            {{ customerReminder.invoices.length }} unpaid
                            invoices.
                        </span>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 font-medium text-primary hover:underline"
                            :aria-busy="reminderDialog.busy"
                            v-on="reminderDialog.intent"
                            @click="remindAboutAll"
                        >
                            <Loader2
                                v-if="reminderDialog.busy"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            Send one combined reminder
                        </button>
                    </div>
                </template>
            </EmailModal>

            <CustomerReminderModal
                v-if="
                    reminderDialog.loaded &&
                    customerReminder &&
                    invoice.customer
                "
                :show="showCustomerReminderModal"
                :customer="invoice.customer"
                :reminder="customerReminder"
                :include-invoice-id="invoice.id"
                @close="showCustomerReminderModal = false"
            />
        </Teleport>
    </div>
</template>
