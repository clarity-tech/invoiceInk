import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent, h, nextTick } from 'vue';
import AppearanceToggle from '@/Components/AppearanceToggle.vue';
import { resetAppearance } from '../helpers';

function checkedValue(wrapper: ReturnType<typeof mount>): string | undefined {
    return wrapper
        .findAll<HTMLInputElement>('input[type="radio"]')
        .find((radio) => radio.element.checked)?.element.value;
}

describe('AppearanceToggle', () => {
    beforeEach(resetAppearance);

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('offers system, light and dark', () => {
        const wrapper = mount(AppearanceToggle);

        expect(wrapper.text()).toContain('System');
        expect(wrapper.text()).toContain('Light');
        expect(wrapper.text()).toContain('Dark');
        expect(wrapper.findAll('input[type="radio"]')).toHaveLength(3);
    });

    it('labels the radio group', () => {
        const wrapper = mount(AppearanceToggle);
        const group = wrapper.get('[role="radiogroup"]');
        const label = wrapper.get(`#${group.attributes('aria-labelledby')}`);

        expect(label.text()).toBe('Theme');
    });

    it('checks the stored choice once mounted', async () => {
        document.cookie = 'appearance=dark; path=/';

        const wrapper = mount(AppearanceToggle);
        await nextTick();

        expect(checkedValue(wrapper)).toBe('dark');
    });

    it('checks system when nothing has been chosen', async () => {
        const wrapper = mount(AppearanceToggle);
        await nextTick();

        expect(checkedValue(wrapper)).toBe('system');
    });

    it('applies and stores a new choice', async () => {
        const wrapper = mount(AppearanceToggle);

        await wrapper.get('input[value="dark"]').setValue(true);

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(document.cookie).toContain('appearance=dark');
    });

    it('gives each toggle on the page its own radio group', () => {
        // The navigation renders one in the desktop menu and one in the
        // mobile panel; sharing a name would make them a single group.
        const page = mount(
            defineComponent({
                render: () =>
                    h('div', [h(AppearanceToggle), h(AppearanceToggle)]),
            }),
        );
        const [desktop, mobile] = page.findAll('[role="radiogroup"]');

        expect(desktop.get('input').attributes('name')).not.toBe(
            mobile.get('input').attributes('name'),
        );
    });
});
