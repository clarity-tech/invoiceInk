<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

function confirmUserDeletion(): void {
    form.password = '';
    confirmingUserDeletion.value = true;
    setTimeout(() => passwordInput.value?.focus(), 250);
}

function deleteUser(): void {
    form.delete('/user', {
        preserveScroll: true,
        onSuccess: () => {
            confirmingUserDeletion.value = false;
        },
        onError: () => passwordInput.value?.focus(),
    });
}
</script>

<template>
    <section class="rounded-xl border border-red-100 bg-white p-6">
        <h2 class="mb-1 text-sm font-semibold tracking-wider text-gray-400 uppercase">
            Delete Account
        </h2>
        <p class="mb-4 text-sm text-gray-500">Permanently delete your account.</p>

        <p class="mb-5 text-sm text-gray-600">
            Once your account is deleted, all of its resources and data will be permanently deleted.
            Before deleting your account, please download any data or information that you wish to
            retain.
        </p>

        <button
            type="button"
            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none"
            @click="confirmUserDeletion"
        >
            Delete Account
        </button>
    </section>

    <!-- Delete User Confirmation Modal -->
    <Teleport to="body">
        <div v-if="confirmingUserDeletion" class="fixed inset-0 z-50 overflow-y-auto">
            <div
                class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    @click="confirmingUserDeletion = false"
                />
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true"
                    >&#8203;</span
                >
                <div
                    class="relative inline-block transform overflow-hidden rounded-xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <div class="bg-white px-6 pt-6 pb-4">
                        <h3 class="text-base font-semibold text-gray-900">Delete Account</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Are you sure you want to delete your account? Once your account is
                            deleted, all of its resources and data will be permanently deleted.
                            Please enter your password to confirm.
                        </p>
                        <div class="mt-4">
                            <input
                                ref="passwordInput"
                                v-model="form.password"
                                type="password"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                placeholder="Password"
                                autocomplete="current-password"
                                @keydown.enter="deleteUser"
                            />
                            <div v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                                {{ form.errors.password }}
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse sm:gap-2">
                        <button
                            type="button"
                            class="inline-flex w-full justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 disabled:opacity-60 sm:w-auto"
                            :disabled="form.processing"
                            @click="deleteUser"
                        >
                            Delete Account
                        </button>
                        <button
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 sm:mt-0 sm:w-auto"
                            @click="confirmingUserDeletion = false"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
