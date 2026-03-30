<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { update } from '@/routes/teams';

const props = defineProps<{
    team: {
        id: number;
        name: string;
        owner: {
            id: number;
            name: string;
            email: string;
            profile_photo_url: string;
        };
    };
    canUpdate: boolean;
}>();

const form = useForm({
    name: props.team.name,
});

const recentlySuccessful = ref(false);

function updateTeamName(): void {
    form.put(update.url({ team: props.team.id }), {
        errorBag: 'updateTeamName',
        preserveScroll: true,
        onSuccess: () => {
            recentlySuccessful.value = true;
            setTimeout(() => (recentlySuccessful.value = false), 2000);
        },
    });
}
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Team Name
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    The team's name and owner information.
                </p>
            </div>
        </div>

        <div class="mt-5 md:col-span-2 md:mt-0">
            <form @submit.prevent="updateTeamName">
                <div class="overflow-hidden bg-white shadow sm:rounded-md">
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
                                        :src="team.owner.profile_photo_url"
                                        :alt="team.owner.name"
                                    />
                                    <div class="ms-4 leading-tight">
                                        <div class="text-gray-900">
                                            {{ team.owner.name }}
                                        </div>
                                        <div class="text-sm text-gray-700">
                                            {{ team.owner.email }}
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
                                    :disabled="!canUpdate"
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
                        v-if="canUpdate"
                        class="flex items-center justify-end bg-gray-50 px-4 py-3 text-end sm:px-6"
                    >
                        <span
                            v-show="recentlySuccessful"
                            class="me-3 text-sm text-gray-600"
                        >
                            Saved.
                        </span>

                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900"
                            :disabled="form.processing"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
