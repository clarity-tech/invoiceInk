<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Check,
    Copy,
    Link2Off,
    Sparkles,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Connection {
    id: number;
    name: string;
    created_at: string | null;
    last_used_at: string | null;
}

interface SignedInApp {
    id: string;
    name: string;
    connected_at: string | null;
}

const props = defineProps<{
    endpoint: string;
    enabled: boolean;
    organization: string | null;
    newToken: string | null;
    apps: SignedInApp[];
    tokens: Connection[];
}>();

const form = useForm({ name: '' });
const copied = ref<string | null>(null);

function copy(value: string, key: string): void {
    navigator.clipboard.writeText(value);
    copied.value = key;
    setTimeout(() => (copied.value = null), 2000);
}

function create(): void {
    form.post('/settings/ai-connection', {
        preserveScroll: true,
        onSuccess: () => form.reset('name'),
    });
}

function revoke(id: number): void {
    router.delete(`/settings/ai-connection/${id}`, { preserveScroll: true });
}

function disconnect(id: string): void {
    router.delete(`/settings/ai-connection/apps/${id}`, {
        preserveScroll: true,
    });
}

// Shown only on the render immediately after creation — the plaintext value is
// never stored, so this is the one chance to copy it.
const token = computed(() => props.newToken ?? '');

const claudeCodeCommand = computed(
    () => `claude mcp add --transport http invoicing ${props.endpoint}`,
);

const claudeCodeTokenCommand = computed(
    () =>
        `claude mcp add --transport http invoicing ${props.endpoint} \\\n  --header "Authorization: Bearer ${token.value || '<your-token>'}"`,
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
                        <strong>Authenticate</strong>. If sign-in does not
                        complete, create an API token below and add the server
                        with it instead.
                    </p>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-medium text-soft-foreground">
                        Other tools
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Any MCP client that supports OAuth sign-in connects with
                        the server URL alone. For one that can only send a
                        header, create an API token below.
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

            <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-foreground">
                    API tokens
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    For clients that cannot sign in, such as a script. Send the
                    token as an
                    <code class="font-mono">Authorization: Bearer</code>
                    header.
                </p>

                <div
                    v-if="token"
                    class="mt-4 rounded-lg border border-success/40 bg-success-soft/50 p-4"
                >
                    <p
                        class="text-sm font-semibold text-success-soft-foreground"
                    >
                        Copy your token now
                    </p>
                    <p class="mt-1 text-sm text-success-soft-foreground">
                        This is the only time it is shown. If you lose it,
                        revoke it and create another.
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <code
                            class="flex-1 overflow-x-auto rounded border border-success/40 bg-card px-3 py-2 font-mono text-xs text-foreground"
                            >{{ token }}</code
                        >
                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center gap-1 rounded-md bg-success px-3 py-2 text-sm font-medium text-success-foreground hover:bg-success/90"
                            @click="copy(token, 'token')"
                        >
                            <Check v-if="copied === 'token'" class="h-4 w-4" />
                            <Copy v-else class="h-4 w-4" />
                            {{ copied === 'token' ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                    <p class="mt-3 text-sm text-success-soft-foreground">
                        For example, in Claude Code:
                    </p>
                    <pre
                        class="mt-1 overflow-x-auto rounded border border-success/40 bg-card p-3 font-mono text-xs text-foreground"
                        >{{ claudeCodeTokenCommand }}</pre>
                </div>

                <form
                    class="mt-4 flex items-end gap-3"
                    @submit.prevent="create"
                >
                    <div class="flex-1">
                        <label
                            for="name"
                            class="block text-sm font-medium text-soft-foreground"
                            >Name</label
                        >
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Monthly report script"
                            class="mt-1 block w-full rounded-md border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        Create token
                    </button>
                </form>

                <ul v-if="tokens.length" class="mt-6 divide-y divide-border">
                    <li
                        v-for="connection in tokens"
                        :key="connection.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <p class="text-sm font-medium text-foreground">
                                {{ connection.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Created {{ connection.created_at }} ·
                                {{
                                    connection.last_used_at
                                        ? `last used ${connection.last_used_at}`
                                        : 'never used'
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-destructive hover:bg-destructive-soft/50"
                            @click="revoke(connection.id)"
                        >
                            <Trash2 class="h-4 w-4" />
                            Revoke
                        </button>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-muted-foreground">
                    No API tokens yet.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
