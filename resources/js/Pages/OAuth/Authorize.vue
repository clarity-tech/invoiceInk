<script setup lang="ts">
import { AlertTriangle, Check, ShieldCheck, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps<{
    clientId: string;
    clientName: string;
    redirectHost: string;
    isLocalRedirect: boolean;
    email: string;
    organization: string | null;
    authToken: string;
    csrfToken: string;
}>();

// Both forms submit natively rather than through Inertia: Passport answers with
// a redirect to the client's own domain, which an XHR visit cannot follow.
const submitting = ref<'approve' | 'deny' | null>(null);

const endpoint = '/oauth/authorize';
const organizationName = props.organization ?? 'your organization';
</script>

<template>
    <GuestLayout title="Connect an AI assistant">
        <div class="py-2">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-soft/50"
                >
                    <ShieldCheck class="h-5 w-5 text-primary" />
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-foreground">
                        Connect {{ clientName }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        wants to read your InvoiceInk data
                    </p>
                </div>
            </div>

            <dl
                class="mt-5 space-y-2 rounded-lg border border-border bg-muted/50 p-4 text-sm"
            >
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Signed in as</dt>
                    <dd class="truncate font-medium text-foreground">
                        {{ email }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Organization</dt>
                    <dd class="truncate font-medium text-foreground">
                        {{ organizationName }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted-foreground">Returns you to</dt>
                    <dd
                        class="truncate font-mono text-xs font-medium text-foreground"
                    >
                        {{ redirectHost }}
                    </dd>
                </div>
            </dl>

            <ul class="mt-5 space-y-2 text-sm text-soft-foreground">
                <li class="flex gap-2">
                    <Check class="mt-0.5 h-4 w-4 shrink-0 text-success" />
                    <span>
                        Read invoices, estimates, customers and payment reports
                        for <strong>{{ organizationName }}</strong
                        >.
                    </span>
                </li>
                <li class="flex gap-2">
                    <X class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                    <span>
                        It cannot create, change, send or delete anything.
                    </span>
                </li>
            </ul>

            <div
                v-if="isLocalRedirect"
                class="mt-5 flex items-start gap-3 rounded-lg border border-warning/25 bg-warning-soft/50 p-3 text-sm text-warning-soft-foreground"
            >
                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                <p>
                    This app runs on your own computer, such as Claude Code.
                    Only approve if you started this connection yourself just
                    now.
                </p>
            </div>

            <p class="mt-5 text-xs text-muted-foreground">
                Only approve if you trust {{ redirectHost }}. You can disconnect
                it at any time from Settings → Connect AI tools.
            </p>

            <div class="mt-5 flex gap-3">
                <form
                    method="post"
                    :action="endpoint"
                    class="flex-1"
                    @submit="submitting = 'deny'"
                >
                    <input type="hidden" name="_token" :value="csrfToken" />
                    <input type="hidden" name="_method" value="DELETE" />
                    <input type="hidden" name="state" value="" />
                    <input type="hidden" name="client_id" :value="clientId" />
                    <input type="hidden" name="auth_token" :value="authToken" />
                    <Button
                        type="submit"
                        variant="outline"
                        class="w-full"
                        :disabled="submitting !== null"
                    >
                        Cancel
                    </Button>
                </form>

                <form
                    method="post"
                    :action="endpoint"
                    class="flex-1"
                    @submit="submitting = 'approve'"
                >
                    <input type="hidden" name="_token" :value="csrfToken" />
                    <input type="hidden" name="state" value="" />
                    <input type="hidden" name="client_id" :value="clientId" />
                    <input type="hidden" name="auth_token" :value="authToken" />
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="submitting !== null"
                    >
                        {{
                            submitting === 'approve' ? 'Connecting…' : 'Approve'
                        }}
                    </Button>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
