<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { create as teamsCreate } from '@/routes/teams';

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
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl leading-tight font-semibold text-foreground">
                    Organizations
                </h2>
                <Link
                    :href="teamsCreate.url()"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15"
                        />
                    </svg>
                    New organization
                </Link>
            </div>
        </template>

        <div class="px-4 py-4 sm:px-0">
            <p class="mb-6 text-sm text-muted-foreground">
                Select an organization to view or manage its settings.
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="org in organizations.data"
                    :key="org.id"
                    :href="`/organizations/${org.id}`"
                    class="group relative rounded-xl border border-border bg-card p-6 shadow-sm transition hover:border-primary/40 hover:shadow-md"
                >
                    <div class="flex items-start gap-4">
                        <div
                            v-if="org.logo_url"
                            class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-paper"
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
                                class="truncate text-base font-semibold text-foreground group-hover:text-primary-soft-foreground"
                            >
                                {{ org.name }}
                            </h3>
                            <p
                                v-if="
                                    org.company_name &&
                                    org.company_name !== org.name
                                "
                                class="truncate text-sm text-muted-foreground"
                            >
                                {{ org.company_name }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-center gap-3 text-xs text-muted-foreground"
                    >
                        <span
                            v-if="org.currency"
                            class="inline-flex items-center rounded-full bg-secondary px-2 py-0.5 font-medium text-soft-foreground"
                            >{{ org.currency }}</span
                        >
                        <span v-if="org.primary_location">
                            {{ org.primary_location.city }},
                            {{ org.primary_location.state }}
                        </span>
                        <span v-else class="text-faint-foreground italic"
                            >No location</span
                        >
                    </div>

                    <!-- Arrow indicator -->
                    <div
                        class="absolute top-6 right-6 text-faint-foreground transition group-hover:text-primary"
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
                class="rounded-xl border-2 border-dashed border-input py-16 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    No organizations found.
                </p>
                <Link
                    :href="teamsCreate.url()"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90"
                >
                    Create your first organization
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
