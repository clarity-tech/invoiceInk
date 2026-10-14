<script setup lang="ts">
import { computed, useId, type HTMLAttributes } from 'vue';
import { Label } from '../label';
import { cn } from '@/lib/utils';

/**
 * Label, control, hint and error as one unit.
 *
 * Every field in the invoice form used to repeat this markup by hand, which is
 * how the spacing, the label weight and the error colour drifted apart between
 * sections. It also wires `for`/`id` and `aria-describedby` automatically, so a
 * field cannot end up with a label pointing at nothing.
 *
 * The control receives the generated id through the slot rather than being
 * guessed at, because the control is whatever the caller puts there.
 */
const props = defineProps<{
    label?: string;
    required?: boolean;
    /**
     * Keeps the label for assistive technology but takes it off the screen,
     * for the few places where what the control is is already obvious from
     * what it sits beside.
     */
    hideLabel?: boolean;
    hint?: string;
    /**
     * A caution about a choice that is still valid -- the document will save,
     * but something about it will not be what the user expects. Distinct from
     * `error`, which means the server refused it.
     */
    warning?: string;
    error?: string;
    class?: HTMLAttributes['class'];
}>();

const id = useId();

/** Only ever one element describes the field: error, then warning, then hint. */
const describedBy = computed(() =>
    props.error || props.warning || props.hint
        ? `${id}-description`
        : undefined,
);
</script>

<template>
    <div :class="cn('space-y-1.5', props.class)">
        <Label
            v-if="label"
            :for="id"
            :required="required"
            :class="hideLabel && 'sr-only'"
            >{{ label }}</Label
        >

        <slot :id="id" :described-by="describedBy" :invalid="Boolean(error)" />

        <p
            v-if="error"
            :id="`${id}-description`"
            class="text-xs text-destructive"
        >
            {{ error }}
        </p>
        <p
            v-else-if="warning"
            :id="`${id}-description`"
            class="text-xs text-warning"
        >
            {{ warning }}
        </p>
        <p
            v-else-if="hint"
            :id="`${id}-description`"
            class="text-xs text-muted-foreground"
        >
            {{ hint }}
        </p>
    </div>
</template>
