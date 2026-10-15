import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { reactive } from 'vue';

const mockPost = vi.fn();
let mockErrors: Record<string, string> = {};

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) =>
        reactive({
            ...initial,
            errors: mockErrors,
            processing: false,
            post: mockPost,
        }),
}));

import DraftFromDocumentDialog from '@/Components/Invoice/DraftFromDocumentDialog.vue';

function draftButton(wrapper: ReturnType<typeof mount>) {
    return wrapper
        .findAll('button')
        .find((button) => button.text().includes('Draft invoice'))!;
}

describe('DraftFromDocumentDialog', () => {
    beforeEach(() => {
        mockErrors = {};
        vi.clearAllMocks();
    });

    it('drafts only once there is something to read', async () => {
        const wrapper = mount(DraftFromDocumentDialog, {
            props: { show: true },
        });

        expect(draftButton(wrapper).attributes('disabled')).toBeDefined();

        await wrapper
            .find('#draft-text')
            .setValue('Invoice Acme for 40 hours at ₹2,000.');

        expect(draftButton(wrapper).attributes('disabled')).toBeUndefined();
    });

    it('sends the document as form data and stays open on an error', async () => {
        const wrapper = mount(DraftFromDocumentDialog, {
            props: { show: true },
        });

        await wrapper.find('#draft-text').setValue('Invoice Acme.');
        await draftButton(wrapper).trigger('click');

        expect(mockPost).toHaveBeenCalledWith(
            '/invoices/draft',
            expect.objectContaining({
                forceFormData: true,
                preserveState: 'errors',
            }),
        );
    });

    it('drafts an estimate when opened from the estimate form', () => {
        const wrapper = mount(DraftFromDocumentDialog, {
            props: { show: true, type: 'estimate' },
        });

        expect(wrapper.text()).toContain('AI fills in the estimate');
        expect(wrapper.text()).toContain(
            'Nothing is saved until you save the estimate.',
        );
        expect(wrapper.text()).toContain('Draft estimate');
    });

    it('shows why a draft failed', () => {
        mockErrors = { draft: 'The document could not be read just now.' };

        const wrapper = mount(DraftFromDocumentDialog, {
            props: { show: true },
        });

        expect(wrapper.find('[role="alert"]').text()).toBe(
            'The document could not be read just now.',
        );
    });
});
