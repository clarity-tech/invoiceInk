import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import DocumentOpenActions from '@/Components/Invoice/DocumentOpenActions.vue';
import type { Invoice } from '@/types';
import { makeInvoice, makeOrganization } from '../../helpers';

// Minted and signed by the server; the component only passes it on.
const SHARE_URL =
    'https://invoiceink.test/invoices/view/abc123?expires=1767225600&signature=abc';

/**
 * The browser features the button decides between. A phone with a share
 * sheet unless told otherwise; `noShareSheet` is a browser without share().
 */
function stubDevice({
    coarse = true,
    noShareSheet = false,
    share = vi.fn().mockResolvedValue(undefined),
    canShare,
    writeText = vi.fn().mockResolvedValue(undefined),
}: {
    coarse?: boolean;
    noShareSheet?: boolean;
    share?: ReturnType<typeof vi.fn>;
    canShare?: ReturnType<typeof vi.fn>;
    writeText?: ReturnType<typeof vi.fn>;
} = {}) {
    vi.stubGlobal(
        'matchMedia',
        vi.fn((query: string) => ({
            matches: query === '(pointer: coarse)' ? coarse : false,
        })),
    );
    Object.defineProperty(navigator, 'share', {
        value: noShareSheet ? undefined : share,
        configurable: true,
    });
    Object.defineProperty(navigator, 'canShare', {
        value: canShare,
        configurable: true,
    });
    Object.defineProperty(navigator, 'clipboard', {
        value: { writeText },
        configurable: true,
    });

    return { share, writeText };
}

function mountActions(invoice: Partial<Invoice> = {}) {
    return mount(DocumentOpenActions, {
        props: {
            invoice: makeInvoice({
                invoice_number: 'INV-26001',
                total: 11800000,
                currency: 'INR',
                due_at: '2026-12-13',
                organization: makeOrganization(),
                ...invoice,
            }),
            shareLink: {
                url: SHARE_URL,
                expires_at: '2026-11-13T10:00:00+05:30',
            },
        },
    });
}

function linkButton(wrapper: ReturnType<typeof mountActions>) {
    return wrapper
        .findAll('button')
        .find((b) => ['Share', 'Copy link'].includes(b.text()))!;
}

async function tap(wrapper: ReturnType<typeof mountActions>) {
    await linkButton(wrapper).trigger('click');
    await flushPromises();
}

function status(wrapper: ReturnType<typeof mountActions>) {
    return wrapper.find('[role="status"]').text();
}

