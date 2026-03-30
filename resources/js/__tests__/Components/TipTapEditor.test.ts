import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';

// TipTap internals are hard to mock fully; stub the heavy parts
vi.mock('@tiptap/vue-3', () => {
    const { ref } = require('vue');
    return {
        EditorContent: {
            template: '<div data-testid="editor-content" />',
            props: ['editor'],
        },
        useEditor: (opts: Record<string, unknown>) => {
            const mockEditor = {
                getHTML: () => (opts as any).content ?? '',
                commands: { setContent: vi.fn() },
                isActive: () => false,
                chain: () => ({
                    focus: () => ({
                        toggleBold: () => ({ run: vi.fn() }),
                        toggleItalic: () => ({ run: vi.fn() }),
                        toggleUnderline: () => ({ run: vi.fn() }),
                        toggleHeading: () => ({ run: vi.fn() }),
                        toggleBulletList: () => ({ run: vi.fn() }),
                        toggleOrderedList: () => ({ run: vi.fn() }),
                        toggleBlockquote: () => ({ run: vi.fn() }),
                        setLink: () => ({ run: vi.fn() }),
                        insertContent: () => ({ run: vi.fn() }),
                    }),
                }),
            };
            return ref(mockEditor);
        },
    };
});

vi.mock('@tiptap/starter-kit', () => ({
    default: { configure: () => ({}) },
}));
vi.mock('@tiptap/extension-link', () => ({
    default: { configure: () => ({}) },
}));
vi.mock('@tiptap/extension-underline', () => ({ default: {} }));
vi.mock('@tiptap/extension-placeholder', () => ({
    default: { configure: () => ({}) },
}));

import TipTapEditor from '@/Components/TipTapEditor.vue';

function mountComponent(props = {}) {
    return mount(TipTapEditor, {
        props: {
            modelValue: '<p>Hello world</p>',
            ...props,
        },
    });
}

describe('TipTapEditor', () => {
    it('renders the editor container', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[data-testid="editor-content"]').exists()).toBe(
            true,
        );
    });

    it('renders toolbar buttons', () => {
        const wrapper = mountComponent();
        const buttons = wrapper.findAll('button');
        // Bold, Italic, Underline, H2, H3, Bullet, Ordered, Blockquote, Link = 9
        expect(buttons.length).toBeGreaterThanOrEqual(9);
    });

    it('renders Bold button with title', () => {
        const wrapper = mountComponent();
        const boldBtn = wrapper.find('button[title="Bold"]');
        expect(boldBtn.exists()).toBe(true);
    });

    it('renders Italic button', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[title="Italic"]').exists()).toBe(true);
    });

    it('renders Underline button', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[title="Underline"]').exists()).toBe(true);
    });

    it('renders heading buttons (H2, H3)', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[title="Heading"]').exists()).toBe(true);
        expect(wrapper.find('button[title="Subheading"]').exists()).toBe(true);
    });

    it('renders list buttons', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[title="Bullet list"]').exists()).toBe(true);
        expect(wrapper.find('button[title="Numbered list"]').exists()).toBe(
            true,
        );
    });

    it('renders blockquote button', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[title="Quote"]').exists()).toBe(true);
    });

    it('renders link button', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('button[title="Insert link"]').exists()).toBe(true);
    });

    it('accepts modelValue prop', () => {
        const wrapper = mountComponent({ modelValue: '<p>Test content</p>' });
        expect(wrapper.vm).toBeDefined();
    });

    it('accepts placeholder prop', () => {
        const wrapper = mountComponent({ placeholder: 'Type here...' });
        expect(wrapper.vm).toBeDefined();
    });

    it('exposes insertText method', () => {
        const wrapper = mountComponent();
        expect(typeof (wrapper.vm as any).insertText).toBe('function');
    });

    it('exposes editor ref', () => {
        const wrapper = mountComponent();
        expect((wrapper.vm as any).editor).toBeDefined();
    });

    it('renders outer border container', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('.rounded-md.border').exists()).toBe(true);
    });
});
