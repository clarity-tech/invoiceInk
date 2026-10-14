<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

const { isDark, setAppearance } = useAppearance();
</script>

<template>
    <!--
        Light or dark in one click, from the bar itself. It flips what the page
        shows now, so on a dark system the first click gives light. Following
        the system again is the rarer choice, and lives with the full System /
        Light / Dark toggle in the user menu.

        A toggle button: the label names the state and `aria-pressed` says
        whether it is on, so assistive technology hears "Dark theme, pressed".
    -->
    <Button
        type="button"
        variant="ghost"
        size="icon"
        class="text-muted-foreground [&_svg]:size-5"
        aria-label="Dark theme"
        :aria-pressed="isDark"
        :title="isDark ? 'Switch to light theme' : 'Switch to dark theme'"
        data-appearance-switch
        @click="setAppearance(isDark ? 'light' : 'dark')"
    >
        <Sun v-if="isDark" aria-hidden="true" />
        <Moon v-else aria-hidden="true" />
    </Button>
</template>
