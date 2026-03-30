<script setup lang="ts">
import DeleteTeamForm from './Partials/DeleteTeamForm.vue';
import TeamMemberManager from './Partials/TeamMemberManager.vue';
import UpdateTeamNameForm from './Partials/UpdateTeamNameForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Role {
    key: string;
    name: string;
    description: string;
    permissions: string[];
}

interface TeamMember {
    id: number;
    name: string;
    email: string;
    profile_photo_url: string;
    membership: {
        role: string;
    };
}

interface TeamInvitation {
    id: number;
    email: string;
}

interface Team {
    id: number;
    name: string;
    personal_team: boolean;
    owner: {
        id: number;
        name: string;
        email: string;
        profile_photo_url: string;
    };
    users: TeamMember[];
    team_invitations: TeamInvitation[];
}

interface Permissions {
    canAddTeamMembers: boolean;
    canDeleteTeam: boolean;
    canRemoveTeamMembers: boolean;
    canUpdateTeam: boolean;
    canUpdateTeamMembers: boolean;
}

defineProps<{
    team: Team;
    availableRoles: Role[];
    permissions: Permissions;
    hasRoles: boolean;
    sendsTeamInvitations: boolean;
}>();
</script>

<template>
    <AppLayout title="Organization Settings">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Organization Settings
            </h2>
        </template>

        <div>
            <div class="mx-auto max-w-7xl py-10 sm:px-6 lg:px-8">
                <UpdateTeamNameForm
                    :team="team"
                    :can-update="permissions.canUpdateTeam"
                />

                <TeamMemberManager
                    :team="team"
                    :available-roles="availableRoles"
                    :permissions="permissions"
                    :has-roles="hasRoles"
                    :sends-team-invitations="sendsTeamInvitations"
                    class="mt-10 sm:mt-0"
                />

                <template
                    v-if="permissions.canDeleteTeam && !team.personal_team"
                >
                    <div class="hidden sm:block">
                        <div class="py-8">
                            <div class="border-t border-gray-200" />
                        </div>
                    </div>

                    <div class="mt-10 sm:mt-0">
                        <DeleteTeamForm :team="team" />
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
