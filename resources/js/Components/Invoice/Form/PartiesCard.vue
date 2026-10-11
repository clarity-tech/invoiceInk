<script setup lang="ts">
import { computed, inject } from 'vue';
import AddressLines from '../AddressLines.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Checkbox } from '@/Components/ui/checkbox';
import { Field } from '@/Components/ui/field';
import { Label } from '@/Components/ui/label';
import { Select } from '@/Components/ui/select';
import type { Customer, Location } from '@/types';
import { InvoiceFormKey } from './formContext';

/**
 * Who is issuing the document and who it is for.
 *
 * The organization location used to own a whole card of its own holding a
 * single select, directly above the card that picked the customer -- two boxes
 * to answer "between whom".
 */
const props = defineProps<{
    organizationLocations: Location[];
    customers: Customer[];
    /** The chosen customer's locations; resolved in the parent. */
    customerLocations: Location[];
    selectedCustomer: Customer | null | undefined;
    /** GST organizations only. */
    showPlaceOfSupply: boolean;
    indianStates?: Record<string, string>;
    taxLabels: Record<string, string>;
}>();

const selectedCustomerId = defineModel<number | null>('selectedCustomerId', {
    required: true,
});
const sameAsBilling = defineModel<boolean>('sameAsBilling', { required: true });

const form = inject(InvoiceFormKey)!;

const selectedBillingLocation = computed(() =>
    props.customerLocations.find((l) => l.id === form.customer_location_id),
);

const selectedShippingLocation = computed(() =>
    props.customerLocations.find(
        (l) => l.id === form.customer_shipping_location_id,
    ),
);

/**
 * GstBreakdownService falls back to the first two digits of the customer's
 * GSTIN when the invoice carries no place of supply. With neither, a domestic
 * supply gets no CGST/SGST/IGST breakdown at all, so the document shows a tax
 * total with nothing explaining it. Worth saying on the form rather than
 * discovering on the PDF.
 */
const placeOfSupplyUnresolved = computed(() => {
    if (!props.showPlaceOfSupply || form.place_of_supply) {
        return false;
    }

    const loc = selectedBillingLocation.value;

    // Exports are always inter-state, so they need no place of supply.
    if (!loc || loc.country !== 'IN') {
        return false;
    }

    return !loc.tax_id || loc.tax_id.length < 2;
});

