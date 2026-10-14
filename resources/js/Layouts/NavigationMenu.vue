<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { dashboard, logout } from '@/routes';
import { index as customersIndex } from '@/routes/customers';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as settingsIndex } from '@/routes/settings';
import { index as organizationsIndex } from '@/routes/organizations';
import { create as teamsCreate } from '@/routes/teams';
import { update as switchTeam } from '@/routes/current-team';
import { show as profileShow } from '@/routes/profile';
import { usePermissions } from '@/composables/usePermissions';
import AppearanceSwitch from '@/Components/AppearanceSwitch.vue';
import AppearanceToggle from '@/Components/AppearanceToggle.vue';
import AppLogo from '@/Components/AppLogo.vue';

interface TeamSummary {
    id: number;
    name: string;
    company_name: string | null;
}

const page = usePage();
const showMobileMenu = ref(false);
const showUserDropdown = ref(false);

const auth = computed(
    () =>
        (page.props.auth ?? {}) as {
            user: {
                id: number;
                name: string;
                email: string;
                profile_photo_url: string;
                all_teams: TeamSummary[];
            } | null;
            currentTeam: {
                id: number;
                name: string;
                company_name: string | null;
            } | null;
        },
);

const teams = computed<TeamSummary[]>(() => auth.value.user?.all_teams ?? []);
const hasMultipleTeams = computed(() => teams.value.length > 1);

const { canManage } = usePermissions();

function handleLogout() {
    router.flushAll();
    router.post(logout.url());
}

function isCurrentPath(path: string): boolean {
    return page.url.startsWith(path);
}

function selectTeam(teamId: number) {
    showUserDropdown.value = false;
    if (auth.value.currentTeam?.id === teamId) {
        return;
    }
    router.put(
        switchTeam.url(),
        { team_id: teamId },
        { preserveScroll: false },
    );
}

// Close mobile menu and dropdowns on Inertia navigation
router.on('navigate', () => {
    showMobileMenu.value = false;
    showUserDropdown.value = false;
});

