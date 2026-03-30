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

        <div class="mb-4 text-sm text-gray-600">
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
                class="mb-4 text-sm text-red-600"
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
                    class="block text-sm font-medium text-gray-700"
                    >Code</label
                >
                <input
                    id="code"
                    ref="codeInput"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    autofocus
                    autocomplete="one-time-code"
                />
                <p v-if="errors.code" class="mt-1 text-xs text-red-600">
                    {{ errors.code }}
                </p>
            </div>

            <div v-else class="mt-4">
                <label
                    for="recovery_code"
                    class="block text-sm font-medium text-gray-700"
                    >Recovery Code</label
                >
                <input
                    id="recovery_code"
                    ref="recoveryCodeInput"
                    name="recovery_code"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    autocomplete="one-time-code"
                />
                <p
                    v-if="errors.recovery_code"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ errors.recovery_code }}
                </p>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <button
                    type="button"
                    class="cursor-pointer text-sm text-gray-600 underline hover:text-gray-900"
                    @click="toggleRecovery"
                >
                    <template v-if="!recovery">Use a recovery code</template>
                    <template v-else>Use an authentication code</template>
                </button>

                <button
                    type="submit"
                    class="ms-4 inline-flex items-center rounded-md border border-transparent bg-brand-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition hover:bg-brand-500 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    :disabled="processing"
                >
                    Log in
                </button>
            </div>
        </Form>
    </GuestLayout>
</template>
