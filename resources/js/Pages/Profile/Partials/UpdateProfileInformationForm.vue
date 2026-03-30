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
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Profile Information
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    Update your account's profile information and email address.
                </p>
            </div>
        </div>

        <div class="mt-5 md:col-span-2 md:mt-0">
            <form @submit.prevent="updateProfileInformation">
                <div class="overflow-hidden bg-white shadow sm:rounded-md">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-6 gap-6">
                            <!-- Profile Photo -->
                            <div
                                v-if="managesProfilePhotos"
                                class="col-span-6 sm:col-span-4"
                            >
                                <input
                                    ref="photoInput"
                                    type="file"
                                    class="hidden"
                                    accept="image/*"
                                    @change="updatePhotoPreview"
                                />

                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Photo</label
                                >

                                <!-- Current Profile Photo -->
                                <div v-show="!photoPreview" class="mt-2">
                                    <img
                                        :src="user.profile_photo_url"
                                        :alt="user.name"
                                        class="size-20 rounded-full object-cover"
                                    />
                                </div>

                                <!-- New Profile Photo Preview -->
                                <div v-show="photoPreview" class="mt-2">
                                    <span
                                        class="block size-20 rounded-full bg-cover bg-center bg-no-repeat"
                                        :style="
                                            'background-image: url(\'' +
                                            photoPreview +
                                            '\');'
                                        "
                                    />
                                </div>

                                <button
                                    type="button"
                                    class="me-2 mt-2 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase shadow-sm hover:bg-gray-50"
                                    @click.prevent="selectNewPhoto"
                                >
                                    Select A New Photo
                                </button>

                                <button
                                    v-if="user.profile_photo_path"
                                    type="button"
                                    class="mt-2 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase shadow-sm hover:bg-gray-50"
                                    @click.prevent="deleteProfilePhoto"
                                >
                                    Remove Photo
                                </button>

                                <div
                                    v-if="form.errors.photo"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.photo }}
                                </div>
                            </div>

                            <!-- Name -->
                            <div class="col-span-6 sm:col-span-4">
                                <label
                                    for="name"
                                    class="block text-sm font-medium text-gray-700"
                                    >Name</label
                                >
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                    required
                                    autocomplete="name"
                                />
                                <div
                                    v-if="form.errors.name"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-span-6 sm:col-span-4">
                                <label
                                    for="email"
                                    class="block text-sm font-medium text-gray-700"
                                    >Email</label
                                >
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                                    required
                                    autocomplete="username"
                                />
                                <div
                                    v-if="form.errors.email"
                                    class="mt-2 text-sm text-red-600"
                                >
                                    {{ form.errors.email }}
                                </div>

                                <div
                                    v-if="!user.email_verified_at"
                                    class="mt-2"
                                >
                                    <p class="text-sm">
                                        Your email address is unverified.
                                        <button
                                            type="button"
                                            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none"
                                            @click.prevent="
                                                sendEmailVerification
                                            "
                                        >
                                            Click here to re-send the
                                            verification email.
                                        </button>
                                    </p>

                                    <p
                                        v-if="verificationLinkSent"
                                        class="mt-2 text-sm font-medium text-green-600"
                                    >
                                        A new verification link has been sent to
                                        your email address.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
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
