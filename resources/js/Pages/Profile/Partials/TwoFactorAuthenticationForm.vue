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
        {
            password: passwordForConfirm.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showPasswordConfirm.value = false;
                passwordForConfirm.value = '';
                action();
            },
            onError: () => {
                // If password confirmation succeeds silently (already confirmed), just proceed
            },
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
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Two Factor Authentication
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Add additional security to your account using two factor
                    authentication.
                </p>
            </div>
        </div>

        <div class="mt-5 md:col-span-2 md:mt-0">
            <div class="overflow-hidden bg-white shadow sm:rounded-md">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">
                        <template v-if="twoFactorEnabled">
                            <template v-if="showingConfirmation">
                                Finish enabling two factor authentication.
                            </template>
                            <template v-else>
                                You have enabled two factor authentication.
                            </template>
                        </template>
                        <template v-else>
                            You have not enabled two factor authentication.
                        </template>
                    </h3>

                    <div class="mt-3 max-w-xl text-sm text-gray-600">
                        <p>
                            When two factor authentication is enabled, you will
                            be prompted for a secure, random token during
                            authentication. You may retrieve this token from
                            your phone's Google Authenticator application.
                        </p>
                    </div>

                    <div v-if="twoFactorEnabled || showingQrCode">
                        <div v-if="showingQrCode">
                            <div class="mt-4 max-w-xl text-sm text-gray-600">
                                <p class="font-semibold">
                                    <template v-if="showingConfirmation">
                                        To finish enabling two factor
                                        authentication, scan the following QR
                                        code using your phone's authenticator
                                        application or enter the setup key and
                                        provide the generated OTP code.
                                    </template>
                                    <template v-else>
                                        Two factor authentication is now
                                        enabled. Scan the following QR code
                                        using your phone's authenticator
                                        application or enter the setup key.
                                    </template>
                                </p>
                            </div>

                            <div
                                class="mt-4 inline-block bg-white p-2"
                                v-html="qrCodeSvg"
                            />

                            <div
                                v-if="setupKey"
                                class="mt-4 max-w-xl text-sm text-gray-600"
                            >
                                <p class="font-semibold">
                                    Setup Key: <span v-text="setupKey" />
                                </p>
                            </div>

                            <div v-if="showingConfirmation" class="mt-4">
                                <label
                                    for="code"
                                    class="block text-sm font-medium text-gray-700"
                                    >Code</label
                                >
                                <input
                                    id="code"
                                    v-model="confirmationCode"
                                    type="text"
                                    inputmode="numeric"
                                    autofocus
                                    autocomplete="one-time-code"
                                    class="mt-1 block w-1/2 rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                    @keydown.enter="
                                        confirmTwoFactorAuthentication
                                    "
                                />
                                <div
                                    v-if="confirmationError"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ confirmationError }}
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                showingRecoveryCodes &&
                                recoveryCodesData.length > 0
                            "
                        >
                            <div class="mt-4 max-w-xl text-sm text-gray-600">
                                <p class="font-semibold">
                                    Store these recovery codes in a secure
                                    password manager. They can be used to
                                    recover access to your account if your two
                                    factor authentication device is lost.
                                </p>
                            </div>

                            <div
                                class="mt-4 grid max-w-xl gap-1 rounded-lg bg-gray-100 px-4 py-4 font-mono text-sm"
                            >
                                <div
                                    v-for="code in recoveryCodesData"
                                    :key="code"
                                >
                                    {{ code }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <template v-if="!twoFactorEnabled && !showingQrCode">
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900"
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
                                    class="me-3 inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700"
                                    :disabled="confirming"
                                    @click="confirmTwoFactorAuthentication"
                                >
                                    Confirm
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase shadow-sm hover:bg-gray-50"
                                    :disabled="disabling"
                                    @click="disableTwoFactorAuthentication"
                                >
                                    Cancel
                                </button>
                            </template>

                            <template v-else-if="showingRecoveryCodes">
                                <button
                                    type="button"
                                    class="me-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase shadow-sm hover:bg-gray-50"
                                    @click="regenerateRecoveryCodesAction"
                                >
                                    Regenerate Recovery Codes
                                </button>
                            </template>

                            <template v-else>
                                <button
                                    type="button"
                                    class="me-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase shadow-sm hover:bg-gray-50"
                                    @click="showRecoveryCodesAction"
                                >
                                    Show Recovery Codes
                                </button>
                            </template>

                            <button
                                v-if="!showingConfirmation"
                                type="button"
                                class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-red-500"
                                :disabled="disabling"
                                @click="disableTwoFactorAuthentication"
                            >
                                Disable
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
