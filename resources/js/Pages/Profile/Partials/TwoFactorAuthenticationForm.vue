<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import {
    enable,
    disable,
    confirm,
    qrCode,
    secretKey,
    recoveryCodes,
    regenerateRecoveryCodes,
} from '@/routes/two-factor';

const props = defineProps<{
    user: {
        two_factor_secret: string | null;
        two_factor_confirmed_at: string | null;
    };
    confirmsTwoFactorAuthentication: boolean;
}>();

const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);
const qrCodeSvg = ref('');
const setupKey = ref('');
const recoveryCodesData = ref<string[]>([]);
const showingRecoveryCodes = ref(false);
const showingQrCode = ref(false);
const showingConfirmation = ref(false);
const confirmationCode = ref('');
const confirmationError = ref('');
const showPasswordConfirm = ref(false);
const passwordForConfirm = ref('');
const pendingAction = ref<(() => void) | null>(null);

const twoFactorEnabled = computed(() => {
    return (
        !!props.user.two_factor_secret && !!props.user.two_factor_confirmed_at
    );
});

function confirmPassword(action: () => void): void {
    router.post(
        '/user/confirm-password',
        { password: passwordForConfirm.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showPasswordConfirm.value = false;
                passwordForConfirm.value = '';
                action();
            },
            onError: () => {},
        },
    );
}

function enableTwoFactorAuthentication(): void {
    enabling.value = true;

    router.post(
        enable.url(),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showingQrCode.value = true;

                return Promise.all([fetchQrCode(), fetchSetupKey()]).then(
                    () => {
                        if (props.confirmsTwoFactorAuthentication) {
                            showingConfirmation.value = true;
                        } else {
                            fetchRecoveryCodes();
                            showingRecoveryCodes.value = true;
                        }

                        enabling.value = false;
                    },
                );
            },
            onFinish: () => {
                enabling.value = false;
            },
        },
    );
}

function confirmTwoFactorAuthentication(): void {
    confirming.value = true;
    confirmationError.value = '';

    router.post(
        confirm.url(),
        { code: confirmationCode.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                confirming.value = false;
                showingQrCode.value = false;
                showingConfirmation.value = false;
                showingRecoveryCodes.value = true;
                fetchRecoveryCodes();
            },
            onError: (errors) => {
                confirming.value = false;
                confirmationError.value = errors.code || 'Invalid code.';
            },
        },
    );
}

function showRecoveryCodesAction(): void {
    fetchRecoveryCodes();
    showingRecoveryCodes.value = true;
}

function regenerateRecoveryCodesAction(): void {
    router.post(
        regenerateRecoveryCodes.url(),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                fetchRecoveryCodes();
                showingRecoveryCodes.value = true;
            },
        },
    );
}

function disableTwoFactorAuthentication(): void {
    disabling.value = true;

    router.delete(disable.url(), {
        preserveScroll: true,
        onSuccess: () => {
            disabling.value = false;
            showingQrCode.value = false;
            showingConfirmation.value = false;
            showingRecoveryCodes.value = false;
        },
        onFinish: () => {
            disabling.value = false;
        },
    });
}

async function fetchQrCode(): Promise<void> {
    const response = await fetch(qrCode.url(), {
        headers: { Accept: 'application/json' },
    });
    const data = await response.json();
    qrCodeSvg.value = data.svg;
}

async function fetchSetupKey(): Promise<void> {
    const response = await fetch(secretKey.url(), {
        headers: { Accept: 'application/json' },
    });
    const data = await response.json();
    setupKey.value = data.secretKey;
}

async function fetchRecoveryCodes(): Promise<void> {
    const response = await fetch(recoveryCodes.url(), {
        headers: { Accept: 'application/json' },
    });
    recoveryCodesData.value = await response.json();
}
</script>

