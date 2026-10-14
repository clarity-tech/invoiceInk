<script setup lang="ts">
import { Check, Download, ExternalLink, Link2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import { Button } from '@/Components/ui/button';
import { formatDate } from '@/composables/useFormatDate';
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
 * Copying is how a link reaches a customer outside an email, so the message
 * says how long it will last: the person pasting it into a chat is the one who
 * can tell the customer. Shown beside the button rather than as its label, so
 * the label keeps its width and nothing in the header moves.
 */
const copyMessage = ref('');
const copied = ref(false);
let copyTimer: ReturnType<typeof setTimeout> | null = null;

async function copyShareLink() {
    try {
        await navigator.clipboard.writeText(props.shareLink.url);
        copied.value = true;
        copyMessage.value = `Link copied. It works until ${formatDate(props.shareLink.expires_at)}.`;
    } catch {
        copied.value = false;
        copyMessage.value =
            'Could not copy. Open View public and copy its address instead.';
    }

    if (copyTimer) {
        clearTimeout(copyTimer);
    }
    copyTimer = setTimeout(() => {
        copyMessage.value = '';
        copied.value = false;
    }, 4000);
}

onBeforeUnmount(() => {
    if (pdfTimer) {
        clearTimeout(pdfTimer);
    }
    if (copyTimer) {
        clearTimeout(copyTimer);
    }
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
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
        <div class="relative">
            <Button
                variant="outline"
                size="sm"
                :title="`Copies a link that works until ${formatDate(shareLink.expires_at)}`"
                @click="copyShareLink"
            >
                <Check v-if="copied" />
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
                    copyMessage
                        ? 'absolute top-full right-0 z-20 mt-2 w-max max-w-72 rounded-md border border-border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-md'
                        : 'sr-only'
                "
            >
                {{ copyMessage }}
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
