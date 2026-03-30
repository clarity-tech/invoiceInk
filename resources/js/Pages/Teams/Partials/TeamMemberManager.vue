<script setup lang="ts">
import { useForm, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

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

const props = defineProps<{
    team: Team;
    availableRoles: Role[];
    permissions: Permissions;
    hasRoles: boolean;
    sendsTeamInvitations: boolean;
}>();

const page = usePage();
const currentUserId = (page.props.auth?.user ?? page.props.user)?.id;

const addMemberForm = useForm({
    email: '',
    role: props.availableRoles.length > 0 ? props.availableRoles[0].key : '',
});

const recentlyAdded = ref(false);

// Role management modal
const managingRoleFor = ref<TeamMember | null>(null);
const currentRole = ref('');

// Leave team
const confirmingLeavingTeam = ref(false);

// Remove member
const confirmingRemoval = ref(false);
const memberBeingRemoved = ref<number | null>(null);

function addTeamMember(): void {
    addMemberForm.post(`/teams/${props.team.id}/members`, {
        errorBag: 'addTeamMember',
        preserveScroll: true,
        onSuccess: () => {
            addMemberForm.reset();
            recentlyAdded.value = true;
            setTimeout(() => (recentlyAdded.value = false), 2000);
        },
    });
}

function cancelInvitation(invitationId: number): void {
    router.delete(`/teams/${props.team.id}/invitations/${invitationId}`, {
        preserveScroll: true,
    });
}

function manageRole(member: TeamMember): void {
    managingRoleFor.value = member;
    currentRole.value = member.membership.role;
}

function updateRole(): void {
    if (!managingRoleFor.value) {
        return;
    }

    router.put(
        `/teams/${props.team.id}/members/${managingRoleFor.value.id}`,
        {
            role: currentRole.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                managingRoleFor.value = null;
            },
        },
    );
}

function confirmLeavingTeam(): void {
    confirmingLeavingTeam.value = true;
}

function leaveTeam(): void {
    router.delete(`/teams/${props.team.id}/members/${currentUserId}`, {
        preserveScroll: true,
    });
}

function confirmTeamMemberRemoval(userId: number): void {
    memberBeingRemoved.value = userId;
    confirmingRemoval.value = true;
}

