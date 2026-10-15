<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { BellRing, Send } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import EmailRecipientInput from './EmailRecipientInput.vue';
import MoneyDisplay from '@/Components/MoneyDisplay.vue';
import TipTapEditor from '@/Components/TipTapEditor.vue';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogBody,
    DialogFooter,
    DialogHeader,
} from '@/Components/ui/dialog';
import { formatDate } from '@/composables/useFormatDate';
import { overdueLabel } from '@/composables/useOverdueLabel';
import type {
    Contact,
    Currency,
    Customer,
    CustomerReminderDraft,
} from '@/types';

const props = defineProps<{
    show: boolean;
    customer: Customer;
    reminder: CustomerReminderDraft;
    // Always selected, e.g. the invoice the dialog was opened from.
    includeInvoiceId?: number;
}>();

const emit = defineEmits<{
    close: [];
}>();

const page = usePage();

const customerContacts = computed<Contact[]>(() => props.customer.emails ?? []);
const orgContacts = computed<Contact[]>(
    () => (page.props as any).auth?.currentTeam?.emails ?? [],
);

const contactSuggestions = computed<Contact[]>(() => {
    const seen = new Set<string>();

    return [...customerContacts.value, ...orgContacts.value].filter(
        (contact) => {
            const email = contact.email?.trim();

            if (!email || seen.has(email)) {
                return false;
            }

            seen.add(email);

            return true;
        },
    );
});

const form = useForm({
    recipients: [] as string[],
    cc: [] as string[],
    subject: '',
    body: '',
    invoice_ids: [] as number[],
});

// The overdue invoices, or all of them when none is overdue yet.
function defaultSelection(): number[] {
    const invoices = props.reminder.invoices;
    const overdue = invoices.filter(
        (invoice) =>
            invoice.days_overdue !== null ||
            invoice.id === props.includeInvoiceId,
    );

    return (
        invoices.some((invoice) => invoice.days_overdue !== null)
            ? overdue
            : invoices
    ).map((invoice) => invoice.id);
}

const selectedNumbers = computed(() =>
    props.reminder.invoices
        .filter((invoice) => form.invoice_ids.includes(invoice.id))
        .map((invoice) => invoice.invoice_number)
        .join(', '),
);

// The subject follows the selection until it is edited by hand.
const subjectEdited = ref(false);

function fillSubject() {
    form.subject = props.reminder.subject
        .split('{{invoice_numbers}}')
        .join(selectedNumbers.value);
}

watch(selectedNumbers, () => {
    if (!subjectEdited.value) {
        fillSubject();
    }
});

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        form.recipients = customerContacts.value
            .map((contact) => contact.email)
            .filter(Boolean);
        form.cc = orgContacts.value.map((contact) => contact.email);
        form.body = props.reminder.body;
        form.invoice_ids = defaultSelection();
        subjectEdited.value = false;
        fillSubject();
        form.clearErrors();
    },
    { immediate: true },
);

// Per currency, like the email: amounts in different currencies never add up.
const totals = computed(() => {
    const sums = new Map<Currency, number>();

    props.reminder.invoices
        .filter((invoice) => form.invoice_ids.includes(invoice.id))
        .forEach((invoice) =>
            sums.set(
                invoice.currency,
                (sums.get(invoice.currency) ?? 0) + invoice.remaining_balance,
            ),
        );

    return [...sums].map(([currency, amount]) => ({ currency, amount }));
});

const sendSummary = computed(() => {
    const invoices = form.invoice_ids.length;
    const recipients = form.recipients.length;

    return `${invoices} invoice${invoices === 1 ? '' : 's'} · ${recipients} recipient${recipients === 1 ? '' : 's'}`;
});

const canSend = computed(
    () =>
        !form.processing &&
        form.recipients.length > 0 &&
        form.invoice_ids.length > 0,
);