/** "… - City (Primary) (Archived)", built once for both address selects. */
function locationLabel(loc: Location): string {
    const primary =
        props.selectedCustomer?.primary_location_id === loc.id
            ? ' (Primary)'
            : '';
    const archived = loc.deleted_at ? ' (Archived)' : '';

    return `${loc.name} - ${loc.city}${primary}${archived}`;
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Parties</CardTitle>
        </CardHeader>

        <CardContent class="space-y-5">
            <!--
                One grid, two rows: who the document is between, then where it
                is going. Place of Supply is decided by the billing address's
                state, so it leads the row that picks that address rather than
                trailing the row that picks the parties.

                It used to hold a block of its own below, which cost 102px of
                a 473px card to carry one select; sharing a row costs it only
                the height of its own hint.
            -->
            <div
                class="grid gap-5"
                :class="showPlaceOfSupply ? 'md:grid-cols-3' : 'md:grid-cols-2'"
            >
                <Field
                    v-if="organizationLocations.length > 0"
                    label="Organization Location"
                    required
                    :error="form.errors.organization_location_id"
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Select
                        :id="id"
                        v-model="form.organization_location_id"
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    >
                        <option :value="null">Select location</option>
                        <option
                            v-for="loc in organizationLocations"
                            :key="loc.id"
                            :value="loc.id"
                        >
                            {{ loc.name }} - {{ loc.city }}
                        </option>
                    </Select>
                </Field>
                <div v-else class="space-y-1.5">
                    <Label>Organization Location</Label>
                    <div
                        class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                    >
                        No locations found.
                        <a
                            href="/organizations"
                            class="font-medium underline"
                            target="_blank"
                            >Add a location</a
                        >
                    </div>
                </div>

                <Field
                    label="Customer"
                    required
                    :error="form.errors.customer_id"
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Select
                        :id="id"
                        v-model="selectedCustomerId"
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    >
                        <option :value="null">Select customer</option>
                        <option
                            v-for="customer in customers"
                            :key="customer.id"
                            :value="customer.id"
                        >
                            {{ customer.name
                            }}{{ customer.deleted_at ? ' (Archived)' : '' }}
                        </option>
                    </Select>
                </Field>

                <!--
                    `col-start-1` is what starts the second row: without it
                    this lands in the empty third column beside Customer, and
                    the addresses it belongs with get pushed down a row on
                    their own.
                -->
                <Field
                    v-if="showPlaceOfSupply"
                    class="md:col-start-1"
                    label="Place of Supply"
                    :error="form.errors.place_of_supply"
                    :warning="
                        placeOfSupplyUnresolved
                            ? `This customer has no GSTIN, so without a place of supply the document shows a tax total with no CGST/SGST/IGST split.`
                            : undefined
                    "
                    hint="Decides the CGST/SGST or IGST split. Taken from the customer's GSTIN when left blank."
                    v-slot="{ id, describedBy, invalid }"
                >
                    <Select
                        :id="id"
                        v-model="form.place_of_supply"
                        :aria-describedby="describedBy"
                        :aria-invalid="invalid || undefined"
                    >
                        <option value="">Select a state…</option>
                        <option
                            v-for="(label, value) in indianStates"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </Select>
                </Field>
                <!--
                    The addresses share the row above rather than
                    opening one of their own. Two grids meant two
                    sets of column edges: "Shipping Address" began
                    at the half-way point of a two-column grid,
                    which lined up with nothing in the three-column
                    grid above it.
                -->
                <template
                    v-if="selectedCustomerId && customerLocations.length > 0"
                >
                    <!--
                    No box. These were two bordered, filled panels with a
                    heading apiece, holding a select and five lines of address
                    -- 180px of a 473px card. The address is here to confirm
                    the right location was picked, not to be read off, so it is
                    one muted line under a labelled field like every other
                    field in the form.
                -->
                    <Field label="Billing Address" class="min-w-0">
                        <Select
                            v-model="form.customer_location_id"
                            aria-label="Billing Address"
                        >
                            <option :value="null">Select location</option>
                            <option
                                v-for="loc in customerLocations"
                                :key="loc.id"
                                :value="loc.id"
                            >
                                {{ locationLabel(loc) }}
                            </option>
                        </Select>
                        <p
                            v-if="form.errors.customer_location_id"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.customer_location_id }}
                        </p>
                        <AddressLines
                            v-if="selectedBillingLocation"
                            :location="selectedBillingLocation"
                            :tax-labels="taxLabels"
                            compact
                        />
                    </Field>

                    <div class="min-w-0 space-y-1.5">
                        <!--
                        The toggle sits with the label rather than pushed to
                        the far side of the column: with "same as billing" on
                        there is no select under it, and a checkbox stranded
                        half a card away from its own heading reads as
                        belonging to nothing.
                    -->
                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-1"
                        >
                            <Label>Shipping Address</Label>
                            <label
                                class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                            >
                                <Checkbox v-model="sameAsBilling" />
                                Same as billing address
                            </label>
                        </div>

                        <p
                            v-if="sameAsBilling"
                            class="text-xs text-muted-foreground"
                        >
                            Shipping address is the same as the billing address.
                        </p>

                        <template v-else>
                            <Select
                                v-model="form.customer_shipping_location_id"
                                aria-label="Shipping Address"
                            >
                                <option :value="null">Select location</option>
                                <option
                                    v-for="loc in customerLocations"
                                    :key="loc.id"
                                    :value="loc.id"
                                >
                                    {{ locationLabel(loc) }}
                                </option>
                            </Select>
                            <p
                                v-if="form.errors.customer_shipping_location_id"
                                class="text-xs text-destructive"
                            >
                                {{ form.errors.customer_shipping_location_id }}
                            </p>
                            <AddressLines
                                v-if="selectedShippingLocation"
                                :location="selectedShippingLocation"
                                :tax-labels="taxLabels"
                                compact
                            />
                        </template>
                    </div>
                </template>
            </div>

            <div
                v-if="customers.length === 0"
                class="rounded-md border border-brand-200 bg-brand-50 p-3 text-sm text-brand-700"
            >
                No customers found.
                <a
                    href="/customers"
                    class="font-medium underline"
                    target="_blank"
                    >Create your first customer</a
                >
            </div>

            <!--
                The billing and shipping selects are hidden when the customer
                has no location, and the `customer_location_id` error would go
                with them. Without this notice, saving just failed in silence.
            -->
            <div
                v-if="selectedCustomerId && customerLocations.length === 0"
                class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
            >
                This customer has no billing address yet, so the invoice cannot
                be saved.
                <a
                    :href="`/customers/${selectedCustomerId}`"
                    class="font-medium underline"
                    target="_blank"
                    >Add a location</a
                >, then reload this page.
            </div>
        </CardContent>
    </Card>
</template>
