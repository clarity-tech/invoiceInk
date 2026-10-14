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
    <section class="rounded-xl border border-border bg-card p-6">
        <h2
            class="mb-1 text-sm font-semibold tracking-wider text-faint-foreground uppercase"
        >
            Update Password
        </h2>
        <p class="mb-6 text-sm text-muted-foreground">
            Ensure your account is using a long, random password to stay secure.
        </p>

        <form @submit.prevent="updatePassword">
            <div class="space-y-5">
                <div>
                    <label
                        for="current_password"
                        class="block text-sm font-medium text-soft-foreground"
                    >
                        Current Password
                    </label>
                    <input
                        id="current_password"
                        v-model="form.current_password"
                        type="password"
                        class="mt-1 block w-full max-w-md rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        autocomplete="current-password"
                    />
                    <div
                        v-if="form.errors.current_password"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.current_password }}
                    </div>
                </div>

                <div>
                    <label
                        for="password"
                        class="block text-sm font-medium text-soft-foreground"
                    >
                        New Password
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full max-w-md rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        autocomplete="new-password"
                    />
                    <div
                        v-if="form.errors.password"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.password }}
                    </div>
                </div>

                <div>
                    <label
                        for="password_confirmation"
                        class="block text-sm font-medium text-soft-foreground"
                    >
                        Confirm Password
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full max-w-md rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        autocomplete="new-password"
                    />
                    <div
                        v-if="form.errors.password_confirmation"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.password_confirmation }}
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-60"
                    :disabled="form.processing"
                >
                    Update Password
                </button>
                <span
                    v-show="recentlySuccessful"
                    class="text-sm text-muted-foreground"
                    >Saved.</span
                >
            </div>
        </form>
    </section>
</template>
