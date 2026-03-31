<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';
</script>

<template>
    <AppLayout title="Settings">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Settings
            </h2>
        </template>

        <div class="px-4 py-4 sm:px-0">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    href="/email-templates"
                    class="flex items-start gap-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-brand-300 hover:shadow-md"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50"
                    >
                        <Mail class="h-5 w-5 text-brand-600" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Email Templates
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Customize emails sent with invoices and estimates
                        </p>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
