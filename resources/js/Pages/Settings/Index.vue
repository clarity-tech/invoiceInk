<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { Mail, Sparkles, WandSparkles } from 'lucide-vue-next';
import { update as updateAi } from '@/routes/settings/ai';

const props = defineProps<{
    ai: { enabled: boolean; configured: boolean };
}>();

function toggleAi(): void {
    const enabled = !props.ai.enabled;

    router
        .optimistic((current: any) => ({ ai: { ...current.ai, enabled } }))
        .put(updateAi.url(), { enabled }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Settings">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-foreground">
                Settings
            </h2>
        </template>

        <div class="px-4 py-4 sm:px-0">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    href="/email-templates"
                    class="flex items-start gap-4 rounded-lg border border-border bg-card p-6 shadow-sm transition hover:border-primary/40 hover:shadow-md"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-soft/50"
                    >
                        <Mail class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-foreground">
                            Email Templates
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Customize emails sent with invoices and estimates
                        </p>
                    </div>
                </Link>

                <div
                    class="flex items-start gap-4 rounded-lg border border-border bg-card p-6 shadow-sm"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-soft/50"
                    >
                        <WandSparkles class="h-5 w-5 text-primary" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <h3
                                id="ai-features-title"
                                class="text-sm font-semibold text-foreground"
                            >
                                AI features
                            </h3>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="ai.enabled"
                                aria-labelledby="ai-features-title"
                                :disabled="!ai.configured"
                                class="group inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent bg-input transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-card focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 aria-checked:bg-primary"
                                @click="toggleAi"
                            >
                                <span
                                    class="block size-5 rounded-full bg-background shadow-sm transition-transform group-aria-checked:translate-x-5"
                                />
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Draft invoices and estimates from a message or file,
                            write emails and suggest tax codes. What you use it
                            with is sent to an AI service; nothing is saved or
                            sent until someone reviews it.
                        </p>
                        <p
                            v-if="!ai.configured"
                            class="mt-2 text-sm text-warning"
                        >
                            AI is not set up on this server yet.
                        </p>
                    </div>
                </div>

                <Link
                    href="/settings/ai-connection"
                    class="flex items-start gap-4 rounded-lg border border-border bg-card p-6 shadow-sm transition hover:border-primary/40 hover:shadow-md"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-soft/50"
                    >
                        <Sparkles class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-foreground">
                            Connect AI tools
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Ask Claude about your invoices, customers and
                            outstanding payments
                        </p>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
