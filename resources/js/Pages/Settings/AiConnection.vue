<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Check, Copy, Sparkles, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Connection {
    id: number;
    name: string;
    created_at: string | null;
    last_used_at: string | null;
}

const props = defineProps<{
    endpoint: string;
    enabled: boolean;
    organization: string | null;
    newToken: string | null;
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

// Shown only on the render immediately after creation — the plaintext value is
// never stored, so this is the one chance to copy it.
const token = computed(() => props.newToken ?? '');

const claudeDesktopConfig = computed(() =>
    JSON.stringify(
        {
            mcpServers: {
                invoicing: {
                    url: props.endpoint,
                    headers: {
                        Authorization: `Bearer ${token.value || '<your-token>'}`,
                    },
                },
            },
        },
        null,
        2,
    ),
);

const claudeCodeCommand = computed(
    () =>
        `claude mcp add --transport http invoicing ${props.endpoint} \\\n  --header "Authorization: Bearer ${token.value || '<your-token>'}"`,
);
</script>

<template>
    <AppLayout title="Connect AI tools">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Connect AI tools
            </h2>
        </template>

        <div class="max-w-3xl space-y-6 px-4 py-4 sm:px-0">
            <div
                class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50"
                    >
                        <Sparkles class="h-5 w-5 text-brand-600" />
                    </div>
                    <div class="text-sm text-gray-600">
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
                class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
            >
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                <p>
                    This feature is not switched on for this environment yet, so
                    connections created here will not work. Ask your
                    administrator to enable
                    <code class="font-mono">MCP_WEB_ENABLED</code>.
                </p>
            </div>

            <div
                v-if="token"
                class="rounded-lg border border-green-300 bg-green-50 p-6"
            >
                <h3 class="text-sm font-semibold text-green-900">
                    Copy your token now
                </h3>
                <p class="mt-1 text-sm text-green-800">
                    This is the only time it is shown. If you lose it, revoke
                    the connection and create another.
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <code
                        class="flex-1 overflow-x-auto rounded border border-green-300 bg-white px-3 py-2 font-mono text-xs text-gray-800"
                        >{{ token }}</code
                    >
                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1 rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700"
                        @click="copy(token, 'token')"
                    >
                        <Check v-if="copied === 'token'" class="h-4 w-4" />
                        <Copy v-else class="h-4 w-4" />
                        {{ copied === 'token' ? 'Copied' : 'Copy' }}
                    </button>
                </div>
            </div>

            <div
                class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h3 class="text-sm font-semibold text-gray-900">Connections</h3>

                <form
                    class="mt-4 flex items-end gap-3"
                    @submit.prevent="create"
                >
                    <div class="flex-1">
                        <label
                            for="name"
                            class="block text-sm font-medium text-gray-700"
                            >Name</label
                        >
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Claude on my laptop"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                    >
                        Create connection
                    </button>
                </form>

                <ul v-if="tokens.length" class="mt-6 divide-y divide-gray-200">
                    <li
                        v-for="connection in tokens"
                        :key="connection.id"
                        class="flex items-center justify-between py-3"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ connection.name }}
                            </p>
                            <p class="text-xs text-gray-500">
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
                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-sm text-red-600 hover:bg-red-50"
                            @click="revoke(connection.id)"
                        >
                            <Trash2 class="h-4 w-4" />
                            Revoke
                        </button>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-gray-500">
                    No connections yet.
                </p>
            </div>

            <div
                class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
            >
                <h3 class="text-sm font-semibold text-gray-900">Setup</h3>

                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-700">Endpoint</p>
                    <div class="mt-1 flex items-center gap-2">
                        <code
                            class="flex-1 overflow-x-auto rounded border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-xs"
                            >{{ endpoint }}</code
                        >
                        <button
                            type="button"
                            class="shrink-0 rounded-md border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50"
                            @click="copy(endpoint, 'endpoint')"
                        >
                            {{ copied === 'endpoint' ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-medium text-gray-700">
                        Claude Desktop
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        Settings → Connectors → Add custom connector, or paste
                        this into your config file.
                    </p>
                    <pre
                        class="mt-2 overflow-x-auto rounded border border-gray-200 bg-gray-50 p-3 font-mono text-xs"
                        >{{ claudeDesktopConfig }}</pre>
                    <button
                        type="button"
                        class="mt-2 rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50"
                        @click="copy(claudeDesktopConfig, 'desktop')"
                    >
                        {{ copied === 'desktop' ? 'Copied' : 'Copy config' }}
                    </button>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-medium text-gray-700">Claude Code</p>
                    <pre
                        class="mt-2 overflow-x-auto rounded border border-gray-200 bg-gray-50 p-3 font-mono text-xs"
                        >{{ claudeCodeCommand }}</pre>
                    <button
                        type="button"
                        class="mt-2 rounded-md border border-gray-300 px-3 py-1.5 text-sm hover:bg-gray-50"
                        @click="copy(claudeCodeCommand, 'code')"
                    >
                        {{ copied === 'code' ? 'Copied' : 'Copy command' }}
                    </button>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-medium text-gray-700">Other tools</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Any client that speaks MCP over HTTP works: point it at
                        the endpoint above and send your token as an
                        <code class="font-mono">Authorization: Bearer</code>
                        header.
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        <strong>ChatGPT is not supported yet.</strong> It only
                        connects to MCP servers that offer OAuth sign-in, and
                        cannot use a token like the one above.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
