<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { store } from '@/routes/password/confirm';
</script>

<template>
    <GuestLayout title="Confirm Password">
        <Head title="Confirm Password" />

        <div class="mb-4 text-sm text-soft-foreground">
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
                class="mb-4 text-sm text-destructive"
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
                    class="block text-sm font-medium text-soft-foreground"
                    >Password</label
                >
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full rounded-md border-input shadow-sm focus:border-ring focus:ring-ring"
                    required
                    autofocus
                    autocomplete="current-password"
                />
                <p v-if="errors.password" class="mt-1 text-xs text-destructive">
                    {{ errors.password }}
                </p>
            </div>

            <div class="mt-4 flex justify-end">
                <button
                    type="submit"
                    class="ms-4 inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold tracking-widest text-primary-foreground uppercase transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    :disabled="processing"
                >
                    Confirm
                </button>
            </div>
        </Form>
    </GuestLayout>
</template>
