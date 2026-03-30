<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { store } from '@/routes/password/confirm';
</script>

<template>
    <GuestLayout title="Confirm Password">
        <Head title="Confirm Password" />

        <div class="mb-4 text-sm text-gray-600">
            This is a secure area of the application. Please confirm your
            password before continuing.
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
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
                    for="password"
                    class="block text-sm font-medium text-gray-700"
                    >Password</label
                >
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    required
                    autofocus
                    autocomplete="current-password"
                />
                <p v-if="errors.password" class="mt-1 text-xs text-red-600">
                    {{ errors.password }}
                </p>
            </div>

            <div class="mt-4 flex justify-end">
                <button
                    type="submit"
                    class="ms-4 inline-flex items-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition hover:bg-brand-500 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    :disabled="processing"
                >
                    Confirm
                </button>
            </div>
        </Form>
    </GuestLayout>
</template>
