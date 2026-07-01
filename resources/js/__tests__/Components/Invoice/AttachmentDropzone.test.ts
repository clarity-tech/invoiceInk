import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import AttachmentDropzone from '@/Components/Invoice/AttachmentDropzone.vue';

function makeFile(name: string, size = 100, type = 'application/pdf'): File {
    const file = new File(['x'], name, { type });
    Object.defineProperty(file, 'size', { value: size });
    return file;
}

describe('Invoice/AttachmentDropzone', () => {
    it('renders staged files with name and size', () => {
        const wrapper = mount(AttachmentDropzone, {
            props: { modelValue: [makeFile('spec.pdf', 2048)] },
        });

        expect(wrapper.text()).toContain('spec.pdf');
        expect(wrapper.text()).toContain('2.0 KB');
    });

    it('emits an updated list without the removed file', async () => {
        const files = [makeFile('a.pdf'), makeFile('b.pdf')];
        const wrapper = mount(AttachmentDropzone, {
            props: { modelValue: files },
        });

        await wrapper
            .find('button[aria-label="Remove attachment"]')
            .trigger('click');

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted).toBeTruthy();
        expect((emitted![0][0] as File[]).map((f) => f.name)).toEqual([
            'b.pdf',
        ]);
    });

    it('adds valid dropped files to the model', async () => {
        const wrapper = mount(AttachmentDropzone, {
            props: { modelValue: [] },
        });

        await wrapper.find('[role="button"]').trigger('drop', {
            dataTransfer: { files: [makeFile('dropped.pdf')] },
        });

        const emitted = wrapper.emitted('update:modelValue');
        expect(emitted).toBeTruthy();
        expect((emitted![0][0] as File[]).map((f) => f.name)).toEqual([
            'dropped.pdf',
        ]);
    });

    it('shows an error for an unsupported dropped file and does not add it', async () => {
        const wrapper = mount(AttachmentDropzone, {
            props: { modelValue: [] },
        });

        await wrapper.find('[role="button"]').trigger('drop', {
            dataTransfer: { files: [makeFile('bad.exe')] },
        });

        expect(wrapper.emitted('update:modelValue')).toBeFalsy();
        expect(wrapper.text()).toContain('Unsupported file type');
    });
});
