import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    fireInstallPrompt,
    IPHONE_SAFARI_UA,
    loadInstallPrompt,
    stubInstallEnvironment,
} from '../helpers';

const IPHONE_CHROME =
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0 Mobile/15E148 Safari/604.1';
const IPHONE_FACEBOOK =
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [FBAN/FBIOS;FBAV/450.0.0.0]';
const IPHONE_GOOGLE_APP =
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/300.0.598994205 Mobile/15E148 Safari/604.1';

type InstallPromptModule = Awaited<ReturnType<typeof loadInstallPrompt>>;

/** A new page load: fresh module state, listening from the start. */
async function pageLoad(): Promise<InstallPromptModule> {
    const module = await loadInstallPrompt();
    module.listenForInstallPrompt();

    return module;
}

describe('useInstallPrompt', () => {
    let useInstallPrompt: InstallPromptModule['useInstallPrompt'];

    beforeEach(async () => {
        localStorage.clear();
        stubInstallEnvironment();
        ({ useInstallPrompt } = await pageLoad());
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('offers nothing until the browser says the app is installable', () => {
        expect(useInstallPrompt().mode.value).toBeNull();
    });

    it('offers the install button once the browser fires the prompt', () => {
        const event = fireInstallPrompt();

        expect(event.defaultPrevented).toBe(true);
        expect(useInstallPrompt().mode.value).toBe('prompt');
    });

    it('shows the browser prompt on install and then goes away', async () => {
        const event = fireInstallPrompt('accepted');
        const { mode, install } = useInstallPrompt();

        await install();

        expect(event.prompt).toHaveBeenCalledOnce();
        expect(mode.value).toBeNull();
    });

    it('snoozes for 30 days when the person declines the browser prompt', async () => {
        fireInstallPrompt('dismissed');
        const { mode, install } = useInstallPrompt();

        await install();

        expect(mode.value).toBeNull();
        const until = Number(localStorage.getItem('pwa-install-snoozed-until'));
        expect(until).toBeGreaterThan(Date.now() + 29 * 24 * 60 * 60 * 1000);
    });

    it('snoozes instead of throwing when the browser refuses to prompt', async () => {
        const event = fireInstallPrompt();
        event.prompt.mockRejectedValue(new DOMException('NotAllowedError'));
        const { mode, install } = useInstallPrompt();

        await expect(install()).resolves.toBeUndefined();

        expect(mode.value).toBeNull();
        expect(
            localStorage.getItem('pwa-install-snoozed-until'),
        ).not.toBeNull();
    });

    it('stays hidden after "Not now", across page loads', async () => {
        fireInstallPrompt();
        const { mode, dismiss } = useInstallPrompt();

        dismiss();
        expect(mode.value).toBeNull();

        const reloaded = await pageLoad();
        fireInstallPrompt();
        expect(reloaded.useInstallPrompt().mode.value).toBeNull();
    });

    it('offers again once the snooze has run out', async () => {
        localStorage.setItem(
            'pwa-install-snoozed-until',
            String(Date.now() - 1),
        );

        const reloaded = await pageLoad();
        fireInstallPrompt();

        expect(reloaded.useInstallPrompt().mode.value).toBe('prompt');
    });

    it('hides once the app has been installed', () => {
        fireInstallPrompt();

        window.dispatchEvent(new Event('appinstalled'));

        expect(useInstallPrompt().mode.value).toBeNull();
    });

    it('does not offer to install an app that is already running installed', () => {
        stubInstallEnvironment({ standalone: true });
        fireInstallPrompt();

        expect(useInstallPrompt().mode.value).toBeNull();
    });

    it('does not show the banner on desktop', () => {
        stubInstallEnvironment({ touch: false });
        fireInstallPrompt();

        expect(useInstallPrompt().mode.value).toBeNull();
    });

    it('shows Add to Home Screen instructions in iOS Safari', () => {
        stubInstallEnvironment({ userAgent: IPHONE_SAFARI_UA });

        expect(useInstallPrompt().mode.value).toBe('ios');
    });

    it.each([
        ['Chrome', IPHONE_CHROME],
        ['the Facebook in-app browser', IPHONE_FACEBOOK],
        ['the Google app', IPHONE_GOOGLE_APP],
    ])('does not show iOS instructions in %s', (_name, userAgent) => {
        stubInstallEnvironment({ userAgent });

        expect(useInstallPrompt().mode.value).toBeNull();
    });
});
