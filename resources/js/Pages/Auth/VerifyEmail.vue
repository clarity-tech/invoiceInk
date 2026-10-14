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

        <div class="mb-4 text-sm text-soft-foreground">
            Before continuing, could you verify your email address by clicking
            on the link we just emailed to you? If you didn't receive the email,
            we will gladly send you another.
        </div>

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-sm font-medium text-success"
        >
            A new verification link has been sent to the email address you
            provided in your profile settings.
        </div>

        <div class="mt-4 space-y-4">
            <Form v-bind="send.form()" v-slot="{ processing }">
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold tracking-widest text-primary-foreground uppercase transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    :disabled="processing"
                >
                    Resend Verification Email
                </button>
            </Form>

            <div class="flex items-center justify-between text-sm">
                <Link
                    href="/user/profile"
                    class="rounded-md text-soft-foreground underline hover:text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                >
                    Edit Profile
                </Link>

                <button
                    type="button"
                    class="rounded-md text-soft-foreground underline hover:text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                    @click="logoutUser"
                >
                    Log Out
                </button>
            </div>
        </div>
    </GuestLayout>
</template>
