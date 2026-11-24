import { afterEach, describe, expect, it } from 'vitest';
import { effectScope, nextTick, reactive, ref } from 'vue';
import type { EffectScope } from 'vue';
import type { DocumentFields } from '@/Components/Invoice/Form/formContext';
import { useDocumentParties } from '@/composables/useDocumentParties';
import type { Customer, Location } from '@/types';
import { makeContact, makeCustomer, makeLocation } from '../helpers';

const delhiOffice = makeLocation({
    id: 20,
    name: 'Client Office',
    city: 'Delhi',
    tax_id: '07AAACA1234A1Z5',
    locatable_type: 'App\\Models\\Customer',
});
const bangaloreBranch = makeLocation({
    id: 21,
    name: 'Branch Office',
    city: 'Bangalore',
    locatable_type: 'App\\Models\\Customer',
});
const dubaiOffice = makeLocation({
    id: 30,
    name: 'Dubai Office',
    city: 'Dubai',
    country: 'AE',
    locatable_type: 'App\\Models\\Customer',
});

/** Primary location is the second one, so "first" and "primary" differ. */
const acme = makeCustomer({
    id: 1,
    name: 'ACME Corp',
    place_of_supply: '07',
    primary_location_id: 21,
    locations: [delhiOffice, bangaloreBranch],
    emails: [
        makeContact({ email: 'billing@acme.test' }),
        makeContact({ email: 'ceo@acme.test' }),
    ],
});

/** No primary location and no place of supply. */
const globex = makeCustomer({
    id: 2,
    name: 'Globex Inc',
    place_of_supply: null,
    primary_location_id: null,
    locations: [bangaloreBranch, delhiOffice],
    emails: [makeContact({ email: 'ap@globex.test' })],
});

const empty = makeCustomer({
    id: 3,
    name: 'Initech',
    primary_location_id: null,
    locations: [],
    emails: [],
});

const sandline = makeCustomer({
    id: 4,
    name: 'Sandline LLC',
    currency: 'AED',
    gst_treatment: 'overseas',
    primary_location_id: 30,
    locations: [dubaiOffice],
    emails: [],
});

const mumbaiHq = makeLocation({ id: 10, tax_id: '27AAACC1234C1Z1' });
const londonHq = makeLocation({ id: 11, country: 'GB', tax_id: 'GB123' });

function makeForm(overrides: Partial<DocumentFields> = {}): DocumentFields {
    return reactive<DocumentFields>({
        customer_id: null,
        organization_location_id: 10,
        customer_location_id: null,
        customer_shipping_location_id: null,
        place_of_supply: '',
        email_recipients: [],
        tds_rate: null,
        tds_section: '',
        tcs_rate: null,
        tcs_section: '',
        items: [],
        ...overrides,
    });
}

let scope: EffectScope | null = null;

afterEach(() => {
    scope?.stop();
    scope = null;
});

/** Runs the composable inside an effect scope, as a component's setup would. */
function setup(
    form: DocumentFields,
    {
        customers = [acme, globex, empty, sandline] as Customer[],
        organizationLocations = [mumbaiHq, londonHq] as Location[],
        indianStates = { '07': 'Delhi', '27': 'Maharashtra' } as
            Record<string, string> | undefined,
    } = {},
) {
    scope = effectScope();

    return scope.run(() =>
        useDocumentParties(form, {
            customers: () => customers,
            organizationLocations: () => organizationLocations,
            indianStates: () => indianStates,
        }),
    )!;
}

