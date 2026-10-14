<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/Components/ui/breadcrumb';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import TipTapEditor from '@/Components/TipTapEditor.vue';

const props = defineProps<{
    templateType: string;
    label: string;
    description: string;
    documentType: string;
    template: {
        subject: string;
        body: string;
        is_customized: boolean;
    };
    defaultTemplate: {
        subject: string;
        body: string;
    };
    variables: Record<string, string>;
}>();

const form = useForm({
    subject: props.template.subject,
    body: props.template.body,
});

const editorRef = ref<InstanceType<typeof TipTapEditor> | null>(null);
const showPreview = ref(false);
const previewHtml = ref('');
const previewSubject = ref('');
const loadingPreview = ref(false);

const isModified = computed(() => {
    return (
        form.subject !== props.template.subject ||
        form.body !== props.template.body
    );
});

const isDifferentFromDefault = computed(() => {
    return (
        form.subject !== props.defaultTemplate.subject ||
        form.body !== props.defaultTemplate.body
    );
});

function save() {
    form.put(`/email-templates/${props.templateType}`, {
        preserveScroll: true,
    });
}

function resetToDefault() {
    if (
        !confirm(
            'Reset this template to the default? Your customizations will be lost.',
        )
    ) {
        return;
    }

    router.delete(`/email-templates/${props.templateType}`, {
        preserveScroll: true,
        onSuccess: () => {
            form.subject = props.defaultTemplate.subject;
            form.body = props.defaultTemplate.body;
        },
    });
}

function restoreDefault() {
    form.subject = props.defaultTemplate.subject;
    form.body = props.defaultTemplate.body;
}

function insertVariable(variable: string) {
    editorRef.value?.insertText(variable);
}

async function preview() {
    loadingPreview.value = true;

    try {
        const response = await fetch('/api/email-templates/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
            body: JSON.stringify({
                subject: form.subject,
                body: form.body,
            }),
        });

        const data = await response.json();
        previewSubject.value = data.subject;
        previewHtml.value = data.body;
        showPreview.value = true;
    } finally {
        loadingPreview.value = false;
    }
}
</script>

<template>
    <AppLayout :title="`Edit ${label}`">
        <template #header>
            <div class="flex items-center gap-3">
                <Breadcrumb>
                    <BreadcrumbItem>
                        <BreadcrumbLink href="/email-templates">
                            Email Templates
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>{{ label }}</BreadcrumbPage>
                    </BreadcrumbItem>
                </Breadcrumb>
                <span
                    v-if="template.is_customized"
                    class="rounded-full bg-primary-soft px-2.5 py-0.5 text-xs font-medium text-primary-soft-foreground"
                >
                    Customized
                </span>
                <span
                    v-else
                    class="rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-soft-foreground"
                >
                    Default
                </span>
            </div>
        </template>

        <div class="py-4">
            <p class="mb-6 text-sm text-muted-foreground">
                {{ description }}
            </p>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <!-- Main Editor (3 cols) -->
                <div class="lg:col-span-3">
                    <!-- Subject -->
                    <div class="mb-4">
                        <label
                            class="mb-1 block text-sm font-medium text-soft-foreground"
                            >Subject</label
                        >
                        <input
                            v-model="form.subject"
                            type="text"
                            class="w-full rounded-md border border-input px-3 py-2 text-sm focus:ring-2 focus:ring-ring focus:outline-none"
                            placeholder="Email subject with {{variables}}"
                        />
                        <p
                            v-if="form.errors.subject"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.subject }}
                        </p>
                    </div>

                    <!-- Body -->
                    <div class="mb-4">
                        <label
                            class="mb-1 block text-sm font-medium text-soft-foreground"
                            >Body</label
                        >
                        <TipTapEditor
                            ref="editorRef"
                            v-model="form.body"
                            placeholder="Compose your email template..."
                        />
                        <p
                            v-if="form.errors.body"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.body }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            :disabled="form.processing || !isModified"
                            class="rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="save"
                        >
                            {{
                                form.processing ? 'Saving...' : 'Save Template'
                            }}
                        </button>
                        <button
                            type="button"
                            class="rounded-md border border-input px-4 py-2 text-sm font-medium text-soft-foreground hover:bg-muted"
                            :disabled="loadingPreview"
                            @click="preview"
                        >
                            {{
                                loadingPreview
                                    ? 'Loading...'
                                    : 'Preview with Sample Data'
                            }}
                        </button>
                        <button
                            v-if="isDifferentFromDefault"
                            type="button"
                            class="text-sm text-muted-foreground hover:text-soft-foreground"
                            @click="restoreDefault"
                        >
                            Restore Default Text
                        </button>
                        <button
                            v-if="template.is_customized"
                            type="button"
                            class="ml-auto text-sm text-destructive hover:text-destructive-soft-foreground"
                            @click="resetToDefault"
                        >
                            Reset to Default
                        </button>
                    </div>
                </div>

                <!-- Variables Sidebar (1 col) -->
                <div class="lg:col-span-1">
                    <div
                        class="sticky top-6 rounded-lg border border-border bg-muted p-4"
                    >
                        <h3
                            class="mb-3 text-sm font-semibold text-soft-foreground"
                        >
                            Insert Variable
                        </h3>
                        <p class="mb-3 text-xs text-muted-foreground">
                            Click to insert at cursor position
                        </p>
                        <div class="flex flex-col gap-1.5">
                            <button
                                v-for="(desc, variable) in variables"
                                :key="variable"
                                type="button"
                                class="flex items-start gap-2 rounded-md px-2 py-1.5 text-left hover:bg-card"
                                :title="desc"
                                @click="insertVariable(variable as string)"
                            >
                                <code
                                    class="shrink-0 rounded bg-card px-1.5 py-0.5 text-xs text-primary ring-1 ring-border"
                                >
                                    {{ variable }}
                                </code>
                                <span
                                    class="truncate text-xs text-muted-foreground"
                                    >{{ desc }}</span
                                >
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Modal -->
            <div
                v-if="showPreview"
                class="fixed inset-0 z-50 flex items-center justify-center bg-overlay"
                @click.self="showPreview = false"
                @keydown.escape="showPreview = false"
            >
                <div
                    class="mx-4 flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg bg-card shadow-2xl"
                >
                    <div
                        class="flex items-center justify-between border-b border-border px-6 py-4"
                    >
                        <div>
                            <h3 class="font-semibold text-foreground">
                                Email Preview
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Subject: {{ previewSubject }}
                            </p>
                        </div>
                        <button
                            class="text-faint-foreground hover:text-soft-foreground"
                            @click="showPreview = false"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-6">
                        <div
                            class="prose prose-sm max-w-none"
                            v-html="previewHtml"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
