<script setup lang="ts">
import { computed, inject, ref } from 'vue';
import { ChevronDown } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Field } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { InvoiceFormKey } from './formContext';

const form = inject(InvoiceFormKey)!;

/**
 * The fields start collapsed: they apply to a minority of documents but used
 * to cost a card of height on every Indian invoice. A document that already
 * carries a rate opens them, and so does a rejected one -- a field nobody can
 * see cannot show why the save failed (.ai/rules/frontend.md).
 */
const sourceTaxesOpen = ref(Boolean(form.tds_rate || form.tcs_rate));

const rejected = computed(() =>
    Boolean(
        form.errors.tds_rate ||
        form.errors.tds_section ||
        form.errors.tcs_rate ||
        form.errors.tcs_section,
    ),
);

const showFields = computed(() => sourceTaxesOpen.value || rejected.value);

/** Rates are stored in basis points; 1000 is 10%. */
function rateAsPercent(rate: number | null): string {
    return rate == null ? '' : String(rate / 100);
}

function onRateInput(field: 'tds_rate' | 'tcs_rate', value: string) {
    const trimmed = value.trim();
    form[field] = trimmed === '' ? null : Math.round(Number(trimmed) * 100);
}

/** What the collapsed card says in place of four empty inputs. */
const summary = computed(() => {
    const parts = [
        form.tds_rate ? `TDS ${rateAsPercent(form.tds_rate)}%` : null,
        form.tcs_rate ? `TCS ${rateAsPercent(form.tcs_rate)}%` : null,
    ].filter(Boolean);

    return parts.length > 0
        ? parts.join(' · ')
        : 'Tax deducted or collected at source. TDS is subtracted from the total; TCS is added. Leave blank if not applicable.';
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>TDS / TCS</CardTitle>
            <CardAction>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    :aria-expanded="showFields"
                    @click="sourceTaxesOpen = !sourceTaxesOpen"
                >
                    {{ showFields ? 'Hide' : 'Add' }}
                    <ChevronDown
                        :class="showFields && 'rotate-180'"
                        class="transition-transform"
                    />
                </Button>
            </CardAction>
        </CardHeader>

        <CardContent>
            <p v-if="!showFields" class="text-sm text-muted-foreground">
                {{ summary }}
            </p>

            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    class="space-y-3 rounded-lg border border-border bg-muted/40 p-4"
                >
                    <h3 class="text-sm font-medium text-foreground">
                        TDS (deducted)
                    </h3>
                    <Field
                        label="Rate (%)"
                        :error="form.errors.tds_rate"
                        v-slot="{ id, describedBy, invalid }"
                    >
                        <Input
                            :id="id"
                            :model-value="rateAsPercent(form.tds_rate)"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="e.g. 10"
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid || undefined"
                            @input="
                                onRateInput(
                                    'tds_rate',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                    </Field>
                    <Field
                        label="Section"
                        :error="form.errors.tds_section"
                        v-slot="{ id, describedBy, invalid }"
                    >
                        <Input
                            :id="id"
                            v-model="form.tds_section"
                            type="text"
                            maxlength="20"
                            placeholder="e.g. 194J"
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid || undefined"
                        />
                    </Field>
                </div>

                <div
                    class="space-y-3 rounded-lg border border-border bg-muted/40 p-4"
                >
                    <h3 class="text-sm font-medium text-foreground">
                        TCS (collected)
                    </h3>
                    <Field
                        label="Rate (%)"
                        :error="form.errors.tcs_rate"
                        v-slot="{ id, describedBy, invalid }"
                    >
                        <Input
                            :id="id"
                            :model-value="rateAsPercent(form.tcs_rate)"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="e.g. 0.1"
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid || undefined"
                            @input="
                                onRateInput(
                                    'tcs_rate',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                    </Field>
                    <Field
                        label="Section"
                        :error="form.errors.tcs_section"
                        v-slot="{ id, describedBy, invalid }"
                    >
                        <Input
                            :id="id"
                            v-model="form.tcs_section"
                            type="text"
                            maxlength="20"
                            placeholder="e.g. 206C(1H)"
                            :aria-describedby="describedBy"
                            :aria-invalid="invalid || undefined"
                        />
                    </Field>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
