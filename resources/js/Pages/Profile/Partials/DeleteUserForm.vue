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
    <section class="rounded-xl border border-destructive/25 bg-card p-6">
        <h2
            class="mb-1 text-sm font-semibold tracking-wider text-faint-foreground uppercase"
        >
            Delete Account
        </h2>
        <p class="mb-4 text-sm text-muted-foreground">
            Permanently delete your account.
        </p>

        <p class="mb-5 text-sm text-soft-foreground">
            Once your account is deleted, all of its resources and data will be
            permanently deleted. Before deleting your account, please download
            any data or information that you wish to retain.
        </p>

        <button
            type="button"
            class="inline-flex items-center rounded-lg bg-destructive px-4 py-2 text-sm font-semibold text-destructive-foreground shadow-sm transition hover:bg-destructive/90 focus:ring-2 focus:ring-destructive focus:ring-offset-2 focus:outline-none"
            @click="confirmUserDeletion"
        >
            Delete Account
        </button>
    </section>

    <!-- Delete User Confirmation Modal -->
    <Teleport to="body">
        <div
            v-if="confirmingUserDeletion"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div
                class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
            >
                <div
                    class="fixed inset-0 bg-overlay transition-opacity"
                    @click="confirmingUserDeletion = false"
                />
                <span
                    class="hidden sm:inline-block sm:h-screen sm:align-middle"
                    aria-hidden="true"
                    >&#8203;</span
                >
                <div
                    class="relative inline-block transform overflow-hidden rounded-xl bg-card text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <div class="bg-card px-6 pt-6 pb-4">
                        <h3 class="text-base font-semibold text-foreground">
                            Delete Account
                        </h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Are you sure you want to delete your account? Once
                            your account is deleted, all of its resources and
                            data will be permanently deleted. Please enter your
                            password to confirm.
                        </p>
                        <div class="mt-4">
                            <input
                                ref="passwordInput"
                                v-model="form.password"
                                type="password"
                                class="block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                                placeholder="Password"
                                autocomplete="current-password"
                                @keydown.enter="deleteUser"
                            />
                            <div
                                v-if="form.errors.password"
                                class="mt-2 text-sm text-destructive"
                            >
                                {{ form.errors.password }}
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-muted px-6 py-4 sm:flex sm:flex-row-reverse sm:gap-2"
                    >
                        <button
                            type="button"
                            class="inline-flex w-full justify-center rounded-lg bg-destructive px-4 py-2 text-sm font-semibold text-destructive-foreground shadow-sm transition hover:bg-destructive/90 disabled:opacity-60 sm:w-auto"
                            :disabled="form.processing"
                            @click="deleteUser"
                        >
                            Delete Account
                        </button>
                        <button
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-semibold text-soft-foreground shadow-sm transition hover:bg-muted sm:mt-0 sm:w-auto"
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
