<script setup lang="ts">
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import LogoutOtherSessionsForm from './Partials/LogoutOtherSessionsForm.vue';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Session {
    ip_address: string;
    is_current_device: boolean;
    last_active: string;
    platform: string | null;
    browser: string | null;
    is_desktop: boolean;
}

defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        profile_photo_url: string;
        profile_photo_path: string | null;
        email_verified_at: string | null;
        two_factor_secret: string | null;
        two_factor_confirmed_at: string | null;
    };
    sessions: Session[];
    confirmsTwoFactorAuthentication: boolean;
    canManageTwoFactorAuthentication: boolean;
    canUpdateProfileInformation: boolean;
    canUpdatePassword: boolean;
    hasAccountDeletionFeatures: boolean;
    sessionsEnabled: boolean;
    managesProfilePhotos: boolean;
}>();
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">Profile</h2>
        </template>

        <div class="px-4 py-4 sm:px-0">
            <div class="space-y-6">
                <!-- Row 1: Profile Information | Update Password -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <UpdateProfileInformationForm
                        v-if="canUpdateProfileInformation"
                        :user="user"
                        :manages-profile-photos="managesProfilePhotos"
                    />
                    <UpdatePasswordForm v-if="canUpdatePassword" />
                </div>

                <!-- Row 2: Two-Factor Authentication | Browser Sessions -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <TwoFactorAuthenticationForm
                        v-if="canManageTwoFactorAuthentication"
                        :user="user"
                        :confirms-two-factor-authentication="confirmsTwoFactorAuthentication"
                    />
                    <LogoutOtherSessionsForm v-if="sessionsEnabled" :sessions="sessions" />
                </div>

                <!-- Row 3: Delete Account -->
                <div v-if="hasAccountDeletionFeatures" class="max-w-2xl">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
