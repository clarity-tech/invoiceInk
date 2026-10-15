<script setup lang="ts">
import { BellRing, Layers } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import CustomerReminderModal from './CustomerReminderModal.vue';
import EmailModal from './EmailModal.vue';
import { Button } from '@/Components/ui/button';
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

const showReminderModal = ref(false);
const showCustomerReminderModal = ref(false);

function remindAboutAll() {
    showReminderModal.value = false;
    showCustomerReminderModal.value = true;
}

onMounted(() => {
    if (
        props.openFromUrl &&
        new URLSearchParams(window.location.search).get('remind') === '1'
    ) {
        showReminderModal.value = true;
    }
});
</script>

<template>
    <div class="contents">
        <Button variant="outline" size="sm" @click="showReminderModal = true">
            <BellRing />
            Remind
        </Button>

        <Teleport to="body">
            <EmailModal
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
                            class="font-medium text-primary hover:underline"
                            @click="remindAboutAll"
                        >
                            Send one combined reminder
                        </button>
                    </div>
                </template>
            </EmailModal>

            <CustomerReminderModal
                v-if="customerReminder && invoice.customer"
                :show="showCustomerReminderModal"
                :customer="invoice.customer"
                :reminder="customerReminder"
                :include-invoice-id="invoice.id"
                @close="showCustomerReminderModal = false"
            />
        </Teleport>
    </div>
</template>