<template>
    <section class="rounded-xl border border-border bg-card p-6">
        <div class="mb-4 flex items-start justify-between">
            <div>
                <h2
                    class="mb-1 text-sm font-semibold tracking-wider text-faint-foreground uppercase"
                >
                    Two-Factor Authentication
                </h2>
                <p class="text-sm text-muted-foreground">
                    Add additional security to your account using two-factor
                    authentication.
                </p>
            </div>
            <span
                v-if="twoFactorEnabled"
                class="inline-flex items-center rounded-full bg-success-soft px-2.5 py-0.5 text-xs font-medium text-success-soft-foreground"
            >
                Enabled
            </span>
            <span
                v-else
                class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-soft-foreground"
            >
                Disabled
            </span>
        </div>

        <div class="text-sm text-soft-foreground">
            <template v-if="twoFactorEnabled">
                <template v-if="showingConfirmation">
                    <p class="font-medium text-foreground">
                        Finish enabling two-factor authentication.
                    </p>
                </template>
                <template v-else>
                    <p>You have enabled two-factor authentication.</p>
                </template>
            </template>
            <template v-else>
                <p>
                    When two-factor authentication is enabled, you will be
                    prompted for a secure, random token during authentication.
                    You may retrieve this token from your phone's Google
                    Authenticator application.
                </p>
            </template>
        </div>

        <div v-if="twoFactorEnabled || showingQrCode" class="mt-5">
            <div v-if="showingQrCode">
                <p class="mb-3 text-sm font-medium text-soft-foreground">
                    <template v-if="showingConfirmation">
                        Scan the QR code using your authenticator app or enter
                        the setup key, then provide the generated OTP code
                        below.
                    </template>
                    <template v-else>
                        Scan the QR code using your authenticator app or enter
                        the setup key.
                    </template>
                </p>

                <div
                    class="inline-block rounded-lg border border-border bg-card p-2"
                    v-html="qrCodeSvg"
                />

                <div v-if="setupKey" class="mt-3 text-sm text-soft-foreground">
                    <p>
                        Setup Key:
                        <span
                            class="font-mono font-semibold text-foreground"
                            v-text="setupKey"
                        />
                    </p>
                </div>

                <div v-if="showingConfirmation" class="mt-4">
                    <label
                        for="code"
                        class="block text-sm font-medium text-soft-foreground"
                    >
                        Authentication Code
                    </label>
                    <input
                        id="code"
                        v-model="confirmationCode"
                        type="text"
                        inputmode="numeric"
                        autofocus
                        autocomplete="one-time-code"
                        class="mt-1 block w-48 rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        @keydown.enter="confirmTwoFactorAuthentication"
                    />
                    <div
                        v-if="confirmationError"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ confirmationError }}
                    </div>
                </div>
            </div>

            <div
                v-if="showingRecoveryCodes && recoveryCodesData.length > 0"
                class="mt-5"
            >
                <p class="mb-3 text-sm font-medium text-soft-foreground">
                    Store these recovery codes in a secure password manager.
                    They can be used to recover access to your account if your
                    two-factor authentication device is lost.
                </p>
                <div
                    class="grid max-w-sm gap-1 rounded-lg border border-border bg-muted px-4 py-3 font-mono text-sm text-soft-foreground"
                >
                    <div v-for="code in recoveryCodesData" :key="code">
                        {{ code }}
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            <template v-if="!twoFactorEnabled && !showingQrCode">
                <button
                    type="button"
                    class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-60"
                    :disabled="enabling"
                    @click="enableTwoFactorAuthentication"
                >
                    Enable
                </button>
            </template>

            <template v-else>
                <template v-if="showingConfirmation">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-60"
                        :disabled="confirming"
                        @click="confirmTwoFactorAuthentication"
                    >
                        Confirm
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-semibold text-soft-foreground shadow-sm transition hover:bg-muted disabled:opacity-60"
                        :disabled="disabling"
                        @click="disableTwoFactorAuthentication"
                    >
                        Cancel
                    </button>
                </template>

                <template v-else-if="showingRecoveryCodes">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-semibold text-soft-foreground shadow-sm transition hover:bg-muted"
                        @click="regenerateRecoveryCodesAction"
                    >
                        Regenerate Recovery Codes
                    </button>
                </template>

                <template v-else>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-semibold text-soft-foreground shadow-sm transition hover:bg-muted"
                        @click="showRecoveryCodesAction"
                    >
                        Show Recovery Codes
                    </button>
                </template>

                <button
                    v-if="!showingConfirmation"
                    type="button"
                    class="inline-flex items-center rounded-lg bg-destructive px-4 py-2 text-sm font-semibold text-destructive-foreground shadow-sm transition hover:bg-destructive/90 disabled:opacity-60"
                    :disabled="disabling"
                    @click="disableTwoFactorAuthentication"
                >
                    Disable
                </button>
            </template>
        </div>
    </section>

    <!-- Password Confirmation Modal -->
    <ConfirmationModal
        :show="showPasswordConfirm"
        title="Confirm Password"
        message="For your security, please confirm your password to continue."
        confirm-label="Confirm"
        @confirm="
            () => {
                if (pendingAction) confirmPassword(pendingAction);
            }
        "
        @cancel="showPasswordConfirm = false"
    />
</template>
