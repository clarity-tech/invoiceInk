<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

interface Location {
    city: string;
    state: string;
}

interface Organization {
    id: number;
    name: string;
    company_name: string | null;
    currency: string | null;
    country_code: string | null;
    logo_url: string | null;
    primary_location: Location | null;
    personal_team: boolean;
}

interface PaginatedOrganizations {
    data: Organization[];
    current_page: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    organizations: PaginatedOrganizations;
}>();
</script>

<template>
    <AppLayout title="Organizations">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Organizations
            </h2>
        </template>

        <div class="px-4 py-4 sm:px-0">
            <p class="mb-6 text-sm text-gray-500">
                Select an organization to view or manage its settings.
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="org in organizations.data"
                    :key="org.id"
                    :href="`/organizations/${org.id}`"
                    class="group relative rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-brand-300 hover:shadow-md"
                >
                    <div class="flex items-start gap-4">
                        <div
                            v-if="org.logo_url"
                            class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-white"
                        >
                            <img
                                :src="org.logo_url"
                                :alt="org.name"
                                class="size-full object-contain p-1"
                            />
                        </div>
                        <div
                            v-else
                            class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700"
                        >
                            <span class="text-lg font-bold text-white">{{
                                org.name.charAt(0)
                            }}</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3
                                class="truncate text-base font-semibold text-gray-900 group-hover:text-brand-700"
                            >
                                {{ org.name }}
                            </h3>
                            <p
                                v-if="
                                    org.company_name &&
                                    org.company_name !== org.name
                                "
                                class="truncate text-sm text-gray-500"
                            >
                                {{ org.company_name }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-center gap-3 text-xs text-gray-500"
                    >
                        <span
                            v-if="org.currency"
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-600"
                            >{{ org.currency }}</span
                        >
                        <span v-if="org.primary_location">
                            {{ org.primary_location.city }},
                            {{ org.primary_location.state }}
                        </span>
                        <span v-else class="text-gray-400 italic"
                            >No location</span
                        >
                    </div>

                    <!-- Arrow indicator -->
                    <div
                        class="absolute top-6 right-6 text-gray-300 transition group-hover:text-brand-500"
                    >
                        <svg
                            class="size-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m8.25 4.5 7.5 7.5-7.5 7.5"
                            />
                        </svg>
                    </div>
                </Link>
            </div>

            <!-- Empty state -->
            <div
                v-if="organizations.data.length === 0"
                class="rounded-xl border-2 border-dashed border-gray-300 py-16 text-center"
            >
                <p class="text-sm text-gray-500">No organizations found.</p>
            </div>
        </div>
    </AppLayout>
</template>
