<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

const showPassword = ref(false);
const showConfirmPassword = ref(false);
</script>

<template>
    <GuestLayout title="Register">
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <div
                v-if="Object.keys(errors).length"
                class="mb-4 text-sm text-red-600"
            >
                <ul>
                    <li v-for="(error, key) in errors" :key="key">
                        {{ error }}
                    </li>
                </ul>
            </div>

            <div>
                <label
                    for="name"
                    class="block text-sm font-medium text-gray-700"
                    >Name</label
                >
                <input
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    required
                    autofocus
                    autocomplete="name"
                />
                <p v-if="errors.name" class="mt-1 text-xs text-red-600">
                    {{ errors.name }}
                </p>
            </div>

            <div class="mt-4">
                <label
                    for="email"
                    class="block text-sm font-medium text-gray-700"
                    >Email</label
                >
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    required
                    autocomplete="username"
                />
                <p v-if="errors.email" class="mt-1 text-xs text-red-600">
                    {{ errors.email }}
                </p>
            </div>

            <div class="mt-4">
                <label
                    for="password"
                    class="block text-sm font-medium text-gray-700"
                    >Password</label
                >
                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 mt-1 flex items-center pr-3"
                        @click="showPassword = !showPassword"
                        :aria-label="
                            showPassword ? 'Hide password' : 'Show password'
                        "
                    >
                        <svg
                            v-if="!showPassword"
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                            />
                        </svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Must be at least 8 characters
                </p>
                <p v-if="errors.password" class="mt-1 text-xs text-red-600">
                    {{ errors.password }}
                </p>
            </div>

            <div class="mt-4">
                <label
                    for="password_confirmation"
                    class="block text-sm font-medium text-gray-700"
                    >Confirm Password</label
                >
                <div class="relative">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        class="mt-1 block w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 mt-1 flex items-center pr-3"
                        @click="showConfirmPassword = !showConfirmPassword"
                        :aria-label="
                            showConfirmPassword
                                ? 'Hide password'
                                : 'Show password'
                        "
                    >
                        <svg
                            v-if="!showConfirmPassword"
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                            />
                        </svg>
                    </button>
                </div>
                <p
                    v-if="errors.password_confirmation"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errors.password_confirmation }}
                </p>
            </div>

            <div class="mt-4">
                <label for="terms" class="flex items-center">
                    <input
                        id="terms"
                        name="terms"
                        type="checkbox"
                        class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500"
                    />
                    <span class="ms-2 text-sm text-gray-600">
                        I agree to the
                        <a
                            href="/terms-of-service"
                            target="_blank"
                            class="text-brand-600 underline hover:text-brand-500"
                            >Terms of Service</a
                        >
                        and
                        <a
                            href="/privacy-policy"
                            target="_blank"
                            class="text-brand-600 underline hover:text-brand-500"
                            >Privacy Policy</a
                        >
                    </span>
                </label>
                <p v-if="errors.terms" class="mt-1 text-xs text-red-600">
                    {{ errors.terms }}
                </p>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="login.url()"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none"
                >
                    Already registered?
                </Link>

                <button
                    type="submit"
                    class="ms-4 inline-flex items-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition hover:bg-brand-500 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    :disabled="processing"
                >
                    Register
                </button>
            </div>
        </Form>
    </GuestLayout>
</template>
