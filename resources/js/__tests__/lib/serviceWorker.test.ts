import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { setPwaMeta } from '../helpers';
import { pwaState, syncServiceWorker } from '@/lib/serviceWorker';

describe('syncServiceWorker', () => {
    let registration: {
        update: ReturnType<typeof vi.fn>;
        unregister: ReturnType<typeof vi.fn>;
    };
    let serviceWorker: {
        register: ReturnType<typeof vi.fn>;
        getRegistrations: ReturnType<typeof vi.fn>;
    };

    beforeEach(() => {
        registration = {
            update: vi.fn().mockResolvedValue(undefined),
            unregister: vi.fn().mockResolvedValue(true),
        };
        serviceWorker = {
            register: vi.fn().mockResolvedValue({}),
            getRegistrations: vi.fn().mockResolvedValue([registration]),
        };
        Object.defineProperty(navigator, 'serviceWorker', {
            value: serviceWorker,
            configurable: true,
        });
        vi.stubEnv('PROD', true);
    });

    afterEach(() => {
        Reflect.deleteProperty(navigator, 'serviceWorker');
        vi.unstubAllEnvs();
        setPwaMeta(null);
    });

    it('registers the worker once the page has loaded while the PWA is on', () => {
        setPwaMeta('enabled');

        syncServiceWorker();
        expect(serviceWorker.register).not.toHaveBeenCalled();
        window.dispatchEvent(new Event('load'));

        expect(serviceWorker.register).toHaveBeenCalledWith('/sw.js');
        expect(serviceWorker.getRegistrations).not.toHaveBeenCalled();
    });

    it('swaps in the self-removing worker while the PWA is off', async () => {
        setPwaMeta('disabled');

        syncServiceWorker();
        window.dispatchEvent(new Event('load'));

        await vi.waitFor(() => expect(registration.update).toHaveBeenCalled());
        expect(registration.unregister).not.toHaveBeenCalled();
        expect(serviceWorker.register).not.toHaveBeenCalled();
    });

    it('unregisters directly when the self-removing worker cannot be fetched', async () => {
        setPwaMeta('disabled');
        registration.update.mockRejectedValue(new TypeError('Failed to fetch'));

        syncServiceWorker();

        await vi.waitFor(() =>
            expect(registration.unregister).toHaveBeenCalledOnce(),
        );
    });

    it('leaves the worker alone on a page without the switch', async () => {
        setPwaMeta(null);

        syncServiceWorker();
        window.dispatchEvent(new Event('load'));
        await Promise.resolve();

        expect(pwaState()).toBeNull();
        expect(serviceWorker.register).not.toHaveBeenCalled();
        expect(serviceWorker.getRegistrations).not.toHaveBeenCalled();
    });
});
