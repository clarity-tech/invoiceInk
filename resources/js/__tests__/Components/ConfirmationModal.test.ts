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

    describe('focus management', () => {
        // The dialog must be in the real document for focus to move into it.
        function open(props: Record<string, unknown> = {}) {
            return mount(ConfirmationModal, {
                props: { show: false, ...props },
                attachTo: document.body,
                global: { stubs: { Teleport: true } },
            });
        }

        it('emits cancel when Escape is pressed', async () => {
            const wrapper = open();
            await wrapper.setProps({ show: true });
            await nextTick();

            document.dispatchEvent(
                new KeyboardEvent('keydown', { key: 'Escape' }),
            );

            expect(wrapper.emitted('cancel')).toHaveLength(1);
            wrapper.unmount();
        });

        it('stops listening for Escape once closed', async () => {
            const wrapper = open();
            await wrapper.setProps({ show: true });
            await nextTick();
            await wrapper.setProps({ show: false });
            await nextTick();

            document.dispatchEvent(
                new KeyboardEvent('keydown', { key: 'Escape' }),
            );

            expect(wrapper.emitted('cancel')).toBeUndefined();
            wrapper.unmount();
        });

        it('focuses cancel first on a destructive dialog', async () => {
            const wrapper = open({ destructive: true });
            await wrapper.setProps({ show: true });
            await nextTick();

            const cancel = wrapper.findAll('button')[1];
            expect(document.activeElement).toBe(cancel.element);
            wrapper.unmount();
        });

        it('focuses the first action on a non-destructive dialog', async () => {
            const wrapper = open({ destructive: false });
            await wrapper.setProps({ show: true });
            await nextTick();

            const confirm = wrapper.findAll('button')[0];
            expect(document.activeElement).toBe(confirm.element);
            wrapper.unmount();
        });

        it('restores focus to the opener when dismissed', async () => {
            const opener = document.createElement('button');
            document.body.appendChild(opener);
            opener.focus();
            expect(document.activeElement).toBe(opener);

            const wrapper = open({ destructive: true });
            await wrapper.setProps({ show: true });
            await nextTick();
            expect(document.activeElement).not.toBe(opener);

            await wrapper.setProps({ show: false });
            await nextTick();

            expect(document.activeElement).toBe(opener);
            wrapper.unmount();
            opener.remove();
        });

        it('wraps focus from the last action back to the first', async () => {
            const wrapper = open();
            await wrapper.setProps({ show: true });
            await nextTick();

            const [confirm, cancel] = wrapper
                .findAll('button')
                .map((b) => b.element as HTMLButtonElement);

            cancel.focus();
            document.dispatchEvent(
                new KeyboardEvent('keydown', { key: 'Tab', cancelable: true }),
            );

            expect(document.activeElement).toBe(confirm);
            wrapper.unmount();
        });

        it('wraps focus backwards from the first action to the last', async () => {
            const wrapper = open();
            await wrapper.setProps({ show: true });
            await nextTick();

            const [confirm, cancel] = wrapper
                .findAll('button')
                .map((b) => b.element as HTMLButtonElement);

            confirm.focus();
            document.dispatchEvent(
                new KeyboardEvent('keydown', {
                    key: 'Tab',
                    shiftKey: true,
                    cancelable: true,
                }),
            );

            expect(document.activeElement).toBe(cancel);
            wrapper.unmount();
        });
    });
});
