import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockPost, mockClearErrors, mockRouterVisit } = vi.hoisted(() => ({
    mockPost: vi.fn(),
    mockClearErrors: vi.fn(),
    mockRouterVisit: vi.fn(),
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
    router: { visit: mockRouterVisit, get: vi.fn() },
    Head: { template: '<div />' },
    Link: { template: '<a><slot /></a>' },
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
                    personal_team: false,
                },
            },
            flash: { success: null, error: null, message: null },
        },
    }),
}));

import SetupPage from '@/Pages/Organizations/Setup.vue';

function makeCountryInfo(overrides = {}) {
    return {
        value: 'IN',
        label: 'India',
        currency: 'INR',
        financial_year_options: {
            april_march: 'April - March',
            calendar: 'Calendar Year',
        },
        default_financial_year: 'april_march',
        supported_currencies: { INR: 'Indian Rupee', USD: 'US Dollar' },
        tax_system: { name: 'GST', rates: ['5%', '12%', '18%', '28%'] },
        recommended_numbering: '{PREFIX}-{FY}-{SEQUENCE:4}',
        ...overrides,
    };
}

function makeOrg(overrides = {}) {
    return {
        id: 1,
        name: "Test's Team",
        company_name: '',
        tax_number: '',
        registration_number: '',
        website: '',
        notes: '',
        phone: '',
        emails: [],
        currency: '',
        country_code: '',
        financial_year_type: '',
        financial_year_start_month: 4,
        financial_year_start_day: 1,
        primary_location: null,
        ...overrides,
    };
}

const countries = [
    makeCountryInfo(),
    makeCountryInfo({
        value: 'AE',
        label: 'United Arab Emirates',
        currency: 'AED',
        supported_currencies: { AED: 'UAE Dirham', USD: 'US Dollar' },
        default_financial_year: 'calendar',
        tax_system: { name: 'VAT', rates: ['5%'] },
        financial_year_options: { calendar: 'Calendar Year' },
    }),
];

const currencies = { INR: 'Indian Rupee', USD: 'US Dollar', AED: 'UAE Dirham' };

function mountComponent(propsOverride = {}) {
    return mount(SetupPage, {
        props: {
            organization: makeOrg(),
            countries,
            currencies,
            ...propsOverride,
        },
        global: {
            stubs: {
                AppLayout: {
                    template: '<div><slot name="header" /><slot /></div>',
                },
            },
        },
    });
}

/**
 * Helper to simulate successful step submission so we can navigate steps.
 * Since form.post is mocked, we trigger the onSuccess callback manually.
 */
function simulateStepSuccess() {
    mockPost.mockImplementation(
        (_url: string, options: { onSuccess?: () => void }) => {
            options?.onSuccess?.();
        },
    );
}

