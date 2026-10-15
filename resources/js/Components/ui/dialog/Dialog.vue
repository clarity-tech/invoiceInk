<script setup lang="ts">
import {
    nextTick,
    onBeforeUnmount,
    ref,
    watch,
    type HTMLAttributes,
} from 'vue';
import { cn } from '@/lib/utils';

/**
 * A modal panel over the page. Escape closes it, the page behind stops
 * scrolling, and focus moves into the panel and back to the trigger after.
 * A click on the backdrop does not close it: these dialogs hold a composed
 * email, and a stray click would throw it away.
 */
const props = withDefaults(
    defineProps<{
        open: boolean;
        size?: 'md' | 'lg' | 'xl';
        class?: HTMLAttributes['class'];
    }>(),
    { size: 'lg', class: undefined },
);

const emit = defineEmits<{ close: [] }>();

defineOptions({ inheritAttrs: false });

const panel = ref<HTMLElement | null>(null);
let returnFocusTo: HTMLElement | null = null;

const sizes = {
    md: 'sm:max-w-lg',
    lg: 'sm:max-w-2xl',
    xl: 'sm:max-w-3xl',
};

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        emit('close');
    }
}

function release() {
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
    returnFocusTo?.focus?.();
    returnFocusTo = null;
}

watch(
    () => props.open,
    async (open, wasOpen) => {
        if (open) {
            returnFocusTo = document.activeElement as HTMLElement | null;
            document.addEventListener('keydown', onKeydown);
            document.body.style.overflow = 'hidden';
            await nextTick();
            panel.value?.focus();
        } else if (wasOpen) {
            release();
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (props.open) {
        release();
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0"
        leave-active-class="transition duration-100 ease-in"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            data-slot="dialog-overlay"
            class="fixed inset-0 z-50 flex items-end justify-center bg-overlay backdrop-blur-[2px] sm:items-center sm:p-4"
        >
            <div
                ref="panel"
                v-bind="$attrs"
                role="dialog"
                aria-modal="true"
                tabindex="-1"
                data-slot="dialog"
                :class="
                    cn(
                        'flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-xl border border-border bg-card text-card-foreground shadow-2xl outline-none sm:rounded-xl',
                        sizes[size],
                        props.class,
                    )
                "
            >
                <slot />
            </div>
        </div>
    </Transition>
</template>
