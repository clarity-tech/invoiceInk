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
        class="rounded-xl border border-border bg-card"
    >
        <div class="border-b border-border px-6 py-4">
            <h2 class="text-lg font-semibold text-foreground">Bank Details</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Bank information displayed on your invoices for payment.
            </p>
        </div>

        <div class="space-y-6 p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="bank-name"
                        class="block text-sm font-medium text-soft-foreground"
                        >Bank Name</label
                    >
                    <input
                        id="bank-name"
                        v-model="form.bank_name"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="bank-branch"
                        class="block text-sm font-medium text-soft-foreground"
                        >Branch</label
                    >
                    <input
                        id="bank-branch"
                        v-model="form.bank_branch"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        for="bank-acc-name"
                        class="block text-sm font-medium text-soft-foreground"
                        >Account Name</label
                    >
                    <input
                        id="bank-acc-name"
                        v-model="form.bank_account_name"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="bank-acc-num"
                        class="block text-sm font-medium text-soft-foreground"
                        >Account Number</label
                    >
                    <input
                        id="bank-acc-num"
                        v-model="form.bank_account_number"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <label
                        for="bank-ifsc"
                        class="block text-sm font-medium text-soft-foreground"
                        >IFSC Code</label
                    >
                    <input
                        id="bank-ifsc"
                        v-model="form.bank_ifsc"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="bank-swift"
                        class="block text-sm font-medium text-soft-foreground"
                        >SWIFT Code</label
                    >
                    <input
                        id="bank-swift"
                        v-model="form.bank_swift"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                </div>
                <div>
                    <label
                        for="bank-pan"
                        class="block text-sm font-medium text-soft-foreground"
                        >PAN</label
                    >
                    <input
                        id="bank-pan"
                        v-model="form.bank_pan"
                        type="text"
                        class="mt-1 block w-full rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                    />
                </div>
            </div>
        </div>

        <div
            class="flex items-center justify-end gap-3 border-t border-border bg-muted/50 px-6 py-4"
        >
            <span v-show="recentlySuccessful" class="text-sm text-success"
                >Saved.</span
            >
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-50"
            >
                {{ form.processing ? 'Saving...' : 'Save Bank Details' }}
            </button>
        </div>
    </form>
</template>
