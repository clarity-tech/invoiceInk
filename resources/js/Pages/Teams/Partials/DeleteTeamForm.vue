<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps<{
    team: {
        id: number;
        name: string;
    };
}>();

const confirmingTeamDeletion = ref(false);

const form = useForm({});

function deleteTeam(): void {
    form.delete(`/teams/${props.team.id}`, {
        errorBag: 'deleteTeam',
    });
}
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Delete Team
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Permanently delete this team.
                </p>
            </div>
        </div>

        <div class="mt-5 md:col-span-2 md:mt-0">
            <div class="overflow-hidden bg-white shadow sm:rounded-md">
                <div class="px-4 py-5 sm:p-6">
                    <div class="max-w-xl text-sm text-gray-600">
                        Once a team is deleted, all of its resources and data
                        will be permanently deleted. Before deleting this team,
                        please download any data or information regarding this
                        team that you wish to retain.
                    </div>

                    <div class="mt-5">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-red-500"
                            @click="confirmingTeamDeletion = true"
                        >
                            Delete Team
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmationModal
        :show="confirmingTeamDeletion"
        title="Delete Team"
        message="Are you sure you want to delete this team? Once a team is deleted, all of its resources and data will be permanently deleted."
        confirm-label="Delete Team"
        :destructive="true"
        @confirm="deleteTeam"
        @cancel="confirmingTeamDeletion = false"
    />
</template>
