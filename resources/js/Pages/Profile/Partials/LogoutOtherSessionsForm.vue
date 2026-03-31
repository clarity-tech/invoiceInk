<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Session {
    ip_address: string;
    is_current_device: boolean;
    last_active: string;
    platform: string | null;
    browser: string | null;
    is_desktop: boolean;
}

defineProps<{
    sessions: Session[];
}>();

const confirmingLogout = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);
const recentlySuccessful = ref(false);

const form = useForm({
    password: '',
});

function confirmLogout(): void {
    form.password = '';
    confirmingLogout.value = true;
    setTimeout(() => passwordInput.value?.focus(), 250);
}

function logoutOtherBrowserSessions(): void {
    form.delete('/user/other-browser-sessions', {
        preserveScroll: true,
        onSuccess: () => {
            confirmingLogout.value = false;
            form.reset();
            recentlySuccessful.value = true;
            setTimeout(() => (recentlySuccessful.value = false), 2000);
        },
        onError: () => passwordInput.value?.focus(),
    });
}
</script>

<template>
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="mb-1 text-sm font-semibold tracking-wider text-gray-400 uppercase">
            Browser Sessions
        </h2>
        <p class="mb-6 text-sm text-gray-500">
            Manage and log out your active sessions on other browsers and devices.
        </p>

        <p class="mb-5 text-sm text-gray-600">
            If necessary, you may log out of all of your other browser sessions across all of your
            devices. Some of your recent sessions are listed below; however, this list may not be
            exhaustive. If you feel your account has been compromised, you should also update your
            password.
        </p>

        <!-- Session List -->
        <div v-if="sessions.length > 0" class="mb-5 space-y-3">
            <div
                v-for="(session, index) in sessions"
                :key="index"
                class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3"
            >
                <div class="shrink-0 text-gray-400">
                    <svg
                        v-if="session.is_desktop"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="size-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"
                        />
                    </svg>
                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="size-6"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"
                        />
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-700">
                        {{ session.platform || 'Unknown' }} &mdash;
                        {{ session.browser || 'Unknown' }}
                    </div>
                    <div class="mt-0.5 text-xs text-gray-500">
                        {{ session.ip_address }} &middot;
                        <span v-if="session.is_current_device" class="font-semibold text-green-600">
                            This device
                        </span>
                        <span v-else>Last active {{ session.last_active }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none"
                @click="confirmLogout"
            >
                Log Out Other Sessions
            </button>
            <span v-show="recentlySuccessful" class="text-sm text-gray-500">Done.</span>
        </div>
    </section>

    <!-- Logout Confirmation Modal -->
    <Teleport to="body">
        <div v-if="confirmingLogout" class="fixed inset-0 z-50 overflow-y-auto">
            <div
                class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    @click="confirmingLogout = false"
                />
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true"
                    >&#8203;</span
                >
                <div
                    class="relative inline-block transform overflow-hidden rounded-xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <div class="bg-white px-6 pt-6 pb-4">
                        <h3 class="text-base font-semibold text-gray-900">
                            Log Out Other Browser Sessions
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Please enter your password to confirm you would like to log out of your
                            other browser sessions across all of your devices.
                        </p>
                        <div class="mt-4">
                            <input
                                ref="passwordInput"
                                v-model="form.password"
                                type="password"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="Password"
                                autocomplete="current-password"
                                @keydown.enter="logoutOtherBrowserSessions"
                            />
                            <div v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                                {{ form.errors.password }}
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse sm:gap-2">
                        <button
                            type="button"
                            class="inline-flex w-full justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500 disabled:opacity-60 sm:w-auto"
                            :disabled="form.processing"
                            @click="logoutOtherBrowserSessions"
                        >
                            Log Out Sessions
                        </button>
                        <button
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 sm:mt-0 sm:w-auto"
                            @click="confirmingLogout = false"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
