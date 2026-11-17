import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, ref } from 'vue';

vi.mock('@inertiajs/vue3', () => ({
    router: { on: () => () => {} },
}));

import NetworkErrorNotice from '@/Components/NetworkErrorNotice.vue';
import { Dialog } from '@/Components/ui/dialog';
import {
    defineLazyDialog,
    dismissLazyDialogFailure,
    useLazyDialog,
    type LazyDialog,
} from '@/composables/useLazyDialog';

/** Stands in for EmailModal: the real Dialog, so its focus handling runs. */
const Panel = defineComponent({
    name: 'Panel',
    components: { Dialog },
    props: { show: Boolean },
    emits: ['close'],
    template:
        '<Dialog :open="show" @close="$emit(\'close\')"><button>Inside</button></Dialog>',
});

type PanelModule = { default: typeof Panel };

/** What `import()` gives the loader: an ES module whose default is Panel. */
const panelModule = { __esModule: true, default: Panel } as PanelModule;

function deferred() {
    let resolve!: (module: PanelModule) => void;
    let reject!: (error: Error) => void;
    const promise = new Promise<PanelModule>((res, rej) => {
        resolve = res;
        reject = rej;
    });

    return { promise, resolve, reject };
}

/** A page with one trigger for the lazy dialog, wired the way pages are. */
function mountPage(dialog: LazyDialog<typeof Panel>) {
    const Page = defineComponent({
        components: { NetworkErrorNotice },
        setup() {
            const LazyPanel = dialog.component;
            const opener = useLazyDialog(dialog);
            const show = ref(false);

            return { LazyPanel, opener, show };
        },
        template: `
            <div>
                <button
                    data-testid="trigger"
                    :aria-busy="opener.busy"
                    v-on="opener.intent"
                    @click="opener.open(() => { show = true })"
                >Open</button>
                <component :is="LazyPanel" v-if="opener.loaded" :show="show" @close="show = false" />
                <NetworkErrorNotice />
            </div>
        `,
    });

    return mount(Page, { attachTo: document.body });
}

function panel(): HTMLElement | null {
    return document.querySelector('[data-slot="dialog"]');
}

describe('useLazyDialog', () => {
    beforeEach(() => {
        dismissLazyDialogFailure();
        document.body.innerHTML = '';
    });

    it('shows the trigger busy until the code arrives, then hands focus to the dialog and back', async () => {
        const chunk = deferred();
        const wrapper = mountPage(defineLazyDialog(() => chunk.promise));
        const trigger = wrapper.get('[data-testid="trigger"]');

        (trigger.element as HTMLElement).focus();
        await trigger.trigger('click');

        expect(trigger.attributes('aria-busy')).toBe('true');
        expect(panel()).toBeNull();
        // Still focused: a busy trigger is never disabled, so the dialog can
        // remember it as the place to return to.
        expect(document.activeElement).toBe(trigger.element);

        chunk.resolve(panelModule);
        await flushPromises();

        expect(trigger.attributes('aria-busy')).toBe('false');
        expect(panel()).not.toBeNull();
        expect(document.activeElement).toBe(panel());

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await flushPromises();

        expect(panel()).toBeNull();
        expect(document.activeElement).toBe(trigger.element);

        wrapper.unmount();
    });

    it('opens at once when the code is already there', async () => {
        const wrapper = mountPage(
            defineLazyDialog(() => Promise.resolve(panelModule)),
        );
        const trigger = wrapper.get('[data-testid="trigger"]');

        await trigger.trigger('mouseenter');
        await flushPromises();

        await trigger.trigger('click');

        expect(panel()).not.toBeNull();
        expect(trigger.attributes('aria-busy')).toBe('false');

        wrapper.unmount();
    });

    it('starts fetching on pointerdown, focus or hover, and only once', async () => {
        const importer = vi.fn(() => Promise.resolve(panelModule));
        const wrapper = mountPage(defineLazyDialog(importer));
        const trigger = wrapper.get('[data-testid="trigger"]');

        await trigger.trigger('pointerdown');
        await trigger.trigger('focus');
        await trigger.trigger('mouseenter');
        await flushPromises();

        expect(importer).toHaveBeenCalledTimes(1);
        // Fetched, so it is mounted, but nobody has opened it.
        expect(panel()).toBeNull();

        wrapper.unmount();
    });

    it('reports a click that cannot load the dialog, and fetches again on the next', async () => {
        const importer = vi
            .fn<() => Promise<PanelModule>>()
            .mockImplementationOnce(() =>
                Promise.reject(new Error('Failed to fetch')),
            )
            .mockImplementationOnce(() => Promise.resolve(panelModule));
        const wrapper = mountPage(defineLazyDialog(importer));
        const trigger = wrapper.get('[data-testid="trigger"]');

        await trigger.trigger('click');
        await flushPromises();

        const notice = wrapper.get('[data-testid="network-error"]');
        expect(notice.text()).toContain("That didn't open");
        expect(notice.text()).toContain('Reload page');
        expect(trigger.attributes('aria-busy')).toBe('false');
        expect(panel()).toBeNull();

        await trigger.trigger('click');
        await flushPromises();

        expect(importer).toHaveBeenCalledTimes(2);
        expect(panel()).not.toBeNull();
        expect(wrapper.find('[data-testid="network-error"]').exists()).toBe(
            false,
        );

        wrapper.unmount();
    });

    it('says nothing when only a prefetch fails', async () => {
        const wrapper = mountPage(
            defineLazyDialog(() =>
                Promise.reject<PanelModule>(new Error('Failed to fetch')),
            ),
        );

        await wrapper.get('[data-testid="trigger"]').trigger('mouseenter');
        await flushPromises();

        expect(wrapper.find('[data-testid="network-error"]').exists()).toBe(
            false,
        );

        wrapper.unmount();
    });
});
