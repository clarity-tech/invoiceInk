import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';

const { mockPut, mockOptimistic } = vi.hoisted(() => {
    const mockPut = vi.fn();

    return {
        mockPut,
        mockOptimistic: vi.fn((_update: (props: any) => unknown) => ({
            put: mockPut,
        })),
    };
});

vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a><slot /></a>' },
    router: { optimistic: mockOptimistic },
}));

import Index from '@/Pages/Settings/Index.vue';

function mountPage(ai: { enabled: boolean; configured: boolean }) {
    return mount(Index, {
        props: { ai },
        global: { stubs: { AppLayout: { template: '<div><slot /></div>' } } },
    });
}

describe('Settings/Index', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('turns AI features on straight away', async () => {
        const wrapper = mountPage({ enabled: false, configured: true });
        const toggle = wrapper.find('[role="switch"]');

        expect(toggle.attributes('aria-checked')).toBe('false');

        await toggle.trigger('click');

        const update = mockOptimistic.mock.calls[0][0];
        expect(update({ ai: { enabled: false, configured: true } })).toEqual({
            ai: { enabled: true, configured: true },
        });
        expect(mockPut).toHaveBeenCalledWith(
            '/settings/ai',
            { enabled: true },
            { preserveScroll: true },
        );
    });

    it('cannot be switched on before AI is set up', () => {
        const wrapper = mountPage({ enabled: false, configured: false });

        expect(
            wrapper.find('[role="switch"]').attributes('disabled'),
        ).toBeDefined();
        expect(wrapper.text()).toContain(
            'AI is not set up on this server yet.',
        );
    });
});
