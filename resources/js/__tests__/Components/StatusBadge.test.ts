import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import StatusBadge from '@/Components/StatusBadge.vue';

const statuses = [
    'draft',
    'sent',
    'accepted',
    'partially_paid',
    'paid',
    'void',
] as const;
const expectedLabels: Record<string, string> = {
    draft: 'Draft',
    sent: 'Sent',
    accepted: 'Accepted',
    partially_paid: 'Partially Paid',
    paid: 'Paid',
    void: 'Void',
};

describe('StatusBadge', () => {
    for (const status of statuses) {
        it(`renders "${expectedLabels[status]}" for status "${status}"`, () => {
            const wrapper = mount(StatusBadge, { props: { status } });
            expect(wrapper.text()).toBe(expectedLabels[status]);
        });
    }

    it('applies green classes for paid status', () => {
        const wrapper = mount(StatusBadge, { props: { status: 'paid' } });
        expect(wrapper.find('span').classes()).toContain('bg-green-100');
    });
});
