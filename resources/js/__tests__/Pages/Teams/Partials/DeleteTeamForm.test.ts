import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockDelete } = vi.hoisted(() => ({
    mockDelete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return vi.fn();
                if (prop === 'put') return vi.fn();
                if (prop === 'delete') return mockDelete;
                if (prop === 'errors') return {};
                if (prop === 'processing') return false;
                if (prop === 'reset') return vi.fn();
                if (prop === 'clearErrors') return vi.fn();
                if (prop === 'transform') return vi.fn().mockReturnThis();
                return target[prop as string];
            },
            set(target, prop, value) {
                target[prop as string] = value;
                return true;
            },
        });
    },
    Head: { template: '<div />' },
    Link: { template: '<a><slot /></a>' },
    router: { get: vi.fn(), post: vi.fn(), delete: vi.fn(), put: vi.fn() },
    usePage: () => ({
        props: {
            auth: { user: { id: 1, name: 'Test', email: 'test@test.test' } },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import DeleteTeamForm from '@/Pages/Teams/Partials/DeleteTeamForm.vue';

const defaultProps = {
    team: { id: 1, name: 'Test Team' },
};

function mountComponent(propsOverride = {}) {
    return mount(DeleteTeamForm, {
        props: { ...defaultProps, ...propsOverride },
        global: {
            stubs: {
                ConfirmationModal: {
                    template:
                        '<div v-if="show" data-testid="modal"><slot /><button data-testid="confirm-btn" @click="$emit(\'confirm\')">Confirm</button><button data-testid="cancel-btn" @click="$emit(\'cancel\')">Cancel</button></div>',
                    props: [
                        'show',
                        'title',
                        'message',
                        'confirmLabel',
                        'destructive',
                    ],
                    emits: ['confirm', 'cancel'],
                },
            },
        },
    });
}

describe('DeleteTeamForm', () => {
    beforeEach(() => {
        mockDelete.mockClear();
    });

    it('renders Delete Team heading', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Delete Team');
    });

    it('renders permanent deletion warning', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('permanently deleted');
    });

    it('renders delete button', () => {
        const wrapper = mountComponent();
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Delete Team'));
        expect(btn).toBeDefined();
    });

    it('opens confirmation modal when delete button is clicked', async () => {
        const wrapper = mountComponent();
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Delete Team'));
        await btn?.trigger('click');
        expect(wrapper.find('[data-testid="modal"]').exists()).toBe(true);
    });

    it('does not show modal initially', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('[data-testid="modal"]').exists()).toBe(false);
    });

    it('calls form.delete on confirm', async () => {
        const wrapper = mountComponent();
        // Open modal
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Delete Team'));
        await btn?.trigger('click');
        // Confirm
        const confirmBtn = wrapper.find('[data-testid="confirm-btn"]');
        await confirmBtn.trigger('click');
        expect(mockDelete).toHaveBeenCalledWith('/teams/1', expect.any(Object));
    });

    it('closes modal on cancel', async () => {
        const wrapper = mountComponent();
        const btn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Delete Team'));
        await btn?.trigger('click');
        expect(wrapper.find('[data-testid="modal"]').exists()).toBe(true);
        const cancelBtn = wrapper.find('[data-testid="cancel-btn"]');
        await cancelBtn.trigger('click');
        expect(wrapper.find('[data-testid="modal"]').exists()).toBe(false);
    });
});
