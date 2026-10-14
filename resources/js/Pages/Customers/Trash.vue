<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { formatDate } from '@/composables/useFormatDate';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Customer } from '@/types';

const { can } = usePermissions();

interface PaginatedCustomers {
    data: (Customer & {
        locations_count: number;
        deleted_at?: string | null;
    })[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    customers: PaginatedCustomers;
}>();

const restoring = ref<number | null>(null);

// Focus lands here after a confirmed delete: the row that opened the
// dialog is removed, so the dialog cannot hand focus back to it.
const listRegion = ref<HTMLElement | null>(null);

function restoreCustomer(customer: Customer) {
    restoring.value = customer.id;
    router.post(
        `/customers/${customer.id}/restore`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                restoring.value = null;
            },
        },
    );
}

const confirmingForceDelete = ref(false);
const customerToForceDelete = ref<Customer | null>(null);

function confirmForceDelete(customer: Customer) {
    customerToForceDelete.value = customer;
    confirmingForceDelete.value = true;
}

function forceDeleteCustomer() {
    if (!customerToForceDelete.value) {
        return;
    }

    const id = customerToForceDelete.value.id;

    confirmingForceDelete.value = false;
    customerToForceDelete.value = null;

    router.delete(`/customers/${id}/force`, {
        preserveScroll: true,
    });
}

function primaryEmail(customer: Customer): string {
    return customer.emails?.[0]?.email ?? '';
}
</script>

<template>
    <AppLayout title="Customers Trash">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-foreground">
                    Customers Trash
                </h2>
                <a
                    href="/customers"
                    class="rounded-md bg-card px-4 py-2 text-sm font-medium text-soft-foreground shadow-sm ring-1 ring-input hover:bg-muted"
                >
                    Back to Customers
                </a>
            </div>
        </template>

        <div class="px-4 sm:px-0">
            <div
                ref="listRegion"
                tabindex="-1"
                aria-label="Trashed customers list"
                class="overflow-x-auto rounded-xl border border-border bg-card shadow-sm focus:ring-2 focus:ring-ring focus:outline-none"
            >
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-muted/60">
                        <tr>
                            <th
                                class="px-6 py-2.5 text-left text-xs font-medium text-muted-foreground"
                            >
                                Name
                            </th>
                            <th
                                class="px-6 py-2.5 text-left text-xs font-medium text-muted-foreground"
                            >
                                Email
                            </th>
                            <th
                                class="px-6 py-2.5 text-left text-xs font-medium text-muted-foreground"
                            >
                                Phone
                            </th>
                            <th
                                class="px-6 py-2.5 text-left text-xs font-medium text-muted-foreground"
                            >
                                Deleted
                            </th>
                            <th
                                class="px-6 py-2.5 text-right text-xs font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border bg-card">
                        <tr
                            v-for="customer in customers.data"
                            :key="customer.id"
                            class="hover:bg-muted/40"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div
                                    class="text-sm font-medium text-foreground"
                                >
                                    {{ customer.name }}
                                </div>
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-muted-foreground"
                            >
                                {{ primaryEmail(customer) || '—' }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-muted-foreground"
                            >
                                {{ customer.phone || '—' }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDate(customer.deleted_at) }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-sm whitespace-nowrap"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        v-if="can('update')"
                                        type="button"
                                        class="cursor-pointer text-primary hover:text-primary-soft-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="restoring === customer.id"
                                        @click="restoreCustomer(customer)"
                                    >
                                        {{
                                            restoring === customer.id
                                                ? 'Restoring...'
                                                : 'Restore'
                                        }}
                                    </button>
                                    <button
                                        v-if="can('delete')"
                                        type="button"
                                        class="cursor-pointer text-destructive hover:text-destructive-soft-foreground"
                                        @click="confirmForceDelete(customer)"
                                    >
                                        Delete Forever
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center">
                                <h3
                                    class="text-sm font-semibold text-foreground"
                                >
                                    Trash is empty
                                </h3>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Deleted customers will appear here.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <nav
                    v-if="customers.last_page > 1"
                    class="flex items-center justify-between border-t border-border bg-card px-4 py-3 sm:px-6"
                >
                    <div class="hidden sm:block">
                        <p class="text-sm text-soft-foreground">
                            Showing page
                            <span class="font-medium">{{
                                customers.current_page
                            }}</span>
                            of
                            <span class="font-medium">{{
                                customers.last_page
                            }}</span>
                            ({{ customers.total }} total)
                        </p>
                    </div>
                    <div
                        class="flex flex-1 justify-between gap-2 sm:justify-end"
                    >
                        <template
                            v-for="link in customers.links"
                            :key="link.label"
                        >
                            <button
                                v-if="link.url"
                                type="button"
                                class="relative inline-flex cursor-pointer items-center rounded-md border px-4 py-2 text-sm font-medium"
                                :class="
                                    link.active
                                        ? 'border-primary bg-primary-soft/50 text-primary'
                                        : 'border-input bg-card text-soft-foreground hover:bg-muted'
                                "
                                @click="router.get(link.url!)"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="relative inline-flex items-center rounded-md border border-border bg-secondary px-4 py-2 text-sm font-medium text-faint-foreground"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </nav>
            </div>
        </div>

        <ConfirmationModal
            :restore-focus-to="listRegion"
            :show="confirmingForceDelete"
            title="Delete Forever"
            message="Permanently delete this customer? This cannot be undone. If they have any invoices, the deletion will be blocked."
            confirm-label="Delete Forever"
            :destructive="true"
            @confirm="forceDeleteCustomer"
            @cancel="confirmingForceDelete = false"
        />
    </AppLayout>
</template>
