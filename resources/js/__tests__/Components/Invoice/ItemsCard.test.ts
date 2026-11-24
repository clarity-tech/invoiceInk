import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';
import type { LineItem } from '@/composables/useInvoiceCalculator';
import { makeTaxOption } from '../../helpers';

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
    usePage: () => ({ props: { auth: { currentTeam: { ai: false } } } }),
}));

import ItemsCard from '@/Components/Invoice/Form/ItemsCard.vue';
import {
    DocumentFormKey,
    type DocumentFields,
} from '@/Components/Invoice/Form/formContext';

const consulting = (): LineItem => ({
    description: 'Consulting',
    sac_code: '9983',
    quantity: 2,
    unit_price: 50000,
    tax_rate: 1800,
});

const blank = (): LineItem => ({
    description: '',
    sac_code: null,
    quantity: 1,
    unit_price: 0,
    tax_rate: 0,
});

/**
 * Only DocumentFields and errors: no status, dates or numbering series, the
 * way a form other than InvoiceForm would provide it.
 */
function makeForm(overrides: Partial<DocumentFields> = {}) {
    return reactive({
        customer_id: 1,
        organization_location_id: 10,
        customer_location_id: 20,
        customer_shipping_location_id: 20,
        place_of_supply: '27',
        email_recipients: [],
        tds_rate: null as number | null,
        tds_section: '',
        tcs_rate: null as number | null,
        tcs_section: '',
        items: [] as LineItem[],
        errors: {} as Record<string, string>,
        ...overrides,
    });
}

let wrapper: ReturnType<typeof mountCard> | null = null;

afterEach(() => {
    wrapper?.unmount();
    wrapper = null;
});

/** Mounts the card with v-model:items wired the way a parent would. */
function mountCard(
    items: LineItem[],
    form = makeForm(),
    props: Partial<{
        supplierStateCode: string | null;
        isForeignCustomer: boolean;
    }> = {},
) {
    const mounted = mount(ItemsCard, {
        attachTo: document.body,
        props: {
            items,
            'onUpdate:items': (next: LineItem[]) =>
                mounted.setProps({ items: next }),
            currency: 'INR' as const,
            taxOptions: [makeTaxOption()],
            supplierStateCode: '27',
            isForeignCustomer: false,
            ...props,
        },
        global: {
            provide: { [DocumentFormKey as symbol]: form },
            stubs: {
                ConfirmationModal: {
                    template:
                        '<div v-if="show" data-testid="confirm-modal"><button data-testid="confirm-btn" @click="$emit(\'confirm\')">Confirm</button></div>',
                    props: ['show'],
                    emits: ['confirm', 'cancel'],
                },
            },
        },
    });

    wrapper = mounted;

    return mounted;
}

const lastEmitted = (w: NonNullable<typeof wrapper>) =>
    w.emitted<[LineItem[]]>('update:items')!.at(-1)![0];

describe('Invoice/Form/ItemsCard', () => {
    it('totals the lines from a form that provides only DocumentFields', () => {
        const form = makeForm({
            tds_rate: 1000,
            tds_section: '194J',
            tcs_rate: 100,
        });
        const w = mountCard([consulting()], form);
        const text = w.text();

        // 2 × ₹500 = ₹1,000; 18% GST split CGST/SGST for an intra-state
        // supply (supplier and place of supply both 27).
        expect(text).toContain('Subtotal:');
        expect(text).toContain('1,000.00');
        expect(text).toContain('CGST @ (9%):');
        expect(text).toContain('SGST @ (9%):');
        // TDS 10% of the subtotal; TCS 1% of subtotal plus tax.
        expect(text).toContain('TDS @ 10% (194J):');
        expect(text).toContain('100.00');
        expect(text).toContain('TCS @ 1%:');
        expect(text).toContain('11.80');
        // 1,180 + 11.80 − 100 = 1,091.80
        expect(text).toContain('Total (₹)');
        expect(text).toContain('1,091.80');
    });

    it('shows one tax line when the supply has no GST split', () => {
        const w = mountCard([consulting()], makeForm(), {
            supplierStateCode: null,
        });

        expect(w.text()).toContain('Tax:');
        expect(w.text()).not.toContain('CGST');
    });

    it('adds a blank line through the model and moves focus into it', async () => {
        const items = [consulting()];
        const w = mountCard(items);

        const addButtons = w
            .findAll('button')
            .filter((b) => b.text() === 'Add Item');
        await addButtons[0].trigger('click');
        await w.vm.$nextTick();

        expect(lastEmitted(w)).toEqual([consulting(), blank()]);
        // A new array is handed back; the parent's is left alone.
        expect(items).toHaveLength(1);
        expect(document.activeElement?.id).toBe('item-1-description');
    });

    it('hands an edited line back as a new array', async () => {
        const items = [consulting()];
        const w = mountCard(items);

        await w.find('#item-0-description').setValue('Design');

        expect(lastEmitted(w)[0].description).toBe('Design');
        expect(items[0].description).toBe('Consulting');
    });

    it('asks before removing a filled line', async () => {
        const w = mountCard([consulting(), blank()]);

        await w.find('button[aria-label="Remove item 1"]').trigger('click');

        expect(w.emitted('update:items')).toBeUndefined();
        expect(w.find('[data-testid="confirm-modal"]').exists()).toBe(true);

        await w.find('[data-testid="confirm-btn"]').trigger('click');

        expect(lastEmitted(w)).toEqual([blank()]);
        expect(w.find('[data-testid="confirm-modal"]').exists()).toBe(false);
    });

    it('passes item errors from the form down to the row', () => {
        const form = makeForm({
            errors: { 'items.0.description': 'A description is required.' },
        } as Partial<DocumentFields>);
        const w = mountCard([blank()], form);

        expect(w.text()).toContain('A description is required.');
    });
});
