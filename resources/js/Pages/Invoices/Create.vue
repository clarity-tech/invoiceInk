<script setup lang="ts">
import { Sparkles } from 'lucide-vue-next';
import AiDraftButton from '@/Components/Invoice/AiDraftButton.vue';
import InvoiceForm from '@/Components/Invoice/InvoiceForm.vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import AppLayout from '@/Layouts/AppLayout.vue';
import type {
    Customer,
    Location,
    TaxRateOption,
    InvoiceNumberingSeries,
    InvoiceAttachment,
    InvoiceFormDefaults,
} from '@/types';

defineProps<{
    type: 'invoice' | 'estimate';
    customers: Customer[];
    organizationLocations: Location[];
    taxOptions: TaxRateOption[];
    numberingSeries: InvoiceNumberingSeries[];
    statusOptions: Record<string, string>;
    taxLabels: Record<string, string>;
    attachments: InvoiceAttachment[];
    indianStates: Record<string, string>;
    defaults: InvoiceFormDefaults;
    aiDrafting: boolean;
    /** Set when the form was pre-filled from a document. */
    draft: { source: string; warnings: string[] } | null;
}>();
</script>

<template>
    <AppLayout
        :title="`Create ${type === 'estimate' ? 'Estimate' : 'Invoice'}`"
    >
        <template #header>
            <Breadcrumb>
                <BreadcrumbItem>
                    <BreadcrumbLink href="/invoices"> Invoices </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                    <BreadcrumbPage>
                        Create
                        {{ type === 'estimate' ? 'Estimate' : 'Invoice' }}
                    </BreadcrumbPage>
                </BreadcrumbItem>
            </Breadcrumb>

            <!--
                The same slot the edit page carries, so the form can teleport
                its Status field to the same place on both. Without it the
                teleport would have nowhere to land.
            -->
            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                <h2 class="text-lg font-semibold text-foreground">
                    New {{ type === 'estimate' ? 'Estimate' : 'Invoice' }}
                </h2>
                <div id="document-status-control" />
                <div v-if="aiDrafting" class="ml-auto">
                    <AiDraftButton :type="type" />
                </div>
            </div>
        </template>

        <div class="px-4 sm:px-0">
            <div
                v-if="draft"
                role="status"
                class="mb-4 rounded-lg border border-primary/25 bg-primary-soft/40 px-4 py-3 text-sm"
            >
                <p class="flex items-center gap-2 font-medium text-foreground">
                    <Sparkles class="size-4 shrink-0 text-primary" />
                    Drafted from {{ draft.source }}
                </p>
                <p class="mt-0.5 text-soft-foreground">
                    Check every field before saving. Nothing is saved yet.
                </p>
                <ul
                    v-if="draft.warnings.length"
                    class="mt-2 list-disc space-y-1 pl-5 text-warning-soft-foreground"
                >
                    <li v-for="warning in draft.warnings" :key="warning">
                        {{ warning }}
                    </li>
                </ul>
            </div>

            <InvoiceForm
                mode="create"
                :type="type"
                :customers="customers"
                :organization-locations="organizationLocations"
                :tax-options="taxOptions"
                :numbering-series="numberingSeries"
                :status-options="statusOptions"
                :tax-labels="taxLabels"
                :attachments="attachments"
                :indian-states="indianStates"
                :defaults="defaults"
            />
        </div>
    </AppLayout>
</template>
