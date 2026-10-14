<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { store } from '@/routes/two-factor/login';

const recovery = ref(false);
const codeInput = ref<HTMLInputElement | null>(null);
const recoveryCodeInput = ref<HTMLInputElement | null>(null);

function toggleRecovery(): void {
    recovery.value = !recovery.value;
    nextTick(() => {
        if (recovery.value) {
            recoveryCodeInput.value?.focus();
        } else {
            codeInput.value?.focus();
        }
    });
}
</script>

<template>
    <GuestLayout title="Two-factor Confirmation">
        <Head title="Two-factor Confirmation" />

        <div class="mb-4 text-sm text-soft-foreground">
            <template v-if="!recovery">
                Please confirm access to your account by entering the
                authentication code provided by your authenticator application.
            </template>
            <template v-else>
                Please confirm access to your account by entering one of your
                emergency recovery codes.
            </template>
        </div>

        <Form v-bind="store.form()" v-slot="{ errors, processing }">
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

            <div v-if="!recovery" class="mt-4">
                <label
                    for="code"
                    class="block text-sm font-medium text-soft-foreground"
                    >Code</label
                >
                <input
                    id="code"
                    ref="codeInput"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full rounded-md border-input shadow-sm focus:border-ring focus:ring-ring"
                    autofocus
                    autocomplete="one-time-code"
                />
                <p v-if="errors.code" class="mt-1 text-xs text-destructive">
                    {{ errors.code }}
                </p>
            </div>

            <div v-else class="mt-4">
                <label
                    for="recovery_code"
                    class="block text-sm font-medium text-soft-foreground"
                    >Recovery Code</label
                >
                <input
                    id="recovery_code"
                    ref="recoveryCodeInput"
                    name="recovery_code"
                    type="text"
                    class="mt-1 block w-full rounded-md border-input shadow-sm focus:border-ring focus:ring-ring"
                    autocomplete="one-time-code"
                />
                <p
                    v-if="errors.recovery_code"
                    class="mt-1 text-xs text-destructive"
                >
                    {{ errors.recovery_code }}
                </p>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <button
                    type="button"
                    class="cursor-pointer text-sm text-soft-foreground underline hover:text-foreground"
                    @click="toggleRecovery"
                >
                    <template v-if="!recovery">Use a recovery code</template>
                    <template v-else>Use an authentication code</template>
                </button>

                <button
                    type="submit"
                    class="ms-4 inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold tracking-widest text-primary-foreground uppercase transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    :disabled="processing"
                >
                    Log in
                </button>
            </div>
        </Form>
    </GuestLayout>
</template>
