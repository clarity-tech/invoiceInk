<script setup lang="ts">
import {
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
    type HTMLAttributes,
} from 'vue';
import { controlClasses } from '../input';
import { cn } from '@/lib/utils';

const props = defineProps<{
    /**
     * Grow to fit the content instead of scrolling inside a fixed box. A line
     * item's description is often several sentences, and a one-line field
     * hides all but the first of them while it is being written.
     *
     * The grip stays: it is how a textarea says it is one, and someone editing
     * a long description may want more room than the text currently needs.
     * Dragging it hands the height over -- see `resizedByHand`.
     */
    autosize?: boolean;
    class?: HTMLAttributes['class'];
}>();

const model = defineModel<string | null>();
const el = ref<HTMLTextAreaElement | null>(null);

/**
 * Set once the grip has been dragged. From then on the height is the user's,
 * and typing no longer snaps it back to the content.
 */
const resizedByHand = ref(false);

function resize() {
    const node = el.value;

    if (!props.autosize || !node || resizedByHand.value) {
        return;
    }

    // Collapse first: scrollHeight only shrinks once the element is allowed to.
    node.style.height = 'auto';

    // scrollHeight excludes the border, but `height` includes it under
    // border-box -- without the difference the last line is clipped by 2px,
    // which `overflow-hidden` then hides rather than reveals.
    node.style.height = `${node.scrollHeight + node.offsetHeight - node.clientHeight}px`;
}

/**
 * The drag is spotted from where the pointer went down rather than from the
 * height changing afterwards: autosize changes the height constantly, and
 * telling its own change from the user's by comparison turned into a loop
 * that grew one box to 13558px.
 */
function onPointerDown(event: PointerEvent) {
    const node = el.value;

    if (!props.autosize || !node || resizedByHand.value) {
        return;
    }

    const box = node.getBoundingClientRect();
    const GRIP = 18;

    if (event.clientX > box.right - GRIP && event.clientY > box.bottom - GRIP) {
        resizedByHand.value = true;
    }
}

/**
 * A narrower window rewraps the text, which changes how tall it needs to be
 * without anything being typed. Watched on the window rather than on the
 * element: a ResizeObserver here sees the height this very function sets, and
 * on a narrow viewport -- where the change can take an ancestor's scrollbar
 * with it -- that became "ResizeObserver loop completed with undelivered
 * notifications" on every page load.
 */
function onWindowResize() {
    requestAnimationFrame(resize);
}

onMounted(() => {
    resize();
    window.addEventListener('resize', onWindowResize, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener('resize', onWindowResize));

// Covers a value set from outside -- a duplicated document seeding its lines,
// for one -- which no input event announces.
watch(model, () => nextTick(resize));
</script>

<template>
    <textarea
        ref="el"
        v-model="model"
        :class="
            cn(
                controlClasses,
                'resize-y',
                autosize && !resizedByHand && 'overflow-hidden',
                !autosize && 'min-h-20',
                props.class,
            )
        "
        @input="resize"
        @pointerdown="onPointerDown"
    />
</template>