describe('OrganizationSetupFlow', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockPost.mockReset();
    });

    describe('step 1 - company information', () => {
        it('starts at step 1', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Company Information');
            expect(wrapper.text()).toContain('Step 1 of 4');
        });

        it('shows company name, tax number, registration number, website, notes fields', () => {
            const wrapper = mountComponent();
            expect(wrapper.find('#company_name').exists()).toBe(true);
            expect(wrapper.find('#tax_number').exists()).toBe(true);
            expect(wrapper.find('#registration_number').exists()).toBe(true);
            expect(wrapper.find('#website').exists()).toBe(true);
            expect(wrapper.find('#notes').exists()).toBe(true);
        });

        it('does not show Previous button on step 1', () => {
            const wrapper = mountComponent();
            const prevBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Previous');
            expect(prevBtn).toBeUndefined();
        });

        it('shows Next button', () => {
            const wrapper = mountComponent();
            const nextBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Next');
            expect(nextBtn).toBeDefined();
        });

        it('calls form.post with step=1 on Next click', async () => {
            const wrapper = mountComponent();
            const nextBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Next');
            await nextBtn!.trigger('click');
            expect(mockPost).toHaveBeenCalledWith(
                '/organization/setup/1/step',
                expect.any(Object),
            );
        });
    });

    describe('step 2 - primary location', () => {
        it('navigates to step 2 after step 1 success', async () => {
            simulateStepSuccess();
            const wrapper = mountComponent();
            const nextBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Next');
            await nextBtn!.trigger('click');
            await wrapper.vm.$nextTick();
            expect(wrapper.text()).toContain('Primary Location');
            expect(wrapper.text()).toContain('Your main business address');
        });

        it('shows location fields on step 2', async () => {
            simulateStepSuccess();
            const wrapper = mountComponent();
            const nextBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Next');
            await nextBtn!.trigger('click');
            await wrapper.vm.$nextTick();
            expect(wrapper.find('#location_name').exists()).toBe(true);
            expect(wrapper.find('#gstin').exists()).toBe(true);
            expect(wrapper.find('#address_line_1').exists()).toBe(true);
            expect(wrapper.find('#city').exists()).toBe(true);
            expect(wrapper.find('#state').exists()).toBe(true);
            expect(wrapper.find('#postal_code').exists()).toBe(true);
        });

        it('shows Previous button on step 2', async () => {
            simulateStepSuccess();
            const wrapper = mountComponent();
            const nextBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Next');
            await nextBtn!.trigger('click');
            await wrapper.vm.$nextTick();
            const prevBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Previous');
            expect(prevBtn).toBeDefined();
        });

        it('Previous button goes back to step 1', async () => {
            simulateStepSuccess();
            const wrapper = mountComponent();
            // Go to step 2
            const nextBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Next');
            await nextBtn!.trigger('click');
            await wrapper.vm.$nextTick();
            // Go back
            const prevBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Previous');
            await prevBtn!.trigger('click');
            await wrapper.vm.$nextTick();
            expect(wrapper.text()).toContain('Company Information');
            expect(mockClearErrors).toHaveBeenCalled();
        });
    });

    describe('step 3 - configuration', () => {
        async function goToStep3(wrapper: ReturnType<typeof mountComponent>) {
            simulateStepSuccess();
            // step 1 -> 2
            let nextBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Next');
            await nextBtn!.trigger('click');
            await wrapper.vm.$nextTick();
            // step 2 -> 3
            nextBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Next');
            await nextBtn!.trigger('click');
            await wrapper.vm.$nextTick();
        }

        it('shows configuration fields on step 3', async () => {
            const wrapper = mountComponent();
            await goToStep3(wrapper);
            expect(wrapper.text()).toContain('Configuration');
            expect(wrapper.text()).toContain('Currency');
            expect(wrapper.find('#country_code').exists()).toBe(true);
            expect(wrapper.find('#currency').exists()).toBe(true);
        });

        it('shows country options', async () => {
            const wrapper = mountComponent();
            await goToStep3(wrapper);
            expect(wrapper.text()).toContain('India');
            expect(wrapper.text()).toContain('United Arab Emirates');
        });

        it('selecting country updates available currencies', async () => {
            const wrapper = mountComponent();
            await goToStep3(wrapper);
            const countrySelect = wrapper.find('#country_code');
            await countrySelect.setValue('AE');
            await wrapper.vm.$nextTick();
            // UAE supported currencies
            expect(wrapper.text()).toContain('UAE Dirham');
        });

        it('shows country info panel when organization already has country_code', async () => {
            simulateStepSuccess();
            const wrapper = mountComponent({
                organization: makeOrg({ country_code: 'IN', currency: 'INR' }),
            });
            await goToStep3(wrapper);
            expect(wrapper.text()).toContain('Tax System:');
            expect(wrapper.text()).toContain('GST');
            expect(wrapper.text()).toContain('Common Rates:');
        });

        it('shows financial year dropdown when organization has country_code', async () => {
            simulateStepSuccess();
            const wrapper = mountComponent({
                organization: makeOrg({ country_code: 'IN', currency: 'INR' }),
            });
            await goToStep3(wrapper);
            expect(wrapper.text()).toContain('Financial Year');
            expect(wrapper.text()).toContain('April - March');
        });
    });

    describe('step 4 - contact details', () => {
        async function goToStep4(wrapper: ReturnType<typeof mountComponent>) {
            simulateStepSuccess();
            for (let i = 0; i < 3; i++) {
                const nextBtn = wrapper
                    .findAll('button')
                    .find((b) => b.text() === 'Next');
                await nextBtn!.trigger('click');
                await wrapper.vm.$nextTick();
            }
        }

        it('shows contact details on step 4', async () => {
            const wrapper = mountComponent();
            await goToStep4(wrapper);
            expect(wrapper.text()).toContain('Contact Details');
            expect(wrapper.text()).toContain('Email Addresses');
            expect(wrapper.text()).toContain('Phone Number');
        });

        it('shows "Complete Setup" button on step 4', async () => {
            const wrapper = mountComponent();
            await goToStep4(wrapper);
            const completeBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Complete Setup');
            expect(completeBtn).toBeDefined();
        });

        it('shows "+ Add email" button', async () => {
            const wrapper = mountComponent();
            await goToStep4(wrapper);
            const addBtn = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Add email'));
            expect(addBtn).toBeDefined();
        });

        it('shows "+ Add email" button and email input exists', async () => {
            const wrapper = mountComponent({
                organization: makeOrg({ emails: ['admin@co.test'] }),
            });
            await goToStep4(wrapper);
            const emailInputs = wrapper.findAll('input[type="email"]');
            expect(emailInputs.length).toBeGreaterThanOrEqual(1);
            const addBtn = wrapper
                .findAll('button')
                .find((b) => b.text().includes('Add email'));
            expect(addBtn).toBeDefined();
        });

        it('shows remove button for additional email inputs', async () => {
            const wrapper = mountComponent({
                organization: makeOrg({
                    emails: ['one@co.test', 'two@co.test'],
                }),
            });
            await goToStep4(wrapper);
            const emailInputs = wrapper.findAll('input[type="email"]');
            expect(emailInputs.length).toBe(2);
            // Remove button should exist (for index > 0)
            const removeBtn = wrapper.findAll('button').find((b) => {
                return b.find('svg path[fill-rule="evenodd"]').exists();
            });
            expect(removeBtn).toBeDefined();
        });

        it('calls form.post with step=4 on Complete Setup click', async () => {
            const wrapper = mountComponent();
            await goToStep4(wrapper);
            // Reset mock to capture the step 4 call
            mockPost.mockReset();
            mockPost.mockImplementation(() => {});
            const completeBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Complete Setup');
            await completeBtn!.trigger('click');
            expect(mockPost).toHaveBeenCalledWith(
                '/organization/setup/1/step',
                expect.any(Object),
            );
        });
    });

    describe('step navigation', () => {
        it('progress bar shows all 4 step titles', () => {
            const wrapper = mountComponent();
            expect(wrapper.text()).toContain('Company Information');
            expect(wrapper.text()).toContain('Primary Location');
            expect(wrapper.text()).toContain('Configuration');
            expect(wrapper.text()).toContain('Contact Details');
        });

        it('step number buttons are rendered', () => {
            const wrapper = mountComponent();
            const stepBtns = wrapper
                .findAll('button')
                .filter((b) => /^[1-4]$/.test(b.text().trim()));
            expect(stepBtns.length).toBe(4);
        });

        it('pre-fills form when organization has existing data', () => {
            const wrapper = mountComponent({
                organization: makeOrg({
                    company_name: 'Existing Corp',
                    currency: 'USD',
                    country_code: 'IN',
                }),
            });
            const nameInput = wrapper.find('#company_name');
            expect((nameInput.element as HTMLInputElement).value).toBe(
                'Existing Corp',
            );
        });
    });

    describe('processing state', () => {
        it('shows Next button text (not Processing) by default', () => {
            const wrapper = mountComponent();
            const nextBtn = wrapper
                .findAll('button')
                .find((b) => b.text() === 'Next');
            expect(nextBtn).toBeDefined();
            expect(wrapper.text()).not.toContain('Processing...');
        });
    });
});
