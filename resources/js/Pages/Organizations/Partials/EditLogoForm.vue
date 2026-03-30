<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Organization } from '../types';

const props = defineProps<{
    organization: Organization;
}>();

const recentlySuccessful = ref(false);
const previewUrl = ref<string | null>(null);

const form = useForm({
    logo: null as File | null,
});

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files?.[0]) {
        form.logo = target.files[0];
        previewUrl.value = URL.createObjectURL(target.files[0]);
    }
}

function upload() {
    if (!form.logo) return;

    form.post(`/organizations/${props.organization.id}/logo`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            previewUrl.value = null;
            recentlySuccessful.value = true;
            setTimeout(() => (recentlySuccessful.value = false), 2000);
        },
    });
}

function removeLogo() {
    router.delete(`/organizations/${props.organization.id}/logo`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">
                Organization Logo
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Your logo appears on invoices, estimates, and emails.
            </p>
        </div>

        <div class="p-6">
            <!-- Current Logo -->
            <div v-if="organization.logo_url && !previewUrl" class="mb-6">
                <p class="mb-3 text-sm font-medium text-gray-700">
                    Current Logo
                </p>
                <div class="flex items-center gap-4">
                    <div
                        class="flex size-24 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
                        <img
                            :src="organization.logo_url"
                            :alt="organization.name"
                            class="size-full object-contain p-2"
                        />
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-100"
                        @click="removeLogo"
                    >
                        <svg
                            class="size-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                            />
                        </svg>
                        Remove
                    </button>
                </div>
            </div>

            <!-- Upload -->
            <form @submit.prevent="upload">
                <div class="flex items-center gap-6">
                    <!-- Preview -->
                    <div
                        class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-50"
                    >
                        <img
                            v-if="previewUrl"
                            :src="previewUrl"
                            alt="Preview"
                            class="size-full object-contain p-2"
                        />
                        <svg
                            v-else
                            class="size-8 text-gray-300"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21z"
                            />
                        </svg>
                    </div>

                    <div class="flex-1">
                        <label
                            for="logo-upload"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                        >
                            <svg
                                class="size-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"
                                />
                            </svg>
                            Choose file
                        </label>
                        <input
                            id="logo-upload"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml"
                            class="hidden"
                            @change="onFileChange"
                        />
                        <p class="mt-2 text-xs text-gray-500">
                            JPG, PNG, GIF, or SVG. Max 2MB.
                        </p>
                        <p
                            v-if="form.errors.logo"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.logo }}
                        </p>
                    </div>
                </div>

                <div v-if="form.logo" class="mt-4 flex items-center gap-3">
                    <span
                        v-show="recentlySuccessful"
                        class="text-sm text-green-600"
                        >Uploaded!</span
                    >
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                    >
                        {{ form.processing ? 'Uploading...' : 'Upload Logo' }}
                    </button>
                    <button
                        type="button"
                        class="text-sm text-gray-500 hover:text-gray-700"
                        @click="
                            form.reset();
                            previewUrl = null;
                        "
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
