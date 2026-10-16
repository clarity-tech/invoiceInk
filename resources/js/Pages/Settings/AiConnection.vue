<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, Link2Off, Sparkles } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface SignedInApp {
    id: string;
    name: string;
    connected_at: string | null;
}

const props = defineProps<{
    endpoint: string;
    enabled: boolean;
    organization: string | null;
    apps: SignedInApp[];
}>();

const copied = ref<string | null>(null);

function copy(value: string, key: string): void {
    navigator.clipboard.writeText(value);
    copied.value = key;
    setTimeout(() => (copied.value = null), 2000);
}

function disconnect(id: string): void {
    router.delete(`/settings/ai-connection/apps/${id}`, {
        preserveScroll: true,
    });
}

const claudeCodeCommand = computed(
    () => `claude mcp add --transport http invoicing ${props.endpoint}`,
);
</script>

<template>
    <AppLayout title="Connect AI tools">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-foreground">
                Connect AI tools
            </h2>
        </template>

        <div class="max-w-3xl space-y-6 px-4 py-4 sm:px-0">
            <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-soft/50"
                    >
                        <Sparkles class="h-5 w-5 text-primary" />
                    </div>
                    <div class="text-sm text-soft-foreground">
                        <p>
                            Let an AI assistant read your invoicing data, so you
                            can ask things like
                            <em>"what's overdue by more than 30 days?"</em> or
                            <em>"how much did we collect last month?"</em>
                        </p>
                        <p class="mt-2">
                            Access is
                            <strong>read-only</strong> — a connected assistant
                            can never create, change, send or delete anything —
                            and is limited to
                            <strong>{{
                                organization ?? 'your organization'
                            }}</strong
                            >.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="!enabled"
                class="flex items-start gap-3 rounded-lg border border-warning/25 bg-warning-soft/50 p-4 text-sm text-warning-soft-foreground"
            >
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                <p>
                    This feature is not switched on for this environment yet, so
                    connections made here will not work. Ask your administrator
                    to enable
                    <code class="font-mono">MCP_WEB_ENABLED</code>.
                </p>
            </div>

            <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-foreground">
                    Connect Claude
                </h3>

                <div class="mt-4">
                    <p class="text-sm font-medium text-soft-foreground">
                        Server URL
                    </p>
                    <div class="mt-1 flex items-center gap-2">
                        <code
                            class="flex-1 overflow-x-auto rounded border border-border bg-muted px-3 py-2 font-mono text-xs"
                            >{{ endpoint }}</code
                        >
                        <button
                            type="button"
                            class="shrink-0 rounded-md border border-input px-3 py-2 text-sm hover:bg-muted"
                            @click="copy(endpoint, 'endpoint')"
                        >
                            {{ copied === 'endpoint' ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-medium text-soft-foreground">
                        Claude on the web, desktop and mobile
                    </p>
                    <ol
                        class="mt-2 list-decimal space-y-1 pl-5 text-sm text-muted-foreground"
                    >
                        <li>
                            In Claude, open
                            <strong>Customize → Connectors</strong> and choose
                            <strong>Add custom connector</strong>.
                        </li>
                        <li>
                            Paste the server URL above and add it. Leave the
                            OAuth client fields empty.
                        </li>
                        <li>
                            Click <strong>Connect</strong>, sign in to
                            InvoiceInk if asked, and approve.
                        </li>
                    </ol>
                    <p class="mt-2 text-sm text-muted-foreground">
                        On a Team or Enterprise plan an Owner adds the connector
                        once under
                        <strong>Organization settings → Connectors</strong>;
                        each person then clicks <strong>Connect</strong>.
                    </p>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-medium text-soft-foreground">
                        Claude Code
                    </p>
                    <pre
                        class="mt-2 overflow-x-auto rounded border border-border bg-muted p-3 font-mono text-xs"
                        >{{ claudeCodeCommand }}</pre>
                    <button
                        type="button"
                        class="mt-2 rounded-md border border-input px-3 py-1.5 text-sm hover:bg-muted"
                        @click="copy(claudeCodeCommand, 'code')"
                    >
                        {{ copied === 'code' ? 'Copied' : 'Copy command' }}
                    </button>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Then run <code class="font-mono">/mcp</code> in Claude
                        Code, pick <strong>invoicing</strong> and choose
                        <strong>Authenticate</strong>.
                    </p>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-medium text-soft-foreground">
                        Other tools
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Any MCP client that supports OAuth sign-in connects with
                        the server URL alone.
                    </p>
                </div>
            </div>

            <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-foreground">
                    Connected apps
                </h3>

                <ul v-if="apps.length" class="mt-4 divide-y divide-border">
                    <li
                        v-for="app in apps"
                        :key="app.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <p class="text-sm font-medium text-foreground">
                                {{ app.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Connected {{ app.connected_at }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-destructive hover:bg-destructive-soft/50"
                            @click="disconnect(app.id)"
                        >
                            <Link2Off class="h-4 w-4" />
                            Disconnect
                        </button>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    No apps connected yet. Apps you approve from Claude appear
                    here.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
