<script setup lang="ts">
import { Download, ExternalLink } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import { Button } from '@/Components/ui/button';
import type { Invoice } from '@/types';

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
 */
const props = defineProps<{
    invoice: Invoice;
}>();

const segment = computed(() =>
    props.invoice.type === 'invoice' ? 'invoices' : 'estimates',
);
const publicUrl = computed(
    () => `/${segment.value}/view/${props.invoice.ulid}`,
);
const pdfUrl = computed(() => `/${segment.value}/${props.invoice.ulid}/pdf`);

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

onBeforeUnmount(() => {
    if (pdfTimer) {
        clearTimeout(pdfTimer);
    }
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <Button
            as="a"
            variant="outline"
            size="sm"
            :href="publicUrl"
            target="_blank"
            rel="noopener"
        >
            <ExternalLink />
            View public
        </Button>
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
