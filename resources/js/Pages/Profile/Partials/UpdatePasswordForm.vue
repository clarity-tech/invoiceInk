<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { update } from '@/routes/user-password';

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const recentlySuccessful = ref(false);

function updatePassword(): void {
    form.put(update.url(), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            recentlySuccessful.value = true;
            setTimeout(() => (recentlySuccessful.value = false), 2000);
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }

            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
}
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Update Password
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Ensure your account is using a long, random password to stay
                    secure.
                </p>
            </div>
        </div>

        <div class="mt-5 md:col-span-2 md:mt-0">
            <form @submit.prevent="updatePassword">
                <div class="overflow-hidden bg-white shadow sm:rounded-md">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-4">
                                <label
                                    for="current_password"
                                    class="block text-sm font-medium text-gray-700"
                                    >Current Password</label
                                >
                                <input
                                    id="current_password"
                                    v-model="form.current_password"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                    autocomplete="current-password"
                                />
                                <div
                                    v-if="form.errors.current_password"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.current_password }}
                                </div>
                            </div>

                            <div class="col-span-6 sm:col-span-4">
                                <label
                                    for="password"
                                    class="block text-sm font-medium text-gray-700"
                                    >New Password</label
                                >
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                    autocomplete="new-password"
                                />
                                <div
                                    v-if="form.errors.password"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <div class="col-span-6 sm:col-span-4">
                                <label
                                    for="password_confirmation"
                                    class="block text-sm font-medium text-gray-700"
                                    >Confirm Password</label
                                >
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                    autocomplete="new-password"
                                />
                                <div
                                    v-if="form.errors.password_confirmation"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.password_confirmation }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-end bg-gray-50 px-4 py-3 text-end sm:px-6"
                    >
                        <span
                            v-show="recentlySuccessful"
                            class="me-3 text-sm text-gray-600"
                        >
                            Saved.
                        </span>

                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900"
                            :disabled="form.processing"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