function send() {
    form.post(`/customers/${props.customer.id}/send-reminder`, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Dialog
        :open="show"
        size="xl"
        aria-labelledby="customer-reminder-title"
        @close="emit('close')"
    >
        <DialogHeader
            title-id="customer-reminder-title"
            :title="`Remind ${customer.name}`"
            description="One email covering the invoices you select."
            @close="emit('close')"
        >
            <template #icon>
                <BellRing />
            </template>
        </DialogHeader>

        <DialogBody>
            <fieldset>
                <legend
                    class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Invoices in this reminder
                </legend>
                <div class="overflow-hidden rounded-lg border border-border">
                    <ul class="divide-y divide-border bg-background">
                        <li
                            v-for="invoice in reminder.invoices"
                            :key="invoice.id"
                        >
                            <label
                                class="flex cursor-pointer items-center gap-3 px-3 py-2.5 transition-colors hover:bg-muted has-checked:bg-primary-soft/30"
                            >
                                <input
                                    v-model="form.invoice_ids"
                                    type="checkbox"
                                    :value="invoice.id"
                                    class="size-4 shrink-0 cursor-pointer rounded border-input text-primary shadow-xs focus:ring-2 focus:ring-ring/30 focus:ring-offset-0 focus:outline-none"
                                />
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block text-sm font-medium text-foreground"
                                    >
                                        {{ invoice.invoice_number }}
                                    </span>
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        Due {{ formatDate(invoice.due_at) }}
                                        <span
                                            v-if="invoice.days_overdue !== null"
                                            class="text-destructive"
                                        >
                                            · {{ overdueLabel(invoice) }}
                                        </span>
                                    </span>
                                </span>
                                <MoneyDisplay
                                    class="text-sm font-medium text-foreground tabular-nums"
                                    :amount="invoice.remaining_balance"
                                    :currency="invoice.currency"
                                />
                            </label>
                        </li>
                    </ul>
                    <div
                        v-if="totals.length"
                        class="space-y-1 border-t border-border bg-muted px-3 py-2.5"
                    >
                        <p
                            v-for="total in totals"
                            :key="total.currency"
                            class="flex justify-between text-sm font-semibold text-foreground"
                        >
                            <span>Total due ({{ total.currency }})</span>
                            <MoneyDisplay
                                class="tabular-nums"
                                :amount="total.amount"
                                :currency="total.currency"
                            />
                        </p>
                    </div>
                </div>
                <p
                    v-if="form.errors.invoice_ids"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ form.errors.invoice_ids }}
                </p>
            </fieldset>

            <div>
                <div
                    class="divide-y divide-border rounded-lg border border-border bg-background"
                >
                    <div class="flex items-start gap-3 px-3 py-1">
                        <label
                            class="w-14 shrink-0 pt-2 text-sm text-muted-foreground"
                            >To</label
                        >
                        <EmailRecipientInput
                            v-model="form.recipients"
                            bare
                            class="flex-1"
                            :suggestions="contactSuggestions"
                        />
                    </div>
                    <div class="flex items-start gap-3 px-3 py-1">
                        <label
                            class="w-14 shrink-0 pt-2 text-sm text-muted-foreground"
                            >Cc</label
                        >
                        <EmailRecipientInput
                            v-model="form.cc"
                            bare
                            class="flex-1"
                            :suggestions="contactSuggestions"
                        />
                    </div>
                    <div class="flex items-center gap-3 px-3 py-1">
                        <label
                            for="customer-reminder-subject"
                            class="w-14 shrink-0 text-sm text-muted-foreground"
                            >Subject</label
                        >
                        <input
                            id="customer-reminder-subject"
                            v-model="form.subject"
                            type="text"
                            class="min-h-9 w-full border-0 bg-transparent p-1 text-sm font-medium text-foreground outline-none focus:ring-0 pointer-coarse:text-base"
                            @input="subjectEdited = true"
                        />
                    </div>
                </div>
                <p
                    v-if="form.errors.recipients"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ form.errors.recipients }}
                </p>
                <p
                    v-if="form.errors.subject"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ form.errors.subject }}
                </p>
            </div>

            <div>
                <TipTapEditor
                    v-model="form.body"
                    placeholder="Compose your email..."
                />
                <p
                    v-if="form.errors.body"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ form.errors.body }}
                </p>
                <p class="mt-2 text-xs text-muted-foreground">
                    The selected invoices, with links and totals, are listed
                    below your message.
                </p>
            </div>
        </DialogBody>

        <DialogFooter>
            <template #meta>{{ sendSummary }}</template>
            <Button variant="outline" @click="emit('close')">Cancel</Button>
            <Button :disabled="!canSend" @click="send">
                <Send />
                {{ form.processing ? 'Sending...' : 'Send' }}
            </Button>
        </DialogFooter>
    </Dialog>
</template>
