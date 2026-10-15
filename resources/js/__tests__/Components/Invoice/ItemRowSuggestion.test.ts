import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { reactive } from 'vue';
import { makeTaxOption } from '../../helpers';

const mockPost = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    useHttp: (initial: Record<string, unknown>) =>
        reactive({ ...initial, errors: {}, processing: false, post: mockPost }),
}));

import ItemRow from '@/Components/Invoice/ItemRow.vue';

function mountRow(props = {}) {
    return mount(ItemRow, {
        props: {
            item: {
                description: 'Monthly IT support retainer',
                sac_code: null,
                quantity: 1,
                unit_price: 500000,
                tax_rate: 0,
            },
            index: 0,
            currency: 'INR',
            taxOptions: [
                makeTaxOption({ key: 'gst-0', name: 'GST 0%', rate: 0 }),
                makeTaxOption({ key: 'gst-18', name: 'GST 18%', rate: 1800 }),
            ],
            canRemove: false,
            errors: {},
            aiSuggest: true,
            ...props,
        },
    });
}

function suggestButton(wrapper: ReturnType<typeof mount>) {
    return wrapper
        .findAll('button')
        .find((b) => b.text().includes('Suggest code & tax'));
}

describe('ItemRow suggestions', () => {
    beforeEach(() => {
        mockPost.mockReset();
    });

    it('offers a suggestion once AI is on and the line says what it is', () => {
        expect(suggestButton(mountRow())).toBeTruthy();
        expect(suggestButton(mountRow({ aiSuggest: false }))).toBeUndefined();
        expect(
            suggestButton(
                mountRow({
                    item: {
                        description: '',
                        sac_code: null,
                        quantity: 1,
                        unit_price: 0,
                        tax_rate: 0,
                    },
                }),
            ),
        ).toBeUndefined();
    });

    it('fills in the code and rate it suggests, and says why', async () => {
        mockPost.mockImplementation((_url, options) => {
            options.onSuccess({
                sac_code: '998314',
                tax_rate: 1800,
                reason: 'An IT support service.',
            });

            return Promise.resolve();
        });
        const wrapper = mountRow();

        await suggestButton(wrapper)!.trigger('click');

        expect(mockPost).toHaveBeenCalledWith(
            '/invoices/item-suggestion',
            expect.any(Object),
        );
        expect(wrapper.emitted('update')![0][0]).toMatchObject({
            sac_code: '998314',
            tax_rate: 1800,
        });
        expect(wrapper.text()).toContain(
            'Suggested by AI: An IT support service.',
        );
    });
});