describe('DocumentOpenActions', () => {
    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
        // Own properties only, so happy-dom's own implementations come back.
        for (const key of ['share', 'canShare', 'clipboard']) {
            delete (navigator as unknown as Record<string, unknown>)[key];
        }
        delete (document as unknown as Record<string, unknown>).visibilityState;
    });

    describe('on a phone with a share sheet', () => {
        it('offers Share, and shares the document with a message that stands on its own', async () => {
            const { share, writeText } = stubDevice();
            const wrapper = mountActions();

            expect(linkButton(wrapper).text()).toBe('Share');

            await tap(wrapper);

            expect(share).toHaveBeenCalledOnce();
            expect(share).toHaveBeenCalledWith({
                title: 'Invoice INV-26001 from Clarity Technologies',
                text: 'Invoice INV-26001 from Clarity Technologies for ₹1,18,000.00, due 13 Dec 2026.',
                url: SHARE_URL,
            });
            expect(writeText).not.toHaveBeenCalled();
        });

        // The customer reads the shared text; the sharer reads this, and is
        // the one who can tell the customer when the link stops working.
        it('tells the sharer how long the link works', async () => {
            stubDevice();
            const wrapper = mountActions();

            await tap(wrapper);

            expect(status(wrapper)).toBe(
                'Link shared. It works until 13 Nov 2026.',
            );
        });

        it('calls an estimate an estimate, and its date the date it is valid until', async () => {
            const { share } = stubDevice();
            const wrapper = mountActions({
                type: 'estimate',
                invoice_number: 'EST-26001',
                total: 5000000,
            });

            await tap(wrapper);

            expect(share).toHaveBeenCalledWith({
                title: 'Estimate EST-26001 from Clarity Technologies',
                text: 'Estimate EST-26001 from Clarity Technologies for ₹50,000.00, valid until 13 Dec 2026.',
                url: SHARE_URL,
            });
        });

        it('leaves the date out when the document has none', async () => {
            const { share } = stubDevice();
            const wrapper = mountActions({ due_at: null });

            await tap(wrapper);

            expect(share.mock.calls[0][0].text).toBe(
                'Invoice INV-26001 from Clarity Technologies for ₹1,18,000.00.',
            );
        });

        // A customer who has paid half should be asked for the other half,
        // not the whole amount again.
        it('leads with what is still owed on a partly paid invoice', async () => {
            const { share } = stubDevice();
            const wrapper = mountActions({
                total: 10030000,
                amount_paid: 5015000,
            });

            await tap(wrapper);

            expect(share.mock.calls[0][0].text).toBe(
                'Invoice INV-26001 from Clarity Technologies: ₹50,150.00 of ₹1,00,300.00 still due by 13 Dec 2026.',
            );
        });

        it('gives the balance without a date when a partly paid invoice has none', async () => {
            const { share } = stubDevice();
            const wrapper = mountActions({
                total: 10030000,
                amount_paid: 5015000,
                due_at: null,
            });

            await tap(wrapper);

            expect(share.mock.calls[0][0].text).toBe(
                'Invoice INV-26001 from Clarity Technologies: ₹50,150.00 of ₹1,00,300.00 still due.',
            );
        });

        it('says nothing is due once the invoice is paid in full', async () => {
            const { share } = stubDevice();
            const wrapper = mountActions({ amount_paid: 11800000 });

            await tap(wrapper);

            expect(share.mock.calls[0][0].text).toBe(
                'Invoice INV-26001 from Clarity Technologies for ₹1,18,000.00, paid in full.',
            );
        });

        it('formats the total in the document currency', async () => {
            const { share } = stubDevice();
            const wrapper = mountActions({ currency: 'USD', total: 250000 });

            await tap(wrapper);

            expect(share.mock.calls[0][0].text).toContain('for $2,500.00,');
        });

        it('names the organization when it has no company name', async () => {
            const { share } = stubDevice();
            const wrapper = mountActions({
                organization: makeOrganization({
                    company_name: null,
                    name: 'Maya Studio',
                }),
            });

            await tap(wrapper);

            expect(share.mock.calls[0][0].title).toBe(
                'Invoice INV-26001 from Maya Studio',
            );
        });

        // Closing the sheet is the user changing their mind, not a failure.
        it('says nothing when the sheet is closed without sharing', async () => {
            const { writeText } = stubDevice({
                share: vi
                    .fn()
                    .mockRejectedValue(new DOMException('', 'AbortError')),
            });
            const wrapper = mountActions();

            await tap(wrapper);

            expect(status(wrapper)).toBe('');
            expect(writeText).not.toHaveBeenCalled();
            expect(linkButton(wrapper).text()).toBe('Share');
        });

        it('copies the link instead when the browser refuses the sheet', async () => {
            const { writeText } = stubDevice({
                share: vi
                    .fn()
                    .mockRejectedValue(new DOMException('', 'NotAllowedError')),
            });
            const wrapper = mountActions();

            await tap(wrapper);

            expect(writeText).toHaveBeenCalledWith(SHARE_URL);
            expect(status(wrapper)).toBe(
                'Link copied. It works until 13 Nov 2026.',
            );
        });

        it('says where to find the link when neither sharing nor copying works', async () => {
            stubDevice({
                share: vi
                    .fn()
                    .mockRejectedValue(new DOMException('', 'NotAllowedError')),
                writeText: vi.fn().mockRejectedValue(new Error('denied')),
            });
            const wrapper = mountActions();

            await tap(wrapper);

            expect(status(wrapper)).toBe(
                'Could not copy. Open View public and copy its address instead.',
            );
        });

        // The browser rejects a second share() while the sheet is open, and
        // that rejection would otherwise fall through to copying.
        it('ignores a second tap while the sheet is open', async () => {
            let finish: () => void = () => {};
            const share = vi.fn(
                () =>
                    new Promise<void>((resolve) => {
                        finish = resolve;
                    }),
            );
            const { writeText } = stubDevice({ share });
            const wrapper = mountActions();

            await linkButton(wrapper).trigger('click');
            await linkButton(wrapper).trigger('click');
            finish();
            await flushPromises();

            expect(share).toHaveBeenCalledOnce();
            expect(writeText).not.toHaveBeenCalled();
            expect(status(wrapper)).toBe(
                'Link shared. It works until 13 Nov 2026.',
            );
        });

        // Sharing hands the user to another app as it completes. A plain
        // timer would clear the message there, before they could read it.
        it('keeps the message until the sharer is back on the page', async () => {
            vi.useFakeTimers({ toFake: ['setTimeout', 'clearTimeout'] });
            stubDevice();
            const wrapper = mountActions();
            Object.defineProperty(document, 'visibilityState', {
                value: 'hidden',
                configurable: true,
            });

            await tap(wrapper);
            vi.advanceTimersByTime(60000);
            await nextTick();

            expect(status(wrapper)).toBe(
                'Link shared. It works until 13 Nov 2026.',
            );

            Object.defineProperty(document, 'visibilityState', {
                value: 'visible',
                configurable: true,
            });
            document.dispatchEvent(new Event('visibilitychange'));
            vi.advanceTimersByTime(3900);
            await nextTick();
            expect(status(wrapper)).not.toBe('');

            vi.advanceTimersByTime(200);
            await nextTick();
            expect(status(wrapper)).toBe('');
        });
    });

    describe('copying instead', () => {
        // Chrome on Windows and macOS has a share sheet too; at a desk the
        // clipboard is still the shorter way into an email or a web chat.
        it('keeps Copy link under a mouse, even where the browser can share', async () => {
            const { share, writeText } = stubDevice({ coarse: false });
            const wrapper = mountActions();

            expect(linkButton(wrapper).text()).toBe('Copy link');

            await tap(wrapper);

            expect(share).not.toHaveBeenCalled();
            expect(writeText).toHaveBeenCalledWith(SHARE_URL);
            expect(status(wrapper)).toBe(
                'Link copied. It works until 13 Nov 2026.',
            );
        });

        it('keeps Copy link on a phone whose browser has no share sheet', async () => {
            const { writeText } = stubDevice({ noShareSheet: true });
            const wrapper = mountActions();

            expect(linkButton(wrapper).text()).toBe('Copy link');

            await tap(wrapper);

            expect(writeText).toHaveBeenCalledWith(SHARE_URL);
        });

        it('keeps Copy link when the browser says it cannot share the link', async () => {
            const { share, writeText } = stubDevice({
                canShare: vi.fn().mockReturnValue(false),
            });
            const wrapper = mountActions();

            expect(linkButton(wrapper).text()).toBe('Copy link');

            await tap(wrapper);

            expect(share).not.toHaveBeenCalled();
            expect(writeText).toHaveBeenCalledWith(SHARE_URL);
        });

        it('offers Share when the browser confirms it can share the link', () => {
            const canShare = vi.fn().mockReturnValue(true);
            stubDevice({ canShare });
            const wrapper = mountActions();

            expect(linkButton(wrapper).text()).toBe('Share');
            expect(canShare).toHaveBeenCalledWith({ url: SHARE_URL });
        });
    });
});
