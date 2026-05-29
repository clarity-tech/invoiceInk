import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import { nextTick } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

describe('ConfirmationModal', () => {
    it('does not render when show is false', () => {
        const wrapper = mount(ConfirmationModal, { props: { show: false } });
        expect(wrapper.find('.fixed').exists()).toBe(false);
    });

    it('renders when show is true', async () => {
        const wrapper = mount(ConfirmationModal, {
            props: { show: true },
            global: { stubs: { Teleport: true } },
        });
        await nextTick();
        expect(wrapper.text()).toContain('Confirm Action');
    });

    it('displays custom title and message', async () => {
        const wrapper = mount(ConfirmationModal, {
            props: {
                show: true,
                title: 'Delete?',
                message: 'This cannot be undone.',
            },
            global: { stubs: { Teleport: true } },
        });
        await nextTick();
        expect(wrapper.text()).toContain('Delete?');
        expect(wrapper.text()).toContain('This cannot be undone.');
    });

    it('emits confirm on confirm button click', async () => {
        const wrapper = mount(ConfirmationModal, {
            props: { show: true },
            global: { stubs: { Teleport: true } },
        });
        await nextTick();
        const buttons = wrapper.findAll('button');
        await buttons[0].trigger('click'); // first button is confirm
        expect(wrapper.emitted('confirm')).toHaveLength(1);
    });

    it('emits cancel on cancel button click', async () => {
        const wrapper = mount(ConfirmationModal, {
            props: { show: true },
            global: { stubs: { Teleport: true } },
        });
        await nextTick();
        const buttons = wrapper.findAll('button');
        await buttons[1].trigger('click'); // second button is cancel
        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });
});
