<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { update } from '@/routes/user-profile-information';

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        profile_photo_url: string;
        profile_photo_path: string | null;
        email_verified_at: string | null;
    };
    managesProfilePhotos: boolean;
}>();

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null as File | null,
});

const photoPreview = ref<string | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);
const verificationLinkSent = ref(false);
const recentlySuccessful = ref(false);

function updateProfileInformation(): void {
    form.post(update.url(), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            recentlySuccessful.value = true;
            setTimeout(() => (recentlySuccessful.value = false), 2000);
            clearPhotoFileInput();
        },
    });
}

function selectNewPhoto(): void {
    photoInput.value?.click();
}

function updatePhotoPreview(): void {
    const photo = photoInput.value?.files?.[0];

    if (!photo) {
        return;
    }

    form.photo = photo;

    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(photo);
}

function deleteProfilePhoto(): void {
    router.delete('/user/profile-photo', {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
}

function clearPhotoFileInput(): void {
    if (photoInput.value) {
        photoInput.value.value = '';
    }

    form.photo = null;
    photoPreview.value = null;
}

function sendEmailVerification(): void {
    verificationLinkSent.value = true;
}
</script>

<template>
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="mb-1 text-sm font-semibold tracking-wider text-gray-400 uppercase">
            Profile Information
        </h2>
        <p class="mb-6 text-sm text-gray-500">
            Update your account's profile information and email address.
        </p>

        <form @submit.prevent="updateProfileInformation">
            <div class="space-y-5">
                <!-- Profile Photo -->
                <div v-if="managesProfilePhotos">
                    <input
                        ref="photoInput"
                        type="file"
                        class="hidden"
                        accept="image/*"
                        @change="updatePhotoPreview"
                    />

                    <label class="mb-2 block text-sm font-medium text-gray-700">Photo</label>

                    <div class="flex items-center gap-4">
                        <img
                            v-show="!photoPreview"
                            :src="user.profile_photo_url"
                            :alt="user.name"
                            class="size-16 rounded-full object-cover ring-2 ring-gray-100"
                        />
                        <span
                            v-show="photoPreview"
                            class="block size-16 rounded-full bg-cover bg-center bg-no-repeat ring-2 ring-gray-100"
                            :style="'background-image: url(\'' + photoPreview + '\');'"
                        />
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                                @click.prevent="selectNewPhoto"
                            >
                                Change Photo
                            </button>
                            <button
                                v-if="user.profile_photo_path"
                                type="button"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                                @click.prevent="deleteProfilePhoto"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <div v-if="form.errors.photo" class="mt-2 text-sm text-red-600">
                        {{ form.errors.photo }}
                    </div>
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                        required
                        autocomplete="name"
                    />
                    <div v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                        {{ form.errors.name }}
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                        required
                        autocomplete="username"
                    />
                    <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">
                        {{ form.errors.email }}
                    </div>

                    <div v-if="!user.email_verified_at" class="mt-2">
                        <p class="text-sm text-gray-600">
                            Your email address is unverified.
                            <button
                                type="button"
                                class="rounded text-sm text-brand-600 underline hover:text-brand-800 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none"
                                @click.prevent="sendEmailVerification"
                            >
                                Re-send verification email.
                            </button>
                        </p>
                        <p
                            v-if="verificationLinkSent"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email address.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none disabled:opacity-60"
                    :disabled="form.processing"
                >
                    Save Changes
                </button>
                <span v-show="recentlySuccessful" class="text-sm text-gray-500">Saved.</span>
            </div>
        </form>
    </section>
</template>
