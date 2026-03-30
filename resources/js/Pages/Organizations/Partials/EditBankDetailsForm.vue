<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Organization } from '../types';

const props = defineProps<{
    organization: Organization;
}>();

const recentlySuccessful = ref(false);
const bank = props.organization.bank_details;

const form = useForm({
    bank_account_name: bank?.account_name ?? '',
    bank_account_number: bank?.account_number ?? '',
    bank_name: bank?.bank_name ?? '',
    bank_ifsc: bank?.ifsc ?? '',
    bank_branch: bank?.branch ?? '',
    bank_swift: bank?.swift ?? '',
    bank_pan: bank?.pan ?? '',
});

function submit() {
    form.put(`/organizations/${props.organization.id}/bank-details`, {
        preserveScroll: true,
        onSuccess: () => {
            recentlySuccessful.value = true;
            setTimeout(() => (recentlySuccessful.value = false), 2000);
        },
    });
}
</script>

<template>
    <form
        @submit.prevent="submit"
        class="rounded-xl border border-gray-200 bg-white"
    >
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Bank Details</h2>
            <p class="mt-1 text-sm text-gray-500">
                Bank information displayed on your invoices for payment.
            </p>
        </div>

        <div class="space-y-6 p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="bank-name"
                        class="block text-sm font-medium text-gray-700"
                        >Bank Name</label
                    >
                    <input
                        id="bank-name"
                        v-model="form.bank_name"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="bank-branch"
                        class="block text-sm font-medium text-gray-700"
                        >Branch</label
                    >
                    <input
                        id="bank-branch"
                        v-model="form.bank_branch"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="bank-acc-name"
                        class="block text-sm font-medium text-gray-700"
                        >Account Name</label
                    >
                    <input
                        id="bank-acc-name"
                        v-model="form.bank_account_name"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="bank-acc-num"
                        class="block text-sm font-medium text-gray-700"
                        >Account Number</label
                    >
                    <input
                        id="bank-acc-num"
                        v-model="form.bank_account_number"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label
                        for="bank-ifsc"
                        class="block text-sm font-medium text-gray-700"
                        >IFSC Code</label
                    >
                    <input
                        id="bank-ifsc"
                        v-model="form.bank_ifsc"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="bank-swift"
                        class="block text-sm font-medium text-gray-700"
                        >SWIFT Code</label
                    >
                    <input
                        id="bank-swift"
                        v-model="form.bank_swift"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="bank-pan"
                        class="block text-sm font-medium text-gray-700"
                        >PAN</label
                    >
                    <input
                        id="bank-pan"
                        v-model="form.bank_pan"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    />
                </div>
            </div>
        </div>

        <div
            class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-4"
        >
            <span v-show="recentlySuccessful" class="text-sm text-green-600"
                >Saved.</span
            >
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
            >
                {{ form.processing ? 'Saving...' : 'Save Bank Details' }}
            </button>
        </div>
    </form>
</template>