describe('useDocumentParties', () => {
    describe('picking a customer', () => {
        it('writes the id through to the form', async () => {
            const form = makeForm();
            const parties = setup(form);

            parties.selectedCustomerId.value = 1;
            await nextTick();

            expect(form.customer_id).toBe(1);
            expect(parties.selectedCustomer.value?.name).toBe('ACME Corp');
            expect(parties.customerLocations.value).toEqual([
                delhiOffice,
                bangaloreBranch,
            ]);
        });

        it('bills and ships to the primary location', async () => {
            const form = makeForm();
            const parties = setup(form);

            parties.selectedCustomerId.value = 1;
            await nextTick();

            expect(form.customer_location_id).toBe(21);
            expect(form.customer_shipping_location_id).toBe(21);
        });

        it('falls back to the first location when none is primary', async () => {
            const form = makeForm();
            const parties = setup(form);

            parties.selectedCustomerId.value = 2;
            await nextTick();

            expect(form.customer_location_id).toBe(21);
            expect(form.customer_shipping_location_id).toBe(21);
        });

        it('leaves the addresses empty for a customer with no location', async () => {
            const form = makeForm({
                customer_id: 1,
                customer_location_id: 21,
                customer_shipping_location_id: 21,
            });
            const parties = setup(form);

            parties.selectedCustomerId.value = 3;
            await nextTick();

            expect(form.customer_location_id).toBeNull();
            expect(form.customer_shipping_location_id).toBeNull();
        });

        it("defaults the place of supply to the customer's", async () => {
            const form = makeForm({ place_of_supply: '27' });
            const parties = setup(form);

            parties.selectedCustomerId.value = 1;
            await nextTick();

            expect(form.place_of_supply).toBe('07');
        });

        it('clears the place of supply for a customer without one', async () => {
            const form = makeForm({ place_of_supply: '27' });
            const parties = setup(form);

            parties.selectedCustomerId.value = 2;
            await nextTick();

            expect(form.place_of_supply).toBe('');
        });

        it("resets the recipients to the new customer's contacts, all selected", async () => {
            const form = makeForm({
                customer_id: 1,
                email_recipients: [
                    { email: 'billing@acme.test', checked: false },
                    { email: 'extra@elsewhere.test', checked: true },
                ],
            });
            const parties = setup(form);

            parties.selectedCustomerId.value = 2;
            await nextTick();

            expect(form.email_recipients).toEqual([
                { email: 'ap@globex.test', checked: true },
            ]);
            expect(parties.customerContacts.value).toEqual(globex.emails);
        });

        it('clears the addresses and recipients when the customer is unset', async () => {
            const form = makeForm({
                customer_id: 1,
                customer_location_id: 21,
                customer_shipping_location_id: 20,
                place_of_supply: '07',
                email_recipients: [
                    { email: 'billing@acme.test', checked: true },
                ],
            });
            const parties = setup(form);

            parties.selectedCustomerId.value = null;
            await nextTick();

            expect(form.customer_id).toBeNull();
            expect(form.customer_location_id).toBeNull();
            expect(form.customer_shipping_location_id).toBeNull();
            expect(form.email_recipients).toEqual([]);
            // Left as it was: only picking a customer sets it.
            expect(form.place_of_supply).toBe('07');
        });
    });

    describe('an edited document', () => {
        // The watcher is not immediate. Opening a saved document must keep
        // what was saved on it, even where that differs from what picking the
        // same customer afresh would fill in.
        it('keeps its saved addresses, place of supply and recipients', async () => {
            const saved = [
                { email: 'billing@acme.test', checked: false },
                { email: 'extra@elsewhere.test', checked: true },
            ];
            const form = makeForm({
                customer_id: 1,
                customer_location_id: 20,
                customer_shipping_location_id: 21,
                place_of_supply: '27',
                email_recipients: saved,
            });
            const parties = setup(form);
            await nextTick();

            expect(parties.selectedCustomerId.value).toBe(1);
            expect(form.customer_location_id).toBe(20);
            expect(form.customer_shipping_location_id).toBe(21);
            expect(form.place_of_supply).toBe('27');
            expect(form.email_recipients).toEqual(saved);
        });

        it('starts with a separate shipping address shown when it differs', () => {
            const form = makeForm({
                customer_id: 1,
                customer_location_id: 20,
                customer_shipping_location_id: 21,
            });

            expect(setup(form).sameAsBilling.value).toBe(false);
        });
    });

    describe('same as billing', () => {
        it('is on when shipping matches billing', () => {
            const form = makeForm({
                customer_id: 1,
                customer_location_id: 21,
                customer_shipping_location_id: 21,
            });

            expect(setup(form).sameAsBilling.value).toBe(true);
        });

        it('copies billing to shipping when switched on', async () => {
            const form = makeForm({
                customer_id: 1,
                customer_location_id: 20,
                customer_shipping_location_id: 21,
            });
            const parties = setup(form);

            parties.sameAsBilling.value = true;
            await nextTick();

            expect(form.customer_shipping_location_id).toBe(20);
        });

        it('follows a billing change while on, and stops once off', async () => {
            const form = makeForm({
                customer_id: 1,
                customer_location_id: 21,
                customer_shipping_location_id: 21,
            });
            const parties = setup(form);

            form.customer_location_id = 20;
            await nextTick();
            expect(form.customer_shipping_location_id).toBe(20);

            parties.sameAsBilling.value = false;
            await nextTick();
            form.customer_location_id = 21;
            await nextTick();
            expect(form.customer_shipping_location_id).toBe(20);
        });
    });

    describe('GST inputs', () => {
        it('offers place of supply and source taxes to a GST organization', () => {
            const parties = setup(makeForm({ customer_id: 1 }));

            expect(parties.showPlaceOfSupply.value).toBe(true);
            expect(parties.showSourceTaxes.value).toBe(true);
        });

        // A non-Indian organization is sent an empty list of states.
        it('offers neither without Indian states', () => {
            const parties = setup(makeForm({ customer_id: 1 }), {
                indianStates: {},
            });

            expect(parties.showPlaceOfSupply.value).toBe(false);
            expect(parties.showSourceTaxes.value).toBe(false);
        });

        it('offers neither for an overseas customer', () => {
            const parties = setup(makeForm({ customer_id: 4 }));

            expect(parties.showPlaceOfSupply.value).toBe(false);
            expect(parties.showSourceTaxes.value).toBe(false);
        });

        it("takes the supplier state from the organization location's GSTIN", () => {
            const form = makeForm({ organization_location_id: 10 });
            const parties = setup(form);

            expect(parties.supplierStateCode.value).toBe('27');

            form.organization_location_id = 11;
            expect(parties.supplierStateCode.value).toBeNull();

            form.organization_location_id = null;
            expect(parties.supplierStateCode.value).toBeNull();
        });

        it('treats a billing address outside India as an export', async () => {
            const form = makeForm();
            const parties = setup(form);

            expect(parties.isForeignCustomer.value).toBe(false);

            parties.selectedCustomerId.value = 4;
            await nextTick();
            expect(parties.isForeignCustomer.value).toBe(true);

            parties.selectedCustomerId.value = 1;
            await nextTick();
            expect(parties.isForeignCustomer.value).toBe(false);
        });
    });

    it('reads the customer list live, so a reloaded prop is seen', async () => {
        const customers = ref<Customer[]>([acme]);
        const form = makeForm();
        scope = effectScope();
        const parties = scope.run(() =>
            useDocumentParties(form, {
                customers,
                organizationLocations: [mumbaiHq],
                indianStates: {},
            }),
        )!;

        parties.selectedCustomerId.value = 2;
        await nextTick();
        expect(parties.selectedCustomer.value).toBeNull();

        customers.value = [acme, globex];
        expect(parties.selectedCustomer.value?.name).toBe('Globex Inc');
    });
});
