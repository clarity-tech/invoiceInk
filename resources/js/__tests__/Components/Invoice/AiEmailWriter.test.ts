import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { reactive } from 'vue';

const mockPost = vi.fn();
let mockErrors: Record<string, string> = {};
let sent: Record<string, unknown> = {};

vi.mock('@inertiajs/vue3', () => ({
    useHttp: (initial: Record<string, unknown>) => {
        const state: Record<string, unknown> = reactive({
            ...initial,
            errors: mockErrors,
            processing: false,
        });
        let transform = (data: Record<string, unknown>) => data;

        state.transform = (callback: typeof transform) => {
            transform = callback;

            return state;
        };
        state.post = (url: string, options: Record<string, unknown>) => {
            sent = transform({ tone: state.tone, note: state.note });

            return mockPost(url, options) ?? Promise.resolve();
        };

        return state;
    },
}));

import AiEmailWriter from '@/Components/Invoice/AiEmailWriter.vue';

function mountWriter() {
    const wrapper = mount(AiEmailWriter, {
        props: {
            url: '/invoices/5/email-draft',
            payload: { template_type: 'invoice_overdue' },
            subject: 'Invoice #INV-001',
            body: '<p>Template body</p>',
            'onUpdate:subject': (subject: string) =>
                wrapper.setProps({ subject }),
            'onUpdate:body': (body: string) => wrapper.setProps({ body }),
        },
    });

    return wrapper;
}

function button(wrapper: ReturnType<typeof mount>, text: string) {
    return wrapper.findAll('button').find((b) => b.text().includes(text))!;
}

describe('AiEmailWriter', () => {
    beforeEach(() => {
        mockErrors = {};
        sent = {};
        mockPost.mockReset();
    });

    it('writes in the chosen tone, and can put back what was there', async () => {
        mockPost.mockImplementation((_url, options) => {
            options.onSuccess({
                subject: 'Payment overdue: INV-001',
                body: '<p>Written</p>',
            });

            return Promise.resolve();
        });
        const wrapper = mountWriter();

        await button(wrapper, 'Firm').trigger('click');
        await wrapper.find('input').setValue('Offer to split it');
        await button(wrapper, 'Write').trigger('click');

        expect(mockPost).toHaveBeenCalledWith(
            '/invoices/5/email-draft',
            expect.any(Object),
        );
        expect(sent).toEqual({
            tone: 'firm',
            note: 'Offer to split it',
            template_type: 'invoice_overdue',
        });
        expect(wrapper.props('subject')).toBe('Payment overdue: INV-001');
        expect(wrapper.props('body')).toBe('<p>Written</p>');
        expect(wrapper.text()).toContain('Written by AI');

        await button(wrapper, 'Undo').trigger('click');

        expect(wrapper.props('subject')).toBe('Invoice #INV-001');
        expect(wrapper.props('body')).toBe('<p>Template body</p>');
    });

    it('says why it could not write', () => {
        mockErrors = { draft: 'The email could not be written just now.' };

        expect(mountWriter().find('[role="alert"]').text()).toBe(
            'The email could not be written just now.',
        );
    });
});
