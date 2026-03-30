import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockPost, mockRouterDelete } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockRouterDelete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return mockPost;
                if (prop === 'put') return vi.fn();
                if (prop === 'delete') return vi.fn();
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
    router: {
        get: vi.fn(),
        post: vi.fn(),
        delete: mockRouterDelete,
        put: vi.fn(),
    },
    usePage: () => ({
        props: {
            auth: { user: { id: 1, name: 'Test', email: 'test@test.test' } },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import EditLogoForm from '@/Pages/Organizations/Partials/EditLogoForm.vue';

function makeOrg(overrides = {}) {
    return {
        id: 1,
        name: 'Clarity Technologies',
        company_name: null,
        phone: null,
        emails: [{ email: 'test@test.test', name: 'Test' }],
        currency: 'INR',
        country_code: 'IN',
        financial_year_type: 'april_march',
        financial_year_start_month: 4,
        financial_year_start_day: 1,
        tax_number: null,
        registration_number: null,
        website: null,
        notes: null,
        bank_details: null,
        logo_url: null,
        primary_location: null,
        personal_team: false,
        ...overrides,
    };
}

function mountComponent(propsOverride = {}) {
    return mount(EditLogoForm, {
        props: { organization: makeOrg(), ...propsOverride },
    });
}

describe('EditLogoForm', () => {
    beforeEach(() => {
        mockPost.mockClear();
        mockRouterDelete.mockClear();
    });

    it('renders Organization Logo heading', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Organization Logo');
    });

    it('renders description text', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Your logo appears on invoices');
    });

    it('renders file input', () => {
        const wrapper = mountComponent();
        expect(wrapper.find('#logo-upload').exists()).toBe(true);
    });

    it('renders Choose file label', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Choose file');
    });

    it('shows file type hint', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('JPG, PNG, GIF, or SVG');
    });

    it('shows current logo when logo_url is set', () => {
        const wrapper = mountComponent({
            organization: makeOrg({ logo_url: '/logos/test.png' }),
        });
        expect(wrapper.text()).toContain('Current Logo');
        const img = wrapper.find('img[alt="Clarity Technologies"]');
        expect(img.exists()).toBe(true);
        expect(img.attributes('src')).toBe('/logos/test.png');
    });

    it('does not show current logo section when no logo', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).not.toContain('Current Logo');
    });

    it('shows remove button when logo exists', () => {
        const wrapper = mountComponent({
            organization: makeOrg({ logo_url: '/logos/test.png' }),
        });
        expect(wrapper.text()).toContain('Remove');
    });

    it('calls router.delete when remove is clicked', async () => {
        const wrapper = mountComponent({
            organization: makeOrg({ logo_url: '/logos/test.png' }),
        });
        const removeBtn = wrapper
            .findAll('button')
            .find((b) => b.text().includes('Remove'));
        await removeBtn?.trigger('click');
        expect(mockRouterDelete).toHaveBeenCalledWith(
            '/organizations/1/logo',
            expect.any(Object),
        );
    });

    it('shows no validation errors by default', () => {
        const wrapper = mountComponent();
        const errors = wrapper.findAll('.text-red-600');
        expect(errors.length).toBe(0);
    });
});
