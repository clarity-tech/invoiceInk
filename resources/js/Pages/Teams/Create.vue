<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { store } from '@/routes/teams';

const page = usePage();
const user = page.props.auth?.user ?? page.props.user;

const form = useForm({
    name: '',
});

function createTeam(): void {
    form.post(store.url(), {
        errorBag: 'createTeam',
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout title="Create Team">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Create Team
            </h2>
        </template>

        <div>
            <div class="mx-auto max-w-7xl py-10 sm:px-6 lg:px-8">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <div class="px-4 sm:px-0">
                            <h3
                                class="text-lg leading-6 font-medium text-gray-900"
                            >
                                Team Details
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Create a new team to collaborate with others on
                                projects.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 md:col-span-2 md:mt-0">
                        <form @submit.prevent="createTeam">
                            <div
                                class="overflow-hidden bg-white shadow sm:rounded-md"
                            >
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="grid grid-cols-6 gap-6">
                                        <!-- Team Owner -->
                                        <div class="col-span-6">
                                            <label
                                                class="block text-sm font-medium text-gray-700"
                                                >Team Owner</label
                                            >
                                            <div class="mt-2 flex items-center">
                                                <img
                                                    class="size-12 rounded-full object-cover"
                                                    :src="
                                                        user.profile_photo_url
                                                    "
                                                    :alt="user.name"
                                                />
                                                <div class="ms-4 leading-tight">
                                                    <div class="text-gray-900">
                                                        {{ user.name }}
                                                    </div>
                                                    <div
                                                        class="text-sm text-gray-700"
                                                    >
                                                        {{ user.email }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Team Name -->
                                        <div class="col-span-6 sm:col-span-4">
                                            <label
                                                for="name"
                                                class="block text-sm font-medium text-gray-700"
                                                >Team Name</label
                                            >
                                            <input
                                                id="name"
                                                v-model="form.name"
                                                type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                                autofocus
                                            />
                                            <div
                                                v-if="form.errors.name"
                                                class="mt-2 text-sm text-red-600"
                                            >
                                                {{ form.errors.name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-end bg-gray-50 px-4 py-3 text-end sm:px-6"
                                >
                                    <button
                                        type="submit"
                                        class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900"
                                        :disabled="form.processing"
                                    >
                                        Create
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
