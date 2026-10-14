<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { update } from '@/routes/password';

const props = defineProps<{
    token: string;
    email: string;
}>();
</script>

<template>
    <GuestLayout title="Reset Password">
        <Head title="Reset Password" />

        <Form
            v-bind="update.form()"
            :transform="
                (data: Record<string, unknown>) => ({
                    ...data,
                    token: props.token,
                    email: props.email,
                })
            "
            :reset-on-success="['password', 'password_confirmation']"
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
                    :value="props.email"
                    class="mt-1 block w-full rounded-md border-input shadow-sm focus:border-ring focus:ring-ring"
                    required
                    readonly
                    autocomplete="username"
                />
                <p v-if="errors.email" class="mt-1 text-xs text-destructive">
                    {{ errors.email }}
                </p>
            </div>

            <div class="mt-4">
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
                    autocomplete="new-password"
                />
                <p v-if="errors.password" class="mt-1 text-xs text-destructive">
                    {{ errors.password }}
                </p>
            </div>

            <div class="mt-4">
                <label
                    for="password_confirmation"
                    class="block text-sm font-medium text-soft-foreground"
                    >Confirm Password</label
                >
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="mt-1 block w-full rounded-md border-input shadow-sm focus:border-ring focus:ring-ring"
                    required
                    autocomplete="new-password"
                />
                <p
                    v-if="errors.password_confirmation"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.password_confirmation }}
                </p>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold tracking-widest text-primary-foreground uppercase transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    :disabled="processing"
                >
                    Reset Password
                </button>
            </div>
        </Form>
    </GuestLayout>
</template>