// Close dropdowns when clicking outside
function handleClickOutside(event: MouseEvent) {
    const target = event.target as HTMLElement;

    if (showUserDropdown.value && !target.closest('[data-user-dropdown]')) {
        showUserDropdown.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <nav class="border-b border-border bg-card">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 justify-between">
                <!-- Left side -->
                <div class="flex">
                    <div class="flex shrink-0 items-center">
                        <Link
                            :href="dashboard.url()"
                            class="flex items-center"
                            aria-label="InvoiceInk home"
                        >
                            <AppLogo class="h-8 w-auto text-foreground" />
                        </Link>
                    </div>

                    <div
                        class="hidden space-x-4 md:-my-px md:ml-4 md:flex lg:ml-6 lg:space-x-6"
                    >
                        <Link
                            :href="dashboard.url()"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm leading-5 font-medium transition"
                            :class="
                                isCurrentPath('/dashboard')
                                    ? 'border-primary/60 text-foreground'
                                    : 'border-transparent text-muted-foreground hover:border-input hover:text-soft-foreground'
                            "
                        >
                            Dashboard
                        </Link>
                        <Link
                            :href="invoicesIndex.url()"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm leading-5 font-medium transition"
                            :class="
                                isCurrentPath('/invoices') ||
                                isCurrentPath('/estimates')
                                    ? 'border-primary/60 text-foreground'
                                    : 'border-transparent text-muted-foreground hover:border-input hover:text-soft-foreground'
                            "
                        >
                            Invoices
                        </Link>
                        <Link
                            :href="customersIndex.url()"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm leading-5 font-medium transition"
                            :class="
                                isCurrentPath('/customers')
                                    ? 'border-primary/60 text-foreground'
                                    : 'border-transparent text-muted-foreground hover:border-input hover:text-soft-foreground'
                            "
                        >
                            Customers
                        </Link>
                        <Link
                            :href="organizationsIndex.url()"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm leading-5 font-medium transition"
                            :class="
                                isCurrentPath('/organizations')
                                    ? 'border-primary/60 text-foreground'
                                    : 'border-transparent text-muted-foreground hover:border-input hover:text-soft-foreground'
                            "
                        >
                            Organizations
                        </Link>
                        <Link
                            v-if="canManage"
                            :href="settingsIndex.url()"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm leading-5 font-medium transition"
                            :class="
                                isCurrentPath('/settings') ||
                                isCurrentPath('/email-templates')
                                    ? 'border-primary/60 text-foreground'
                                    : 'border-transparent text-muted-foreground hover:border-input hover:text-soft-foreground'
                            "
                        >
                            Settings
                        </Link>
                    </div>
                </div>

                <!-- Right side -->
                <div class="hidden md:ml-4 md:flex md:items-center lg:ml-6">
                    <AppearanceSwitch />
                    <div
                        class="relative ml-2 shrink-0 lg:ml-3"
                        data-user-dropdown
                    >
                        <button
                            class="flex items-center gap-2 rounded-full bg-card text-sm focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                            @click.stop="showUserDropdown = !showUserDropdown"
                        >
                            <img
                                v-if="auth.user?.profile_photo_url"
                                class="h-8 w-8 rounded-full object-cover ring-2 ring-border"
                                :src="auth.user.profile_photo_url"
                                :alt="auth.user.name"
                            />
                            <span
                                v-else
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary-soft ring-2 ring-primary-soft"
                            >
                                <span
                                    class="text-sm font-semibold text-primary"
                                >
                                    {{
                                        auth.user?.name
                                            ?.charAt(0)
                                            ?.toUpperCase()
                                    }}
                                </span>
                            </span>
                            <svg
                                class="h-3.5 w-3.5 text-faint-foreground transition-transform duration-150"
                                :class="{ 'rotate-180': showUserDropdown }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div
                            v-show="showUserDropdown"
                            class="absolute right-0 z-50 mt-2 w-64 origin-top-right rounded-xl border border-border bg-popover text-popover-foreground shadow-lg"
                        >
                            <!-- User info header -->
                            <div class="border-b border-border px-4 py-3">
                                <p
                                    class="truncate text-sm font-medium text-foreground"
                                >
                                    {{ auth.user?.name }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{ auth.user?.email }}
                                </p>
                            </div>

                            <!--
                                Organization switching lives here rather than as
                                its own control in the bar. It cost ~167px of a
                                ~940px nav, which is what forced the whole nav to
                                a 1024px breakpoint; moving it lets the full nav
                                fit from 768px. The current organization is still
                                named, so the context is not lost.
                            -->
                            <div
                                v-if="auth.currentTeam"
                                class="border-b border-border"
                                data-team-dropdown
                            >
                                <div
                                    class="px-4 py-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    Organization
                                </div>
                                <div class="max-h-56 overflow-y-auto pb-1">
                                    <button
                                        v-for="team in teams"
                                        :key="team.id"
                                        type="button"
                                        class="flex w-full items-center justify-between gap-2 px-4 py-2 text-left text-sm text-soft-foreground hover:bg-muted"
                                        :class="
                                            auth.currentTeam?.id === team.id
                                                ? 'font-medium text-foreground'
                                                : ''
                                        "
                                        @click="selectTeam(team.id)"
                                    >
                                        <span class="truncate">{{
                                            team.name
                                        }}</span>
                                        <svg
                                            v-if="
                                                auth.currentTeam?.id === team.id
                                            "
                                            class="h-4 w-4 shrink-0 text-primary"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <div class="pb-1">
                                    <Link
                                        :href="teamsCreate.url()"
                                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary hover:bg-muted"
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
                                        Create organization
                                    </Link>
                                    <Link
                                        :href="organizationsIndex.url()"
                                        class="block px-4 py-2 text-sm text-soft-foreground hover:bg-muted"
                                    >
                                        Manage organizations
                                    </Link>
                                </div>
                            </div>
                            <div class="border-b border-border px-4 py-3">
                                <AppearanceToggle />
                            </div>
                            <div class="py-1">
                                <Link
                                    :href="profileShow.url()"
                                    class="flex items-center gap-2.5 px-4 py-2 text-sm text-soft-foreground hover:bg-muted"
                                >
                                    <svg
                                        class="h-4 w-4 text-faint-foreground"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                                        />
                                    </svg>
                                    Profile
                                </Link>
                                <button
                                    class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-soft-foreground hover:bg-muted"
                                    @click="handleLogout"
                                >
                                    <svg
                                        class="h-4 w-4 text-faint-foreground"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                                        />
                                    </svg>
                                    Log Out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="-mr-2 flex items-center gap-1 md:hidden">
                    <AppearanceSwitch />
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-2 text-faint-foreground transition hover:bg-secondary hover:text-muted-foreground focus:bg-secondary focus:text-muted-foreground focus:outline-none"
                        aria-label="Toggle navigation menu"
                        aria-controls="mobile-nav-panel"
                        :aria-expanded="showMobileMenu"
                        @click="showMobileMenu = !showMobileMenu"
                    >
                        <svg
                            class="h-6 w-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                :class="{
                                    hidden: showMobileMenu,
                                    'inline-flex': !showMobileMenu,
                                }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                :class="{
                                    hidden: !showMobileMenu,
                                    'inline-flex': showMobileMenu,
                                }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-nav-panel" v-show="showMobileMenu" class="md:hidden">
            <div class="space-y-1 pt-2 pb-3">
                <Link
                    :href="dashboard.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/dashboard')
                            ? 'border-primary/60 bg-primary-soft/50 text-primary-soft-foreground'
                            : 'border-transparent text-soft-foreground hover:border-input hover:bg-muted hover:text-foreground'
                    "
                    >Dashboard</Link
                >
                <Link
                    :href="invoicesIndex.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/invoices') ||
                        isCurrentPath('/estimates')
                            ? 'border-primary/60 bg-primary-soft/50 text-primary-soft-foreground'
                            : 'border-transparent text-soft-foreground hover:border-input hover:bg-muted hover:text-foreground'
                    "
                    >Invoices</Link
                >
                <Link
                    :href="customersIndex.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/customers')
                            ? 'border-primary/60 bg-primary-soft/50 text-primary-soft-foreground'
                            : 'border-transparent text-soft-foreground hover:border-input hover:bg-muted hover:text-foreground'
                    "
                    >Customers</Link
                >
                <Link
                    :href="organizationsIndex.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/organizations')
                            ? 'border-primary/60 bg-primary-soft/50 text-primary-soft-foreground'
                            : 'border-transparent text-soft-foreground hover:border-input hover:bg-muted hover:text-foreground'
                    "
                    >Organizations</Link
                >
                <Link
                    v-if="canManage"
                    :href="settingsIndex.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/settings') ||
                        isCurrentPath('/email-templates')
                            ? 'border-primary/60 bg-primary-soft/50 text-primary-soft-foreground'
                            : 'border-transparent text-soft-foreground hover:border-input hover:bg-muted hover:text-foreground'
                    "
                    >Settings</Link
                >
            </div>
            <div
                v-if="auth.currentTeam"
                class="border-t border-border pt-4 pb-1"
            >
                <div
                    class="px-4 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{
                        hasMultipleTeams
                            ? 'Switch organization'
                            : 'Current organization'
                    }}
                </div>
                <div class="mt-2 space-y-1">
                    <button
                        v-for="team in teams"
                        :key="team.id"
                        type="button"
                        class="flex w-full items-center justify-between gap-2 px-4 py-2 text-left text-base font-medium text-soft-foreground hover:bg-muted hover:text-foreground"
                        @click="selectTeam(team.id)"
                    >
                        <span class="truncate">{{ team.name }}</span>
                        <svg
                            v-if="auth.currentTeam?.id === team.id"
                            class="h-4 w-4 text-primary"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </button>
                    <Link
                        :href="teamsCreate.url()"
                        class="block px-4 py-2 text-base font-medium text-primary hover:bg-muted"
                    >
                        + Create organization
                    </Link>
                    <Link
                        :href="organizationsIndex.url()"
                        class="block px-4 py-2 text-base font-medium text-soft-foreground hover:bg-muted hover:text-foreground"
                    >
                        Manage organizations
                    </Link>
                </div>
            </div>
            <div class="border-t border-border px-4 pt-4 pb-3">
                <AppearanceToggle />
            </div>
            <div class="border-t border-border pt-4 pb-1">
                <div class="px-4">
                    <div class="text-base font-medium text-foreground">
                        {{ auth.user?.name }}
                    </div>
                    <div class="text-sm font-medium text-muted-foreground">
                        {{ auth.user?.email }}
                    </div>
                </div>
                <div class="mt-3 space-y-1">
                    <Link
                        :href="profileShow.url()"
                        class="block px-4 py-2 text-base font-medium text-soft-foreground hover:bg-muted hover:text-foreground"
                        >Profile</Link
                    >
                    <button
                        class="block w-full px-4 py-2 text-left text-base font-medium text-soft-foreground hover:bg-muted hover:text-foreground"
                        @click="handleLogout"
                    >
                        Log Out
                    </button>
                </div>
            </div>
        </div>
    </nav>
</template>
