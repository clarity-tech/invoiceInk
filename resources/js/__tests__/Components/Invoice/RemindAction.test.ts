import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { makeInvoice } from '../../helpers';

const { emailModalRequested } = vi.hoisted(() => ({
    emailModalRequested: vi.fn(),
}));

// The composer module itself, so the test sees when it is fetched.
vi.mock('@/Components/Invoice/EmailModal.vue', () => {
    emailModalRequested();

    return {
        // Marks this as an ES module, so the async component takes `default`.
        __esModule: true,
        default: {
            name: 'EmailModal',
            props: [
                'show',
                'invoice',
                'customers',
                'invoiceAttachments',
                'templateType',
            ],
            template:
                '<div data-testid="email-modal" :data-show="String(show)" />',
        },
    };
});

import RemindAction from '@/Components/Invoice/RemindAction.vue';

describe('Invoice/RemindAction', () => {
    it('fetches the composer on first use and opens it', async () => {
        const wrapper = mount(RemindAction, {
            props: {
                invoice: makeInvoice(),
                attachments: [],
                templateType: 'invoice_reminder',
                customerReminder: null,
            },
            global: { stubs: { teleport: true } },
        });

        // The page renders without the composer's code.
        expect(emailModalRequested).not.toHaveBeenCalled();
        expect(wrapper.find('[data-testid="email-modal"]').exists()).toBe(
            false,
        );

        await wrapper.get('button').trigger('click');
        await vi.dynamicImportSettled();
        await flushPromises();

        expect(emailModalRequested).toHaveBeenCalledTimes(1);
        expect(
            wrapper.get('[data-testid="email-modal"]').attributes('data-show'),
        ).toBe('true');
    });
});
