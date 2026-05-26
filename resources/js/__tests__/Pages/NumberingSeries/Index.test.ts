import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { makeNumberingSeries, makeOrganization } from '../../helpers';

const {
    mockPost,
    mockPut,
    mockRouterGet,
    mockRouterPost,
    mockRouterDelete,
    mockRouterReload,
    mockReset,
    mockClearErrors,
} = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockPut: vi.fn(),
    mockRouterGet: vi.fn(),
    mockRouterPost: vi.fn(),
    mockRouterDelete: vi.fn(),
    mockRouterReload: vi.fn(),
    mockReset: vi.fn(),
    mockClearErrors: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (initial: Record<string, unknown>) => {
        const data = { ...initial };
        return new Proxy(data, {
            get(target, prop) {
                if (prop === 'post') return mockPost;
                if (prop === 'put') return mockPut;
                if (prop === 'delete') return vi.fn();
                if (prop === 'errors') return {};
                if (prop === 'processing') return false;
                if (prop === 'reset') return mockReset;
                if (prop === 'clearErrors') return mockClearErrors;
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
        get: mockRouterGet,
        post: mockRouterPost,
        delete: mockRouterDelete,
        reload: mockRouterReload,
    },
    usePage: () => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test',
                    email: 'test@test.test',
                    profile_photo_url: '',
                    two_factor_enabled: false,
                },
                currentTeam: {
                    id: 1,
                    name: 'Team',
                    company_name: 'Co',
                    currency: 'INR',
                },
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import NumberingSeriesIndex from '@/Pages/NumberingSeries/Index.vue';

type SeriesWithOrg = ReturnType<typeof makeNumberingSeries> & {
    organization?: ReturnType<typeof makeOrganization>;
};

function makeSeriesWithOrg(overrides = {}): SeriesWithOrg {
    return {
        ...makeNumberingSeries(overrides),
        organization: makeOrganization(),
    };
}

function makePaginatedSeries(data: SeriesWithOrg[] = [], overrides = {}) {
    return {
        data,
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: data.length,
        links: [
            { url: null, label: '&laquo; Previous', active: false },
            { url: '/numbering-series?page=1', label: '1', active: true },
            { url: null, label: 'Next &raquo;', active: false },
        ],
        ...overrides,
    };
}

const defaultProps = () => ({
    series: makePaginatedSeries(),
    organizations: [makeOrganization()],
    resetFrequencyOptions: {
        never: 'Never',
        yearly: 'Yearly',
        monthly: 'Monthly',
        financial_year: 'Financial Year',
    },
});

function mountComponent(propsOverride = {}) {
    return mount(NumberingSeriesIndex, {
        props: { ...defaultProps(), ...propsOverride },
        global: {
            stubs: {
                AppLayout: {
                    template: '<div><slot name="header" /><slot /></div>',
                },
                ConfirmationModal: {
                    template:
                        '<div v-if="show" data-testid="confirm-modal"><button data-testid="confirm-btn" @click="$emit(\'confirm\')">Confirm</button></div>',
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

describe('NumberingSeries/Index', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders page title', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Numbering Series');
    });

    it('renders series list', () => {
        const s = makeSeriesWithOrg({
            name: 'Default Invoice',
            prefix: 'INV',
            format_pattern: '{PREFIX}-{YEAR}-{SEQUENCE:4}',
            current_number: 5,
        });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });
        expect(wrapper.text()).toContain('Default Invoice');
        expect(wrapper.text()).toContain('{PREFIX}-{YEAR}-{SEQUENCE:4}');
        expect(wrapper.text()).toContain('5');
    });

    it('shows organization name in table', () => {
        const s = makeSeriesWithOrg();
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });
        expect(wrapper.text()).toContain("Maya's Team");
    });

    it('shows Default badge for default series', () => {
        const s = makeSeriesWithOrg({ is_default: true });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });
        expect(wrapper.text()).toContain('Default');
    });

    it('shows Active status badge', () => {
        const s = makeSeriesWithOrg({ is_active: true });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });
        expect(wrapper.text()).toContain('Active');
    });

    it('shows Inactive status badge', () => {
        const s = makeSeriesWithOrg({ is_active: false });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });
        expect(wrapper.text()).toContain('Inactive');
    });

    it('shows empty state when no series', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('No numbering series found');
        expect(wrapper.text()).toContain(
            'A default series will be created automatically',
        );
    });

    it('opens create form on button click', async () => {
        const wrapper = mountComponent();
        const createBtn = wrapper
            .findAll('button')
            .find(
                (b) =>
                    b.text() === 'Create New Series' ||
                    b.text() === 'Create Custom Series',
            );
        await createBtn!.trigger('click');
        expect(wrapper.text()).toContain('Create New Numbering Series');
    });

    it('opens edit form on Edit click', async () => {
        const s = makeSeriesWithOrg({ id: 1 });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });

        const editBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Edit');
        await editBtn!.trigger('click');

        expect(wrapper.text()).toContain('Edit Numbering Series');
    });

    it('calls router.post for toggle active', async () => {
        const s = makeSeriesWithOrg({ id: 3, is_active: true });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });

        const btn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Deactivate');
        await btn!.trigger('click');

        expect(mockRouterPost).toHaveBeenCalledWith(
            '/numbering-series/3/toggle-active',
            {},
            expect.any(Object),
        );
    });

    it('calls router.post for set default', async () => {
        const s = makeSeriesWithOrg({ id: 7, is_default: false });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });

        const btn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Set Default');
        await btn!.trigger('click');

        expect(mockRouterPost).toHaveBeenCalledWith(
            '/numbering-series/7/set-default',
            {},
            expect.any(Object),
        );
    });

    it('does not show Set Default for already default series', () => {
        const s = makeSeriesWithOrg({ is_default: true });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });
        const setDefaultBtns = wrapper
            .findAll('button')
            .filter((b) => b.text() === 'Set Default');
        expect(setDefaultBtns).toHaveLength(0);
    });

    it('opens delete confirmation modal', async () => {
        const s = makeSeriesWithOrg({ id: 1 });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });

        const deleteBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Delete');
        await deleteBtn!.trigger('click');

        expect(wrapper.find('[data-testid="confirm-modal"]').exists()).toBe(
            true,
        );
    });

    it('calls router.delete on confirm delete', async () => {
        const s = makeSeriesWithOrg({ id: 9 });
        const wrapper = mountComponent({ series: makePaginatedSeries([s]) });

        const deleteBtn = wrapper
            .findAll('button')
            .find((b) => b.text() === 'Delete');
        await deleteBtn!.trigger('click');

        const confirmBtn = wrapper.find('[data-testid="confirm-btn"]');
        await confirmBtn.trigger('click');

        expect(mockRouterDelete).toHaveBeenCalledWith(
            '/numbering-series/9',
            expect.any(Object),
        );
    });

    it('renders format token reference in form', async () => {
        const wrapper = mountComponent();
        const createBtn = wrapper
            .findAll('button')
            .find(
                (b) =>
                    b.text() === 'Create New Series' ||
                    b.text() === 'Create Custom Series',
            );
        await createBtn!.trigger('click');

        expect(wrapper.text()).toContain('Format Token Reference');
        expect(wrapper.text()).toContain('{PREFIX}');
        expect(wrapper.text()).toContain('{SEQUENCE:4}');
    });

    it('uses SettingsLayout and renders page content', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).toContain('Numbering Series');
    });

    it('shows pagination when multiple pages', () => {
        const wrapper = mountComponent({
            series: makePaginatedSeries([], { last_page: 2, total: 20 }),
        });
        expect(wrapper.text()).toContain('Showing page');
    });

    it('hides pagination for single page', () => {
        const wrapper = mountComponent();
        expect(wrapper.text()).not.toContain('Showing page');
    });
});
