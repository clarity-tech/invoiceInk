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
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Browser Sessions
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Manage and log out your active sessions on other browsers
                    and devices.
                </p>
            </div>
        </div>

        <div class="mt-5 md:col-span-2 md:mt-0">
            <div class="overflow-hidden bg-white shadow sm:rounded-md">
                <div class="px-4 py-5 sm:p-6">
                    <div class="max-w-xl text-sm text-gray-600">
                        If necessary, you may log out of all of your other
                        browser sessions across all of your devices. Some of
                        your recent sessions are listed below; however, this
                        list may not be exhaustive. If you feel your account has
                        been compromised, you should also update your password.
                    </div>

                    <!-- Session List -->
                    <div v-if="sessions.length > 0" class="mt-5 space-y-6">
                        <div
                            v-for="(session, index) in sessions"
                            :key="index"
                            class="flex items-center"
                        >
                            <div>
                                <svg
                                    v-if="session.is_desktop"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="size-8 text-gray-500"
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
                                    class="size-8 text-gray-500"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"
                                    />
                                </svg>
                            </div>

                            <div class="ms-3">
                                <div class="text-sm text-gray-600">
                                    {{ session.platform || 'Unknown' }} -
                                    {{ session.browser || 'Unknown' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ session.ip_address }},
                                    <span
                                        v-if="session.is_current_device"
                                        class="font-semibold text-green-500"
                                        >This device</span
                                    >
                                    <span v-else
                                        >Last active
                                        {{ session.last_active }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900"
                            @click="confirmLogout"
                        >
                            Log Out Other Browser Sessions
                        </button>

                        <span
                            v-show="recentlySuccessful"
                            class="ms-3 text-sm text-gray-600"
                        >
                            Done.
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <Teleport to="body">
        <div v-if="confirmingLogout" class="fixed inset-0 z-50 overflow-y-auto">
            <div
                class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
            >
                <div
                    class="bg-opacity-75 fixed inset-0 bg-gray-500 transition-opacity"
                    @click="confirmingLogout = false"
                />
                <span
                    class="hidden sm:inline-block sm:h-screen sm:align-middle"
                    aria-hidden="true"
                    >&#8203;</span
                >
                <div
                    class="relative inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Log Out Other Browser Sessions
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Please enter your password to confirm you would
                                like to log out of your other browser sessions
                                across all of your devices.
                            </p>
                        </div>
                        <div class="mt-4">
                            <input
                                ref="passwordInput"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-3/4 rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="Password"
                                autocomplete="current-password"
                                @keydown.enter="logoutOtherBrowserSessions"
                            />
                            <div
                                v-if="form.errors.password"
                                class="mt-2 text-sm text-red-600"
                            >
                                {{ form.errors.password }}
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
                    >
                        <button
                            type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-gray-700 sm:ml-3 sm:w-auto sm:text-sm"
                            :disabled="form.processing"
                            @click="logoutOtherBrowserSessions"
                        >
                            Log Out Other Browser Sessions
                        </button>
                        <button
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
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
