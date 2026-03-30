<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineProps<{
    status?: string;
}>();

function logoutUser(): void {
    router.post(logout.url());
}
</script>

<template>
    <GuestLayout title="Email Verification">
        <Head title="Email Verification" />

        <div class="mb-4 text-sm text-gray-600">
            Before continuing, could you verify your email address by clicking
            on the link we just emailed to you? If you didn't receive the email,
            we will gladly send you another.
        </div>

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-sm font-medium text-green-600"
        >
            A new verification link has been sent to the email address you
            provided in your profile settings.
        </div>

        <div class="mt-4 space-y-4">
            <Form v-bind="send.form()" v-slot="{ processing }">
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition hover:bg-brand-500 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    :disabled="processing"
                >
                    Resend Verification Email
                </button>
            </Form>

            <div class="flex items-center justify-between text-sm">
                <Link
                    href="/user/profile"
                    class="rounded-md text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none"
                >
                    Edit Profile
                </Link>

                <button
                    type="button"
                    class="rounded-md text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none"
                    @click="logoutUser"
                >
                    Log Out
                </button>
            </div>
        </div>
    </GuestLayout>
</template>
