<script setup lang="ts">
import { inject } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Field } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select } from '@/Components/ui/select';
import {
    PAYMENT_TERM_OPTIONS,
    type PaymentTerm,
} from '@/composables/usePaymentTerms';
import type { InvoiceNumberingSeries } from '@/types';
import { InvoiceFormKey } from './formContext';

defineProps<{
    mode: 'create' | 'edit';
    type: 'invoice' | 'estimate';
    statusOptions: Record<string, string>;
    numberingSeries: InvoiceNumberingSeries[];
    /**
     * Resolved in the parent, where the issue-date/due-date watches live. The
     * conversion note is editor-only: the public view and the PDF show
     * document amounts in their own currency and have no use for it.
     */
    storedExchangeRate: {
        from: string;
        to: string | null;
        /** Already trimmed for display by the parent, so a string. */
        rate: string;
    } | null;
    storedExchangeRateHint: string;
    exchangeRateWillChange: boolean;
}>();

/**
 * Payment terms, the issue date and the due date are interdependent: the
 * parent owns the watches that keep them consistent, so the term itself comes
 * down as a model rather than being computed here.
 */
const paymentTerm = defineModel<PaymentTerm>('paymentTerm', { required: true });

const form = inject(InvoiceFormKey)!;
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Invoice Details</CardTitle>
        </CardHeader>

        <!--
            Status belongs with the document's identity, not among its fields:
            it is the one thing a reader looks for before anything else, and
            both pages already carry a header that names the document. It is
            teleported rather than lifted so the form keeps owning the field --
            only where it paints changes.

            Document Type is gone with it. On edit it was a disabled select
            that could never do anything, and on create the type is already
            settled by which button was pressed; `form.type` still carries it.

            `defer` is load-bearing. Teleport resolves its target when it
            mounts, and the header -- although it comes first in the layout's
            template -- is not in the document yet at that point, so a plain
            teleport finds nothing and renders nothing, silently: the warning
            that would say so is stripped from a production build.
        -->
        <Teleport defer to="#document-status-control">
            <Field
                label="Status"
                required
                hide-label
                class="w-full sm:w-40"
                :error="form.errors.status"
                v-slot="{ id, describedBy, invalid }"
            >
                <Select
                    :id="id"
                    v-model="form.status"
                    :aria-describedby="describedBy"
                    :aria-invalid="invalid || undefined"
                >
                    <option
                        v-for="(label, value) in statusOptions"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </Select>
            </Field>
        </Teleport>

        <CardContent class="space-y-4">
            <div
                v-if="
                    type === 'invoice' &&
                    numberingSeries.length > 0 &&
                    mode === 'create'
                "
                class="grid grid-cols-1 gap-4 md:grid-cols-3"
            >
                <Field
                    label="Numbering Series"
                    :error="form.errors.invoice_numbering_series_id"
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Select
                        :id="id"
                        v-model="form.invoice_numbering_series_id"
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    >
                        <option :value="null">Auto-select</option>
                        <option
                            v-for="series in numberingSeries"
                            :key="series.id"
                            :value="series.id"
                        >
                            {{ series.name }}
                            {{
                                series.location
                                    ? `(${series.location.name})`
                                    : '(Org-wide)'
                            }}
                        </option>
                    </Select>
                </Field>
            </div>

            <!-- Issue date, payment terms and due date are interdependent,
             so they share one row on md+ screens (single column below). -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Field
                    label="Issue Date"
                    required
                    :error="form.errors.issued_at"
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Input
                        :id="id"
                        v-model="form.issued_at"
                        type="date"
                        required
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    />
                    <!-- Sits under the issue date because that is the date the
                     rate was resolved for. -->
                    <p
                        v-if="storedExchangeRate"
                        class="text-xs text-muted-foreground"
                        :title="storedExchangeRateHint"
                    >
                        Saved rate: 1 {{ storedExchangeRate.from }} =
                        {{ storedExchangeRate.rate }}
                        {{ storedExchangeRate.to }}
                    </p>
                    <p
                        v-else-if="exchangeRateWillChange"
                        class="text-xs text-muted-foreground"
                    >
                        Exchange rate will be recalculated on save.
                    </p>
                </Field>

                <Field label="Payment Terms" required v-slot="{ id }">
                    <Select :id="id" v-model="paymentTerm" required>
                        <option
                            v-for="option in PAYMENT_TERM_OPTIONS"
                            :key="String(option.value)"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </Select>
                </Field>

                <Field
                    label="Due Date"
                    required
                    :error="form.errors.due_at"
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Input
                        :id="id"
                        v-model="form.due_at"
                        type="date"
                        required
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    />
                </Field>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- Subject spans two of the three columns: it is free text
                 describing the engagement, so it needs more room than the
                 order number beside it. -->
                <Field
                    label="Subject"
                    class="md:col-span-2"
                    :error="form.errors.subject"
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Input
                        :id="id"
                        v-model="form.subject"
                        type="text"
                        placeholder="e.g. Development work for April"
                        maxlength="255"
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    />
                </Field>

                <Field
                    label="Order Number"
                    :error="form.errors.order_number"
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Input
                        :id="id"
                        v-model="form.order_number"
                        type="text"
                        placeholder="Customer's purchase order number"
                        maxlength="255"
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    />
                </Field>
            </div>
        </CardContent>
    </Card>
</template>