function removeTeamMember(): void {
    if (!memberBeingRemoved.value) {
        return;
    }

    router.delete(
        `/teams/${props.team.id}/members/${memberBeingRemoved.value}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmingRemoval.value = false;
                memberBeingRemoved.value = null;
            },
        },
    );
}

function findRoleName(roleKey: string): string {
    return props.availableRoles.find((r) => r.key === roleKey)?.name ?? roleKey;
}
</script>

<template>
    <!-- Add Team Member -->
    <div v-if="permissions.canAddTeamMembers">
        <div class="hidden sm:block">
            <div class="py-8">
                <div class="border-t border-gray-200" />
            </div>
        </div>

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Add Team Member
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Add a new team member to your team, allowing them to
                        collaborate with you.
                    </p>
                </div>
            </div>

            <div class="mt-5 md:col-span-2 md:mt-0">
                <form @submit.prevent="addTeamMember">
                    <div class="overflow-hidden bg-white shadow sm:rounded-md">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6">
                                    <div class="max-w-xl text-sm text-gray-600">
                                        Please provide the email address of the
                                        person you would like to add to this
                                        team.
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="col-span-6 sm:col-span-4">
                                    <label
                                        for="member-email"
                                        class="block text-sm font-medium text-gray-700"
                                        >Email</label
                                    >
                                    <input
                                        id="member-email"
                                        v-model="addMemberForm.email"
                                        type="email"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                    />
                                    <div
                                        v-if="addMemberForm.errors.email"
                                        class="mt-2 text-sm text-red-600"
                                    >
                                        {{ addMemberForm.errors.email }}
                                    </div>
                                </div>

                                <!-- Role -->
                                <div
                                    v-if="availableRoles.length > 0"
                                    class="col-span-6 lg:col-span-4"
                                >
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Role</label
                                    >
                                    <div
                                        v-if="addMemberForm.errors.role"
                                        class="mt-2 text-sm text-red-600"
                                    >
                                        {{ addMemberForm.errors.role }}
                                    </div>

                                    <div
                                        class="relative z-0 mt-1 cursor-pointer rounded-lg border border-gray-200"
                                    >
                                        <button
                                            v-for="(
                                                role, index
                                            ) in availableRoles"
                                            :key="role.key"
                                            type="button"
                                            class="relative inline-flex w-full rounded-lg px-4 py-3 focus:z-10 focus:border-brand-500 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                            :class="{
                                                'rounded-t-none border-t border-gray-200':
                                                    index > 0,
                                                'rounded-b-none':
                                                    index <
                                                    availableRoles.length - 1,
                                            }"
                                            @click="
                                                addMemberForm.role = role.key
                                            "
                                        >
                                            <div
                                                :class="{
                                                    'opacity-50':
                                                        addMemberForm.role &&
                                                        addMemberForm.role !==
                                                            role.key,
                                                }"
                                            >
                                                <div class="flex items-center">
                                                    <div
                                                        class="text-sm text-gray-600"
                                                        :class="{
                                                            'font-semibold':
                                                                addMemberForm.role ===
                                                                role.key,
                                                        }"
                                                    >
                                                        {{ role.name }}
                                                    </div>
                                                    <svg
                                                        v-if="
                                                            addMemberForm.role ===
                                                            role.key
                                                        "
                                                        class="ms-2 size-5 text-green-400"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke-width="1.5"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                        />
                                                    </svg>
                                                </div>
                                                <div
                                                    class="mt-2 text-start text-xs text-gray-600"
                                                >
                                                    {{ role.description }}
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-end bg-gray-50 px-4 py-3 text-end sm:px-6"
                        >
                            <span
                                v-show="recentlyAdded"
                                class="me-3 text-sm text-gray-600"
                            >
                                Added.
                            </span>

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700 focus:bg-gray-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none active:bg-gray-900"
                                :disabled="addMemberForm.processing"
                            >
                                Add
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pending Invitations -->
    <div
        v-if="team.team_invitations.length > 0 && permissions.canAddTeamMembers"
    >
        <div class="hidden sm:block">
            <div class="py-8">
                <div class="border-t border-gray-200" />
            </div>
        </div>

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Pending Team Invitations
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        These people have been invited to your team and have
                        been sent an invitation email. They may join the team by
                        accepting the email invitation.
                    </p>
                </div>
            </div>

            <div class="mt-5 md:col-span-2 md:mt-0">
                <div class="overflow-hidden bg-white shadow sm:rounded-md">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="space-y-6">
                            <div
                                v-for="invitation in team.team_invitations"
                                :key="invitation.id"
                                class="flex items-center justify-between"
                            >
                                <div class="text-gray-600">
                                    {{ invitation.email }}
                                </div>
                                <div class="flex items-center">
                                    <button
                                        v-if="permissions.canRemoveTeamMembers"
                                        class="ms-6 cursor-pointer text-sm text-red-500 focus:outline-none"
                                        @click="cancelInvitation(invitation.id)"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members -->
    <div v-if="team.users.length > 0">
        <div class="hidden sm:block">
            <div class="py-8">
                <div class="border-t border-gray-200" />
            </div>
        </div>

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Team Members
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        All of the people that are part of this team.
                    </p>
                </div>
            </div>

            <div class="mt-5 md:col-span-2 md:mt-0">
                <div class="overflow-hidden bg-white shadow sm:rounded-md">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="space-y-6">
                            <div
                                v-for="member in team.users"
                                :key="member.id"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center">
                                    <img
                                        class="size-8 rounded-full object-cover"
                                        :src="member.profile_photo_url"
                                        :alt="member.name"
                                    />
                                    <div class="ms-4">{{ member.name }}</div>
                                </div>

                                <div class="flex items-center">
                                    <!-- Role -->
                                    <button
                                        v-if="
                                            permissions.canUpdateTeamMembers &&
                                            hasRoles
                                        "
                                        class="ms-2 text-sm text-gray-400 underline"
                                        @click="manageRole(member)"
                                    >
                                        {{
                                            findRoleName(member.membership.role)
                                        }}
                                    </button>
                                    <div
                                        v-else-if="hasRoles"
                                        class="ms-2 text-sm text-gray-400"
                                    >
                                        {{
                                            findRoleName(member.membership.role)
                                        }}
                                    </div>

                                    <!-- Leave -->
                                    <button
                                        v-if="currentUserId === member.id"
                                        class="ms-6 cursor-pointer text-sm text-red-500"
                                        @click="confirmLeavingTeam"
                                    >
                                        Leave
                                    </button>

                                    <!-- Remove -->
                                    <button
                                        v-else-if="
                                            permissions.canRemoveTeamMembers
                                        "
                                        class="ms-6 cursor-pointer text-sm text-red-500"
                                        @click="
                                            confirmTeamMemberRemoval(member.id)
                                        "
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Management Modal -->
    <Teleport to="body">
        <div v-if="managingRoleFor" class="fixed inset-0 z-50 overflow-y-auto">
            <div
                class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0"
            >
                <div
                    class="bg-opacity-75 fixed inset-0 bg-gray-500 transition-opacity"
                    @click="managingRoleFor = null"
                />
                <span
                    class="hidden sm:inline-block sm:h-screen sm:align-middle"
                    aria-hidden="true"
                    >&#8203;</span
                >
                <div
                    class="relative inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Manage Role
                        </h3>
                        <div
                            class="relative z-0 mt-4 cursor-pointer rounded-lg border border-gray-200"
                        >
                            <button
                                v-for="(role, index) in availableRoles"
                                :key="role.key"
                                type="button"
                                class="relative inline-flex w-full rounded-lg px-4 py-3 focus:z-10 focus:border-brand-500 focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                :class="{
                                    'rounded-t-none border-t border-gray-200':
                                        index > 0,
                                    'rounded-b-none':
                                        index < availableRoles.length - 1,
                                }"
                                @click="currentRole = role.key"
                            >
                                <div
                                    :class="{
                                        'opacity-50': currentRole !== role.key,
                                    }"
                                >
                                    <div class="flex items-center">
                                        <div
                                            class="text-sm text-gray-600"
                                            :class="{
                                                'font-semibold':
                                                    currentRole === role.key,
                                            }"
                                        >
                                            {{ role.name }}
                                        </div>
                                        <svg
                                            v-if="currentRole === role.key"
                                            class="ms-2 size-5 text-green-400"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                    <div
                                        class="mt-2 text-start text-xs text-gray-600"
                                    >
                                        {{ role.description }}
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                    <div
                        class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
                    >
                        <button
                            type="button"
                            class="inline-flex w-full justify-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-gray-700 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="updateRole"
                        >
                            Save
                        </button>
                        <button
                            type="button"
                            class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="managingRoleFor = null"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Leave Team Confirmation -->
    <ConfirmationModal
        :show="confirmingLeavingTeam"
        title="Leave Team"
        message="Are you sure you would like to leave this team?"
        confirm-label="Leave"
        :destructive="true"
        @confirm="leaveTeam"
        @cancel="confirmingLeavingTeam = false"
    />

    <!-- Remove Member Confirmation -->
    <ConfirmationModal
        :show="confirmingRemoval"
        title="Remove Team Member"
        message="Are you sure you would like to remove this person from the team?"
        confirm-label="Remove"
        :destructive="true"
        @confirm="removeTeamMember"
        @cancel="
            confirmingRemoval = false;
            memberBeingRemoved = null;
        "
    />
</template>
