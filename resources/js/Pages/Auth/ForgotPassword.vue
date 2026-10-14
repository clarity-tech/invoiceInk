<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <GuestLayout title="Forgot Password">
        <Head title="Forgot Password" />

        <div class="mb-4 text-sm text-soft-foreground">
            Forgot your password? No problem. Just let us know your email
            address and we will email you a password reset link that will allow
            you to choose a new one.
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-success">
            {{ status }}
        </div>

        <Form v-bind="email.form()" v-slot="{ errors, processing }">
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

            <div class="block">
                <label
                    for="email"
                    class="block text-sm font-medium text-soft-foreground"
                    >Email</label
                >
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="mt-1 block w-full rounded-md border-input shadow-sm focus:border-ring focus:ring-ring"
                    required
                    autofocus
                    autocomplete="username"
                />
                <p v-if="errors.email" class="mt-1 text-xs text-destructive">
                    {{ errors.email }}
                </p>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold tracking-widest text-primary-foreground uppercase transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    :disabled="processing"
                >
                    Email Password Reset Link
                </button>
            </div>
        </Form>
    </GuestLayout>
</template>
