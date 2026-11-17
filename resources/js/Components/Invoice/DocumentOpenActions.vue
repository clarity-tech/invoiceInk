<script setup lang="ts">
import { Check, Download, ExternalLink, Link2, Share2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/composables/useFormatDate';
import { formatMoney } from '@/composables/useFormatMoney';
import { download } from '@/routes/invoices';
import type { Invoice, ShareLink } from '@/types';

/**
 * The two actions that open a document as it stands rather than change it.
 *
 * They live here because the detail page and the edit page both offer them,
 * and offering them separately is how they drifted: the same control was
 * "View public" on one page and "View Public" on the other, and "PDF" against
 * "Download PDF" -- one action announcing itself to a screen reader under two
 * different names depending on which page you reached it from. Only one of the
 * two told you anything while a ten-second render was running, and only one
 * carried rel="noopener".
 *
 * The public view needs a signed, expiring link, which only the server can
 * mint, so it arrives as `shareLink`. The PDF goes through the signed-in
 * download route instead, which needs no link to expire.
 */
const props = defineProps<{
    invoice: Invoice;
    shareLink: ShareLink;
}>();

const pdfUrl = computed(() => download.url(props.invoice.id));

/**
 * A PDF download replaces no page, so nothing tells us when it finished. The
 * busy state is released on a timer rather than left running for ever; a real
 * render takes about ten seconds, because the document is rendered twice.
 */
const downloadingPdf = ref(false);
let pdfTimer: ReturnType<typeof setTimeout> | null = null;

function startPdfDownload() {
    downloadingPdf.value = true;
    pdfTimer = setTimeout(() => {
        downloadingPdf.value = false;
    }, 15000);
}

/**
 * On a phone a link reaches a customer through WhatsApp, SMS or another chat,
 * and copying it meant leaving for that app and pasting it there. The system
 * share sheet puts those apps one tap away, so a touch-first device that has
 * one gets Share in place of Copy link.
 *
 * Desktop keeps Copy link even where the browser has a share sheet, as Chrome
 * on Windows and macOS does: there the link is going into an email or a web
 * chat that is already open, and the clipboard is the shorter way in.
 *
 * Decided once, as the component is set up, so the button's name and width
 * never change under the user's finger.
 */
function canOpenShareSheet(url: string): boolean {
    if (typeof navigator.share !== 'function') {
        return false;
    }

    // Older implementations have share() but no canShare(); only an explicit
    // "no" rules the sheet out.
    if (navigator.canShare?.({ url }) === false) {
        return false;
    }

    return (
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(pointer: coarse)').matches
    );
}

const opensShareSheet = canOpenShareSheet(props.shareLink.url);

/**
 * What lands in the chat with the link, so it has to stand on its own. Chat
 * apps generally keep only the text and the link, while Gmail makes the title
 * the subject. The title therefore reads like the subject of the email the
 * same document is sent with, and the text repeats it with what a customer
 * asks first: how much, and by when. "Valid until" for an estimate, as the
 * detail page labels the same date.
 *
 * "How much" is what is still owed. Once part of an invoice is paid, the
 * balance leads and the total follows for context, so the customer is not
 * asked for money they have already sent; once all of it is paid, nothing is
 * said to be due. An estimate has no payments, so it always gives its total.
 * The balance is worked out as the server's remaining_balance is, total less
 * amount_paid in integer cents: the edit page does not send remaining_balance,
 * and amount_paid already counts any TDS the customer withheld.
 *
 * The link goes only in `url`. Android joins text and url into one message,
 * so a link written into the text as well would arrive twice.
 */
const shareTitle = computed(() => {
    const kind = props.invoice.type === 'estimate' ? 'Estimate' : 'Invoice';
    const organization = props.invoice.organization;
    const sender = organization?.company_name || organization?.name;
    const subject = `${kind} ${props.invoice.invoice_number}`;

    return sender ? `${subject} from ${sender}` : subject;
});

const shareText = computed(() => {
    const { invoice } = props;
    const money = (cents: number) => formatMoney(cents, invoice.currency);
    const total = money(invoice.total);
    const dueOn = invoice.due_at ? formatDate(invoice.due_at) : null;

    if (invoice.type === 'estimate') {
        return dueOn
            ? `${shareTitle.value} for ${total}, valid until ${dueOn}.`
            : `${shareTitle.value} for ${total}.`;
    }

    const paid = invoice.amount_paid;

    if (paid > 0 && paid >= invoice.total) {
        return `${shareTitle.value} for ${total}, paid in full.`;
    }

    if (paid > 0) {
        const owed = `${money(invoice.total - paid)} of ${total} still due`;

        return dueOn
            ? `${shareTitle.value}: ${owed} by ${dueOn}.`
            : `${shareTitle.value}: ${owed}.`;
    }

    return dueOn
        ? `${shareTitle.value} for ${total}, due ${dueOn}.`
        : `${shareTitle.value} for ${total}.`;
});

/**
 * Sharing and copying are how a link reaches a customer outside an email, so
 * the message says how long it will last: the person sending it is the one
 * who can tell the customer. It is not in the shared text, which the customer
 * reads. Shown beside the button rather than as its label, so the label keeps
 * its width and nothing in the header moves.
 */
const statusMessage = ref('');
const succeeded = ref(false);
let messageTimer: ReturnType<typeof setTimeout> | null = null;

function announce(message: string, success: boolean) {
    statusMessage.value = message;
    succeeded.value = success;
    scheduleMessageClear();
}

/**
 * The message clears itself after four seconds, but the clock only runs while
 * the page is in view. Sharing sends the user into another app at the moment
 * the share completes, and a plain timer would run out there: they would come
 * back to no message, and no date to pass on.
 */
function scheduleMessageClear() {
    if (messageTimer) {
        clearTimeout(messageTimer);
        messageTimer = null;
    }

    if (document.visibilityState === 'hidden') {
        return;
    }

    messageTimer = setTimeout(() => {
        statusMessage.value = '';
        succeeded.value = false;
        messageTimer = null;
    }, 4000);
}

function onVisibilityChange() {
    if (statusMessage.value) {
        scheduleMessageClear();
    }
}

async function copyShareLink() {
    try {
        await navigator.clipboard.writeText(props.shareLink.url);
        announce(
            `Link copied. It works until ${formatDate(props.shareLink.expires_at)}.`,
            true,
        );
    } catch {
        announce(
            'Could not copy. Open View public and copy its address instead.',
            false,
        );
    }
}

/**
 * share() is called before anything is awaited: browsers open the sheet only
 * in direct response to a tap. A second tap while the sheet is open is
 * ignored rather than passed on, as the browser would reject it and that
 * rejection would read as a failure to share.
 *
 * Closing the sheet without choosing an app rejects with AbortError. That is
 * the user changing their mind, so nothing is said. Any other failure, such
 * as a browser that refuses the sheet, falls back to copying.
 */
let sharing = false;

async function openShareSheet() {
    if (sharing) {
        return;
    }
    sharing = true;

    try {
        await navigator.share({
            title: shareTitle.value,
            text: shareText.value,
            url: props.shareLink.url,
        });
        announce(
            `Link shared. It works until ${formatDate(props.shareLink.expires_at)}.`,
            true,
        );
    } catch (error) {
        if ((error as { name?: unknown } | null)?.name !== 'AbortError') {
            await copyShareLink();
        }
    } finally {
        sharing = false;
    }
}

onMounted(() => {
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onBeforeUnmount(() => {
    document.removeEventListener('visibilitychange', onVisibilityChange);
    if (pdfTimer) {
        clearTimeout(pdfTimer);
    }
    if (messageTimer) {
        clearTimeout(messageTimer);
    }
});
</script>

<template>
    <!--
        The message below hangs from the link button's right edge, except on a
        phone, where it hangs from this row's instead. At 375px the row wraps,
        Share lands mid-screen, and a message hung from it ran off the left
        edge -- the one place a phone shows when the link expires.
    -->
    <div class="relative flex flex-wrap items-center gap-2">
        <Button
            as="a"
            variant="outline"
            size="sm"
            :href="shareLink.url"
            target="_blank"
            rel="noopener"
        >
            <ExternalLink />
            View public
        </Button>
        <div class="sm:relative">
            <!--
                Share2 is Android's share glyph: most of the phones this is
                used on run Android.
            -->
            <Button
                v-if="opensShareSheet"
                variant="outline"
                size="sm"
                :title="`Shares a link that works until ${formatDate(shareLink.expires_at)}`"
                @click="openShareSheet"
            >
                <Check v-if="succeeded" />
                <Share2 v-else />
                Share
            </Button>
            <Button
                v-else
                variant="outline"
                size="sm"
                :title="`Copies a link that works until ${formatDate(shareLink.expires_at)}`"
                @click="copyShareLink"
            >
                <Check v-if="succeeded" />
                <Link2 v-else />
                Copy link
            </Button>
            <!--
                Always in the DOM, so a screen reader is already watching it
                when the message arrives.
            -->
            <p
                role="status"
                :class="
                    statusMessage
                        ? 'absolute top-full right-0 z-20 mt-2 w-max max-w-72 rounded-md border border-border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-md'
                        : 'sr-only'
                "
            >
                {{ statusMessage }}
            </p>
        </div>
        <Button
            as="a"
            variant="outline"
            size="sm"
            :href="pdfUrl"
            :aria-busy="downloadingPdf"
            @click="startPdfDownload"
        >
            <Download :class="downloadingPdf && 'animate-pulse'" />
            {{ downloadingPdf ? 'Generating…' : 'Download PDF' }}
        </Button>
    </div>
</template>
