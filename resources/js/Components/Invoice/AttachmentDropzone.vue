<script setup lang="ts">
import { ref } from 'vue';
import {
    acceptAttribute,
    collectValidFiles,
    formatBytes,
} from '@/composables/useFileAttachments';

// Newly staged files, exposed via v-model so the parent form can submit them.
const model = defineModel<File[]>({ required: true });

const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const error = ref('');

function addFiles(files: FileList | File[] | null) {
    const { accepted, error: validationError } = collectValidFiles(
        model.value,
        files,
    );

    if (accepted.length > 0) {
        model.value = [...model.value, ...accepted];
    }

    error.value = validationError;
}

function removeFile(index: number) {
    model.value = model.value.filter((_, i) => i !== index);
    error.value = '';
}

function onFilesSelected(event: Event) {
    const target = event.target as HTMLInputElement;
    addFiles(target.files);
    target.value = '';
}

function onDrop(event: DragEvent) {
    isDragging.value = false;
    addFiles(event.dataTransfer?.files ?? null);
}
</script>

<template>
    <div>
        <ul v-if="model.length > 0" class="mb-2 space-y-1">
            <li
                v-for="(file, index) in model"
                :key="`${file.name}-${index}`"
                class="flex items-center justify-between rounded-md border border-border bg-muted px-3 py-2"
            >
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium text-foreground">
                        {{ file.name }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        {{ formatBytes(file.size) }}
                    </div>
                </div>
                <button
                    type="button"
                    class="ml-2 text-faint-foreground hover:text-destructive"
                    aria-label="Remove attachment"
                    @click="removeFile(index)"
                >
                    <svg
                        class="h-4 w-4"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </li>
        </ul>

        <div
            class="cursor-pointer rounded-md border border-dashed px-3 py-4 text-center transition"
            :class="
                isDragging
                    ? 'border-primary bg-primary-soft/50'
                    : 'border-input hover:bg-muted'
            "
            role="button"
            tabindex="0"
            @click="fileInputRef?.click()"
            @keydown.enter.prevent="fileInputRef?.click()"
            @keydown.space.prevent="fileInputRef?.click()"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <p class="text-sm text-soft-foreground">
                <span class="font-medium text-primary"
                    >Click to attach files</span
                >
                or drag and drop
            </p>
            <p class="mt-0.5 text-xs text-muted-foreground">
                Up to 10 files, 10MB each, 25MB total
            </p>
            <input
                ref="fileInputRef"
                type="file"
                multiple
                class="hidden"
                :accept="acceptAttribute()"
                @change="onFilesSelected"
            />
        </div>

        <p v-if="error" class="mt-2 text-xs text-destructive">
            {{ error }}
        </p>
    </div>
</template>
