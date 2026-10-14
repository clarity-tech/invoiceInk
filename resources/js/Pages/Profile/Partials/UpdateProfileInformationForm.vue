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
    <section class="rounded-xl border border-border bg-card p-6">
        <h2
            class="mb-1 text-sm font-semibold tracking-wider text-faint-foreground uppercase"
        >
            Profile Information
        </h2>
        <p class="mb-6 text-sm text-muted-foreground">
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

                    <label
                        class="mb-2 block text-sm font-medium text-soft-foreground"
                        >Photo</label
                    >

                    <div class="flex items-center gap-4">
                        <img
                            v-show="!photoPreview"
                            :src="user.profile_photo_url"
                            :alt="user.name"
                            class="size-16 rounded-full object-cover ring-2 ring-border"
                        />
                        <span
                            v-show="photoPreview"
                            class="block size-16 rounded-full bg-cover bg-center bg-no-repeat ring-2 ring-border"
                            :style="
                                'background-image: url(\'' +
                                photoPreview +
                                '\');'
                            "
                        />
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-input bg-card px-3 py-1.5 text-xs font-medium text-soft-foreground shadow-sm transition hover:bg-muted"
                                @click.prevent="selectNewPhoto"
                            >
                                Change Photo
                            </button>
                            <button
                                v-if="user.profile_photo_path"
                                type="button"
                                class="rounded-lg border border-input bg-card px-3 py-1.5 text-xs font-medium text-soft-foreground shadow-sm transition hover:bg-muted"
                                @click.prevent="deleteProfilePhoto"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="form.errors.photo"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.photo }}
                    </div>
                </div>

                <!-- Name -->
                <div>
                    <label
                        for="name"
                        class="block text-sm font-medium text-soft-foreground"
                        >Name</label
                    >
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full max-w-md rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        required
                        autocomplete="name"
                    />
                    <div
                        v-if="form.errors.name"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.name }}
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label
                        for="email"
                        class="block text-sm font-medium text-soft-foreground"
                        >Email</label
                    >
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full max-w-md rounded-lg border-input shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                        required
                        autocomplete="username"
                    />
                    <div
                        v-if="form.errors.email"
                        class="mt-2 text-sm text-destructive"
                    >
                        {{ form.errors.email }}
                    </div>

                    <div v-if="!user.email_verified_at" class="mt-2">
                        <p class="text-sm text-soft-foreground">
                            Your email address is unverified.
                            <button
                                type="button"
                                class="rounded text-sm text-primary underline hover:text-primary-soft-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                @click.prevent="sendEmailVerification"
                            >
                                Re-send verification email.
                            </button>
                        </p>
                        <p
                            v-if="verificationLinkSent"
                            class="mt-2 text-sm font-medium text-success"
                        >
                            A new verification link has been sent to your email
                            address.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:opacity-60"
                    :disabled="form.processing"
                >
                    Save Changes
                </button>
                <span
                    v-show="recentlySuccessful"
                    class="text-sm text-muted-foreground"
                    >Saved.</span
                >
            </div>
        </form>
    </section>
</template>
