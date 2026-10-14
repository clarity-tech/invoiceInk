<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';

defineOptions({ layout: SettingsLayout });

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
    <div>
        <div class="px-4 py-4 sm:px-0">
            <p class="mb-6 text-sm text-muted-foreground">
                Customize the emails sent with your invoices and estimates.
                Templates not customized use the latest defaults.
            </p>

            <!-- Invoice Templates -->
            <div class="mb-8">
                <h3
                    class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Invoice Templates
                </h3>
                <div class="overflow-hidden rounded-lg border border-border">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Template
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr
                                v-for="tpl in templates.filter(
                                    (t) => t.document_type === 'invoice',
                                )"
                                :key="tpl.type"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">
                                        {{ tpl.label }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ tpl.description }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="tpl.is_customized"
                                        class="inline-flex rounded-full bg-primary-soft px-2.5 py-0.5 text-xs font-medium text-primary-soft-foreground"
                                    >
                                        Customized
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-soft-foreground"
                                    >
                                        Default
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="`/email-templates/${tpl.type}`"
                                        class="text-sm font-medium text-primary hover:text-primary-soft-foreground"
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
                    class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Estimate Templates
                </h3>
                <div class="overflow-hidden rounded-lg border border-border">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-muted">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Template
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            <tr
                                v-for="tpl in templates.filter(
                                    (t) => t.document_type === 'estimate',
                                )"
                                :key="tpl.type"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">
                                        {{ tpl.label }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ tpl.description }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="tpl.is_customized"
                                        class="inline-flex rounded-full bg-primary-soft px-2.5 py-0.5 text-xs font-medium text-primary-soft-foreground"
                                    >
                                        Customized
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-soft-foreground"
                                    >
                                        Default
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="`/email-templates/${tpl.type}`"
                                        class="text-sm font-medium text-primary hover:text-primary-soft-foreground"
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
            <div class="mt-8 rounded-lg border border-border bg-muted p-6">
                <h3 class="mb-3 text-sm font-semibold text-soft-foreground">
                    Available Template Variables
                </h3>
                <p class="mb-3 text-xs text-muted-foreground">
                    Use these in your templates — they'll be replaced with real
                    data when the email is sent.
                </p>
                <div class="flex flex-wrap gap-2">
                    <code
                        v-for="(desc, variable) in variables"
                        :key="variable"
                        class="rounded bg-card px-2 py-1 text-xs text-primary ring-1 ring-border"
                        :title="desc"
                    >
                        {{ variable }}
                    </code>
                </div>
            </div>
        </div>
    </div>
</template>
