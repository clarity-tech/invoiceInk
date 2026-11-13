<script setup lang="ts">
import { Download, Share, X } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
import { useInstallPrompt } from '@/composables/useInstallPrompt';

const { mode, install, dismiss } = useInstallPrompt();
</script>

<template>
    <!--
        Offered only on phones and tablets that are not already running the
        installed app (see useInstallPrompt). Chromium gets a real install
        button; iOS Safari has no install API, so it gets the two taps instead.
    -->
    <aside
        v-if="mode"
        role="region"
        aria-label="Install app"
        data-testid="install-banner"
        class="fixed inset-x-0 bottom-0 z-40 p-3 sm:left-auto sm:max-w-sm"
    >
        <div
            class="flex items-start gap-3 rounded-lg border border-border bg-card p-4 shadow-lg"
        >
            <div
                class="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground"
            >
                <Download class="size-5" aria-hidden="true" />
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-foreground">
                    Install InvoiceInk
                </p>

                <template v-if="mode === 'prompt'">
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        Open it from your home screen, like any other app.
                    </p>
                    <div class="mt-3 flex gap-2">
                        <Button size="sm" @click="install">Install</Button>
                        <Button size="sm" variant="ghost" @click="dismiss">
                            Not now
                        </Button>
                    </div>
                </template>
                <p v-else class="mt-0.5 text-sm text-muted-foreground">
                    Tap
                    <Share
                        class="inline size-4 align-text-bottom"
                        aria-label="Share"
                    />
                    then <strong>Add to Home Screen</strong>.
                </p>
            </div>

            <Button
                v-if="mode === 'ios'"
                size="icon"
                variant="ghost"
                class="-mt-1 -mr-1 size-8"
                aria-label="Dismiss"
                @click="dismiss"
            >
                <X aria-hidden="true" />
            </Button>
        </div>
    </aside>
</template>
