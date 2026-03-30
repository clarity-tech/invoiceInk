<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps<{
    templates: {
        type: string;
        label: string;
        description: string;
        document_type: string;
        is_customized: boolean;
    }[];
    variables: Record<string, string>;
}>();
</script>

<template>
    <AppLayout title="Email Templates">
        <template #header>
            <h2 class="text-xl leading-tight font-semibold text-gray-800">
                Email Templates
            </h2>
        </template>

        <div class="px-4 py-4 sm:px-0">
            <p class="mb-6 text-sm text-gray-500">
                Customize the emails sent with your invoices and
                estimates. Templates not customized use the latest
                defaults.
            </p>

            <!-- Invoice Templates -->
            <div class="mb-8">
                <h3
                    class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase"
                >
                    Invoice Templates
                </h3>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Template
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="tpl in templates.filter(
                                    (t) => t.document_type === 'invoice',
                                )"
                                :key="tpl.type"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">
                                        {{ tpl.label }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ tpl.description }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="tpl.is_customized"
                                        class="inline-flex rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800"
                                    >
                                        Customized
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                                    >
                                        Default
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="`/email-templates/${tpl.type}`"
                                        class="text-sm font-medium text-brand-600 hover:text-brand-900"
                                    >
                                        {{
                                            tpl.is_customized
                                                ? 'Edit'
                                                : 'Customize'
                                        }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Estimate Templates -->
            <div>
                <h3
                    class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase"
                >
                    Estimate Templates
                </h3>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Template
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="tpl in templates.filter(
                                    (t) => t.document_type === 'estimate',
                                )"
                                :key="tpl.type"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">
                                        {{ tpl.label }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ tpl.description }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="tpl.is_customized"
                                        class="inline-flex rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800"
                                    >
                                        Customized
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                                    >
                                        Default
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="`/email-templates/${tpl.type}`"
                                        class="text-sm font-medium text-brand-600 hover:text-brand-900"
                                    >
                                        {{
                                            tpl.is_customized
                                                ? 'Edit'
                                                : 'Customize'
                                        }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Variables Reference -->
            <div class="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-6">
                <h3 class="mb-3 text-sm font-semibold text-gray-700">
                    Available Template Variables
                </h3>
                <p class="mb-3 text-xs text-gray-500">
                    Use these in your templates — they'll be replaced with real
                    data when the email is sent.
                </p>
                <div class="flex flex-wrap gap-2">
                    <code
                        v-for="(desc, variable) in variables"
                        :key="variable"
                        class="rounded bg-white px-2 py-1 text-xs text-brand-700 ring-1 ring-gray-200"
                        :title="desc"
                    >
                        {{ variable }}
                    </code>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
