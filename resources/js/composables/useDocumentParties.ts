import { computed, ref, toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import type { DocumentFields } from '@/Components/Invoice/Form/formContext';
import { mergeRecipients } from '@/composables/useRecipients';
import type { Contact, Customer, Location } from '@/types';

export interface DocumentPartiesSources {
    customers: MaybeRefOrGetter<Customer[]>;
    organizationLocations: MaybeRefOrGetter<Location[]>;
    /** Only sent for Indian (GST) organizations; empty or absent otherwise. */
    indianStates: MaybeRefOrGetter<Record<string, string> | undefined>;
}

/**
 * Who a document is between, and what follows from picking them.
 *
 * Choosing a customer fills in half the form -- billing and shipping address,
 * place of supply, email recipients -- and the GST split depends on the pair
 * of locations chosen. That logic used to live inline in InvoiceForm; it is
 * here so any form editing DocumentFields (a recurring profile, for one)
 * behaves the same way when a customer is picked.
 *
 * Call it once per form, from the component that owns the form: the watchers
 * write to it, and a second call would run them twice.
 */
export function useDocumentParties(
    form: DocumentFields,
    sources: DocumentPartiesSources,
) {
    // Separate from form.customer_id so the select can drive the watcher
    // below; the watcher then writes the id through to the form.
    const selectedCustomerId = ref<number | null>(form.customer_id);

    const selectedCustomer = computed(
        () =>
            toValue(sources.customers).find(
                (c) => c.id === selectedCustomerId.value,
            ) ?? null,
    );

    const customerLocations = computed(
        () => selectedCustomer.value?.locations ?? [],
    );

    // The selected customer's contacts, shown as check/uncheck rows in the
    // recipients editor.
    const customerContacts = computed<Contact[]>(
        () => selectedCustomer.value?.emails ?? [],
    );

    // Place of supply applies to Indian (GST) organizations, but not to
    // overseas (export) customers.
    const showPlaceOfSupply = computed(
        () =>
            Object.keys(toValue(sources.indianStates) ?? {}).length > 0 &&
            selectedCustomer.value?.gst_treatment !== 'overseas',
    );

    // TDS/TCS (India source taxes): TDS is deducted from the amount payable,
    // TCS is added on top. Only offered to Indian organizations.
    const showSourceTaxes = computed(() => showPlaceOfSupply.value);

    const selectedOrgLocation = computed(
        () =>
            toValue(sources.organizationLocations).find(
                (l) => l.id === form.organization_location_id,
            ) ?? null,
    );

    // The supplier's GST state code — first two digits of the org GSTIN, only
    // for Indian locations. Drives the intra- vs inter-state CGST/SGST/IGST
    // split.
    const supplierStateCode = computed<string | null>(() => {
        const loc = selectedOrgLocation.value;

        if (
            !loc ||
            loc.country !== 'IN' ||
            !loc.tax_id ||
            loc.tax_id.length < 2
        ) {
            return null;
        }

        return loc.tax_id.slice(0, 2);
    });

    // A customer billed outside India is an export → inter-state → IGST.
    const isForeignCustomer = computed(() => {
        const loc = customerLocations.value.find(
            (l) => l.id === form.customer_location_id,
        );

        return loc != null && loc.country !== 'IN';
    });

    // Customer change: auto-select primary location. Not immediate, which is
    // what keeps an edited document's saved addresses, place of supply and
    // recipients: only a customer the user picks overwrites them.
    watch(selectedCustomerId, (newId) => {
        form.customer_id = newId;

        if (!newId) {
            form.customer_location_id = null;
            form.customer_shipping_location_id = null;
            form.email_recipients = [];

            return;
        }

        const customer = toValue(sources.customers).find((c) => c.id === newId);

        if (customer) {
            const primaryId = customer.primary_location_id;
            const locs = customer.locations ?? [];
            const fallback = primaryId ?? locs[0]?.id ?? null;
            form.customer_location_id = fallback;
            form.customer_shipping_location_id = fallback;
            // Default the place of supply from the chosen customer. Editable
            // here; it never writes back to the customer.
            form.place_of_supply = customer.place_of_supply ?? '';
            // Switching customers resets recipients to the new customer's
            // contacts (all selected); any extras from the previous customer
            // are dropped.
            form.email_recipients = mergeRecipients(customer.emails ?? [], []);
        }
    });

    // "Same as billing" toggle for the shipping address. Defaults to enabled
    // when shipping already matches billing (always the case in create mode
    // after the customer auto-fill, and in edit mode when no separate shipping
    // was chosen).
    const sameAsBilling = ref(
        form.customer_shipping_location_id === form.customer_location_id,
    );

    // While enabled, mirror shipping to billing so any billing change (or
    // customer switch) is reflected in the shipping address too.
    watch(sameAsBilling, (enabled) => {
        if (enabled) {
            form.customer_shipping_location_id = form.customer_location_id;
        }
    });

    watch(
        () => form.customer_location_id,
        (billingId) => {
            if (sameAsBilling.value) {
                form.customer_shipping_location_id = billingId;
            }
        },
    );

    return {
        selectedCustomerId,
        selectedCustomer,
        customerLocations,
        customerContacts,
        showPlaceOfSupply,
        showSourceTaxes,
        supplierStateCode,
        isForeignCustomer,
        sameAsBilling,
    };
}
