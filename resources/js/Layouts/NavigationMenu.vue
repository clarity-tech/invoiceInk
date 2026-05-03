<script setup lang="ts">
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { dashboard, logout } from '@/routes';
import { index as customersIndex } from '@/routes/customers';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as settingsIndex } from '@/routes/settings';
import { index as organizationsIndex } from '@/routes/organizations';
import { show as profileShow } from '@/routes/profile';

const appName = import.meta.env.VITE_APP_NAME || 'InvoiceInk';
const page = usePage();
const showMobileMenu = ref(false);
const showUserDropdown = ref(false);

const auth = (page.props.auth ?? {}) as {
    user: {
        id: number;
        name: string;
        email: string;
        profile_photo_url: string;
    } | null;
    currentTeam: {
        id: number;
        name: string;
        company_name: string | null;
    } | null;
};

function handleLogout() {
    router.flushAll();
    router.post(logout.url());
}

function isCurrentPath(path: string): boolean {
    return page.url.startsWith(path);
}

// Close mobile menu and dropdown on Inertia navigation
router.on('navigate', () => {
    showMobileMenu.value = false;
    showUserDropdown.value = false;
});

// Close dropdown when clicking outside
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
    <nav class="border-b border-gray-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 justify-between">
                <!-- Left side -->
                <div class="flex">
                    <div class="flex shrink-0 items-center">
                        <Link :href="dashboard.url()">
                            <span class="text-xl font-bold text-brand-600">{{
                                appName
                            }}</span>
                        </Link>
                    </div>

                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                        <Link
                            :href="dashboard.url()"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm leading-5 font-medium transition"
                            :class="
                                isCurrentPath('/dashboard')
                                    ? 'border-brand-400 text-gray-900'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
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
                                    ? 'border-brand-400 text-gray-900'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            "
                        >
                            Invoices
                        </Link>
                        <Link
                            :href="customersIndex.url()"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm leading-5 font-medium transition"
                            :class="
                                isCurrentPath('/customers')
                                    ? 'border-brand-400 text-gray-900'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            "
                        >
                            Customers
                        </Link>
                        <Link
                            :href="organizationsIndex.url()"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm leading-5 font-medium transition"
                            :class="
                                isCurrentPath('/organizations')
                                    ? 'border-brand-400 text-gray-900'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            "
                        >
                            Organizations
                        </Link>
                        <Link
                            :href="settingsIndex.url()"
                            class="inline-flex items-center border-b-2 px-1 pt-1 text-sm leading-5 font-medium transition"
                            :class="
                                isCurrentPath('/settings') ||
                                isCurrentPath('/numbering-series') ||
                                isCurrentPath('/email-templates')
                                    ? 'border-brand-400 text-gray-900'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            "
                        >
                            Settings
                        </Link>
                    </div>
                </div>

                <!-- Right side -->
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <span
                        v-if="auth.currentTeam"
                        class="mr-4 text-sm text-gray-500"
                    >
                        {{
                            auth.currentTeam.company_name ||
                            auth.currentTeam.name
                        }}
                    </span>

                    <div class="relative ml-3" data-user-dropdown>
                        <button
                            class="flex items-center gap-2 rounded-full bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:outline-none"
                            @click.stop="showUserDropdown = !showUserDropdown"
                        >
                            <img
                                v-if="auth.user?.profile_photo_url"
                                class="h-8 w-8 rounded-full object-cover ring-2 ring-gray-100"
                                :src="auth.user.profile_photo_url"
                                :alt="auth.user.name"
                            />
                            <span
                                v-else
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 ring-2 ring-brand-50"
                            >
                                <span
                                    class="text-sm font-semibold text-brand-600"
                                >
                                    {{
                                        auth.user?.name
                                            ?.charAt(0)
                                            ?.toUpperCase()
                                    }}
                                </span>
                            </span>
                            <svg
                                class="h-3.5 w-3.5 text-gray-400 transition-transform duration-150"
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
                            class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-xl border border-gray-100 bg-white shadow-lg"
                        >
                            <!-- User info header -->
                            <div class="border-b border-gray-100 px-4 py-3">
                                <p
                                    class="truncate text-sm font-medium text-gray-900"
                                >
                                    {{ auth.user?.name }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-xs text-gray-500"
                                >
                                    {{ auth.user?.email }}
                                </p>
                            </div>
                            <div class="py-1">
                                <Link
                                    :href="profileShow.url()"
                                    class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    <svg
                                        class="h-4 w-4 text-gray-400"
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
                                    class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                    @click="handleLogout"
                                >
                                    <svg
                                        class="h-4 w-4 text-gray-400"
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
                <div class="-mr-2 flex items-center sm:hidden">
                    <button
                        class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
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
        <div v-show="showMobileMenu" class="sm:hidden">
            <div class="space-y-1 pt-2 pb-3">
                <Link
                    :href="dashboard.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/dashboard')
                            ? 'border-brand-400 bg-brand-50 text-brand-700'
                            : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800'
                    "
                    >Dashboard</Link
                >
                <Link
                    :href="invoicesIndex.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/invoices') ||
                        isCurrentPath('/estimates')
                            ? 'border-brand-400 bg-brand-50 text-brand-700'
                            : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800'
                    "
                    >Invoices</Link
                >
                <Link
                    :href="customersIndex.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/customers')
                            ? 'border-brand-400 bg-brand-50 text-brand-700'
                            : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800'
                    "
                    >Customers</Link
                >
                <Link
                    :href="organizationsIndex.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/organizations')
                            ? 'border-brand-400 bg-brand-50 text-brand-700'
                            : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800'
                    "
                    >Organizations</Link
                >
                <Link
                    :href="settingsIndex.url()"
                    class="block border-l-4 py-2 pr-4 pl-3 text-base font-medium"
                    :class="
                        isCurrentPath('/settings') ||
                        isCurrentPath('/numbering-series') ||
                        isCurrentPath('/email-templates')
                            ? 'border-brand-400 bg-brand-50 text-brand-700'
                            : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800'
                    "
                    >Settings</Link
                >
            </div>
            <div class="border-t border-gray-200 pt-4 pb-1">
                <div class="px-4">
                    <div class="text-base font-medium text-gray-800">
                        {{ auth.user?.name }}
                    </div>
                    <div class="text-sm font-medium text-gray-500">
                        {{ auth.user?.email }}
                    </div>
                </div>
                <div class="mt-3 space-y-1">
                    <Link
                        :href="profileShow.url()"
                        class="block px-4 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800"
                        >Profile</Link
                    >
                    <button
                        class="block w-full px-4 py-2 text-left text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800"
                        @click="handleLogout"
                    >
                        Log Out
                    </button>
                </div>
            </div>
        </div>
    </nav>
</template>
