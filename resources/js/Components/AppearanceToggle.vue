<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useId } from 'vue';
import { useAppearance, type Appearance } from '@/composables/useAppearance';

const { appearance, setAppearance } = useAppearance();

// The toggle appears twice in the navigation (desktop menu and mobile panel),
// so each instance needs its own radio group name.
const name = `appearance-${useId()}`;

const options: { value: Appearance; label: string; icon: typeof Sun }[] = [
    { value: 'system', label: 'System', icon: Monitor },
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
];
</script>

<template>
    <!--
        Native radios rather than buttons: one choice out of three is exactly
        what a radio group is, and it brings arrow-key movement and the checked
        state to assistive technology without any script. The group is labelled
        by the visible heading rather than a <legend>, which browsers lay out
        in the fieldset border and will not space like the menu's other headings.
    -->
    <div
        role="radiogroup"
        :aria-labelledby="`${name}-label`"
        data-appearance-toggle
    >
        <p
            :id="`${name}-label`"
            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
        >
            Theme
        </p>
        <div class="grid grid-cols-3 gap-0.5 rounded-lg bg-secondary p-0.5">
            <label
                v-for="option in options"
                :key="option.value"
                class="flex cursor-pointer items-center justify-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground has-checked:bg-card has-checked:text-foreground has-checked:shadow-sm has-focus-visible:ring-2 has-focus-visible:ring-ring"
                :data-appearance-option="option.value"
            >
                <input
                    type="radio"
                    class="sr-only"
                    :name="name"
                    :value="option.value"
                    :checked="appearance === option.value"
                    @change="setAppearance(option.value)"
                />
                <component
                    :is="option.icon"
                    class="size-3.5 shrink-0"
                    aria-hidden="true"
                />
                {{ option.label }}
            </label>
        </div>
    </div>
</template>
