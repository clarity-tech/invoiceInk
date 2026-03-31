<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import { computed, type Component } from 'vue';
import { Settings, MapPin, Landmark, Image, Hash } from 'lucide-vue-next';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import AppLayout from '@/Layouts/AppLayout.vue';
import EditBasicsForm from './Partials/EditBasicsForm.vue';
import EditLocationForm from './Partials/EditLocationForm.vue';
import EditBankDetailsForm from './Partials/EditBankDetailsForm.vue';
import EditLogoForm from './Partials/EditLogoForm.vue';
import EditNumberingForm from './Partials/EditNumberingForm.vue';
import type { Organization, CountryInfo } from './types';
import type { InvoiceNumberingSeries } from '@/types';

const props = defineProps<{
    organization: Organization;
    countries: CountryInfo[];
    currencies: Record<string, string>;
    tab: string;
    numberingSeries: InvoiceNumberingSeries[];
    resetFrequencyOptions: Record<string, string>;
}>();

const currentTab = computed(() => props.tab || 'basics');

const tabs: { key: string; label: string; icon: Component }[] = [
    { key: 'basics', label: 'General', icon: Settings },
    { key: 'location', label: 'Location', icon: MapPin },
    { key: 'bank', label: 'Bank Details', icon: Landmark },
    { key: 'logo', label: 'Logo', icon: Image },
    { key: 'numbering', label: 'Numbering', icon: Hash },
];

function switchTab(key: string) {
    router.get(
        `/organizations/${props.organization.id}/edit`,
        { tab: key },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}
</script>

<template>
    <AppLayout :title="`Edit ${organization.name}`">
        <template #header>
            <Breadcrumb>
                <BreadcrumbItem>
                    <BreadcrumbLink href="/organizations">
                        Organizations
                    </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                    <BreadcrumbLink :href="`/organizations/${organization.id}`">
                        {{ organization.name }}
                    </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                    <BreadcrumbPage>Settings</BreadcrumbPage>
                </BreadcrumbItem>
            </Breadcrumb>
        </template>

        <div class="px-4 py-4 sm:px-0">
            <!-- Tab Navigation + Content -->
            <div class="flex flex-col gap-6 lg:flex-row">
                <!-- Sidebar Tabs -->
                <nav class="w-full shrink-0 lg:w-56">
                    <ul class="flex gap-1 overflow-x-auto lg:flex-col">
                        <li v-for="tab in tabs" :key="tab.key">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition"
                                :class="
                                    currentTab === tab.key
                                        ? 'bg-brand-50 text-brand-700'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                "
                                @click="switchTab(tab.key)"
                            >
                                <component
                                    :is="tab.icon"
                                    class="size-5 shrink-0"
                                />
                                <span class="whitespace-nowrap">{{
                                    tab.label
                                }}</span>
                            </button>
                        </li>
                    </ul>
                </nav>

                <!-- Tab Content -->
                <div class="min-w-0 flex-1">
                    <EditBasicsForm
                        v-if="currentTab === 'basics'"
                        :organization="organization"
                        :countries="countries"
                        :currencies="currencies"
                    />
                    <EditLocationForm
                        v-else-if="currentTab === 'location'"
                        :organization="organization"
                        :countries="countries"
                    />
                    <EditBankDetailsForm
                        v-else-if="currentTab === 'bank'"
                        :organization="organization"
                    />
                    <EditLogoForm
                        v-else-if="currentTab === 'logo'"
                        :organization="organization"
                    />
                    <EditNumberingForm
                        v-else-if="currentTab === 'numbering'"
                        :organization-id="organization.id"
                        :series="numberingSeries"
                        :reset-frequency-options="resetFrequencyOptions"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
