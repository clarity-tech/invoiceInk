<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { store } from '@/routes/teams';

const page = usePage();
const user = page.props.auth?.user ?? page.props.user;

const form = useForm({
    name: '',
});

function createOrganization(): void {
    form.post(store.url(), {
        errorBag: 'createTeam',
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout title="Create organization">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Create organization
            </h2>
        </template>

        <div class="mx-auto max-w-2xl px-4 py-8 sm:px-0">
            <form
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                @submit.prevent="createOrganization"
            >
                <div class="space-y-6 p-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            New organization
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Give your organization a name to get started. You'll
                            add company details, location, currency, and
                            contacts in the setup steps that follow.
                        </p>
                    </div>

                    <!-- Owner -->
                    <div class="flex items-center gap-3">
                        <img
                            class="size-10 rounded-full object-cover"
                            :src="user.profile_photo_url"
                            :alt="user.name"
                        />
                        <div class="leading-tight">
                            <div class="text-sm font-medium text-gray-900">
                                {{ user.name }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ user.email }}
                            </div>
                        </div>
                        <span
                            class="ml-auto inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                        >
                            Owner
                        </span>
                    </div>

                    <!-- Organization name -->
                    <div>
                        <label
                            for="name"
                            class="block text-sm font-medium text-gray-700"
                            >Organization name</label
                        >
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Acme Trading"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                            autofocus
                        />
                        <p class="mt-1.5 text-xs text-gray-500">
                            This is the workspace label shown in the
                            organization switcher. Your legal/business name on
                            invoices is set during setup.
                        </p>
                        <div
                            v-if="form.errors.name"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ form.errors.name }}
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4"
                >
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Continue to setup
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
