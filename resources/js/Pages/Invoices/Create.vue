<script setup lang="ts">
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
        </template>

        <div class="py-4">
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
