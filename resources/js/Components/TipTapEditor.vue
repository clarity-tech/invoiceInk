<script setup lang="ts">
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: { class: 'text-brand-600 underline' },
        }),
        Underline,
        Placeholder.configure({
            placeholder: props.placeholder ?? 'Start typing...',
        }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[200px] px-4 py-3',
        },
    },
    onUpdate: ({ editor: e }) => {
        emit('update:modelValue', e.getHTML());
    },
});

watch(
    () => props.modelValue,
    (val) => {
        if (editor.value && editor.value.getHTML() !== val) {
            editor.value.commands.setContent(val, false);
        }
    },
);

function insertText(text: string) {
    editor.value?.chain().focus().insertContent(text).run();
}

const linkPopoverOpen = ref(false);
const linkUrl = ref('');
const linkInputRef = ref<HTMLInputElement | null>(null);
const linkPopoverRef = ref<HTMLElement | null>(null);
const linkButtonRef = ref<HTMLButtonElement | null>(null);

function openLinkPopover() {
    if (!editor.value) {
        return;
    }

    linkUrl.value = editor.value.getAttributes('link').href ?? '';
    linkPopoverOpen.value = true;

    nextTick(() => {
        linkInputRef.value?.focus();
        linkInputRef.value?.select();
    });
}

function closeLinkPopover() {
    linkPopoverOpen.value = false;
    linkUrl.value = '';
}

function normalizeUrl(input: string): string {
    const trimmed = input.trim();

    if (trimmed === '') {
        return '';
    }

    if (/^[a-z][a-z0-9+.-]*:/i.test(trimmed)) {
        return trimmed;
    }

    if (trimmed.startsWith('/') || trimmed.startsWith('#')) {
        return trimmed;
    }

    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
        return `mailto:${trimmed}`;
    }

    return `https://${trimmed}`;
}

function applyLink() {
    if (!editor.value) {
        return;
    }

    const url = normalizeUrl(linkUrl.value);

    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
        editor.value
            .chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href: url })
            .run();
    }

    closeLinkPopover();
}

function removeLink() {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
    closeLinkPopover();
}

function handleDocumentClick(event: MouseEvent) {
    if (!linkPopoverOpen.value) {
        return;
    }

    const target = event.target as Node;

    if (
        linkPopoverRef.value?.contains(target) ||
        linkButtonRef.value?.contains(target)
    ) {
        return;
    }

    closeLinkPopover();
}

document.addEventListener('mousedown', handleDocumentClick);

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleDocumentClick);
});

defineExpose({ insertText, editor });
</script>

<template>
    <div class="rounded-md border border-gray-300 bg-white">
        <!-- Toolbar -->
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-1 border-b border-gray-200 px-2 py-1.5"
        >
            <button
                type="button"
                class="rounded p-1.5 hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor.isActive('bold') }"
                title="Bold"
                @click="editor.chain().focus().toggleBold().run()"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="3"
                        d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="3"
                        d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"
                    />
                </svg>
            </button>
            <button
                type="button"
                class="rounded p-1.5 hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor.isActive('italic') }"
                title="Italic"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 4h4m-2 0v16m-4 0h8"
                    />
                </svg>
            </button>
            <button
                type="button"
                class="rounded p-1.5 hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor.isActive('underline') }"
                title="Underline"
                @click="editor.chain().focus().toggleUnderline().run()"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 3v7a6 6 0 006 6 6 6 0 006-6V3M4 21h16"
                    />
                </svg>
            </button>

            <div class="mx-1 h-5 w-px bg-gray-300" />

            <button
                type="button"
                class="rounded p-1.5 hover:bg-gray-100"
                :class="{
                    'bg-gray-200': editor.isActive('heading', { level: 2 }),
                }"
                title="Heading"
                @click="
                    editor.chain().focus().toggleHeading({ level: 2 }).run()
                "
            >
                <span class="text-xs font-bold">H2</span>
            </button>
            <button
                type="button"
                class="rounded p-1.5 hover:bg-gray-100"
                :class="{
                    'bg-gray-200': editor.isActive('heading', { level: 3 }),
                }"
                title="Subheading"
                @click="
                    editor.chain().focus().toggleHeading({ level: 3 }).run()
                "
            >
                <span class="text-xs font-bold">H3</span>
            </button>

            <div class="mx-1 h-5 w-px bg-gray-300" />

            <button
                type="button"
                class="rounded p-1.5 hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor.isActive('bulletList') }"
                title="Bullet list"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"
                    />
                </svg>
            </button>
            <button
                type="button"
                class="rounded p-1.5 hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor.isActive('orderedList') }"
                title="Numbered list"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 6h13M7 12h13M7 18h13M2 6h2M2 12h2M2 18h2"
                    />
                </svg>
            </button>
            <button
                type="button"
                class="rounded p-1.5 hover:bg-gray-100"
                :class="{ 'bg-gray-200': editor.isActive('blockquote') }"
                title="Quote"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 10h4v4H3v-4zm14 0h4v4h-4v-4z"
                    />
                </svg>
            </button>

            <div class="mx-1 h-5 w-px bg-gray-300" />

            <div class="relative">
                <button
                    ref="linkButtonRef"
                    type="button"
                    class="rounded p-1.5 text-xs font-medium text-brand-600 hover:bg-brand-50"
                    :class="{
                        'bg-brand-50':
                            editor.isActive('link') || linkPopoverOpen,
                    }"
                    title="Insert link"
                    @click="
                        linkPopoverOpen ? closeLinkPopover() : openLinkPopover()
                    "
                >
                    Link
                </button>

                <div
                    v-if="linkPopoverOpen"
                    ref="linkPopoverRef"
                    class="absolute top-full left-0 z-10 mt-1 flex items-center gap-2 rounded-md border border-gray-200 bg-white p-2 shadow-lg"
                    @keydown.escape.stop="closeLinkPopover"
                >
                    <input
                        ref="linkInputRef"
                        v-model="linkUrl"
                        type="url"
                        placeholder="https://example.com"
                        class="w-64 rounded-md border border-gray-300 px-2 py-1 text-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none"
                        @keydown.enter.prevent="applyLink"
                    />
                    <button
                        type="button"
                        class="rounded-md bg-brand-600 px-3 py-1 text-xs font-medium text-white hover:bg-brand-700"
                        @click="applyLink"
                    >
                        Apply
                    </button>
                    <button
                        v-if="editor.isActive('link')"
                        type="button"
                        class="rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50"
                        @click="removeLink"
                    >
                        Remove
                    </button>
                </div>
            </div>
        </div>

        <!-- Editor content -->
        <EditorContent :editor="editor" />
    </div>
</template>

<style>
.tiptap p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}

.tiptap.prose {
    --tw-prose-body: #1f2937;
    --tw-prose-headings: #1f2937;
    --tw-prose-bold: #1f2937;
    --tw-prose-bullets: #1f2937;
    --tw-prose-counters: #1f2937;
    --tw-prose-links: #7c3aed;
}

/* Lists: prose spaces items "loosely" — TipTap wraps each item's text in a <p>,
   and prose gives those <p>s large vertical margins (the oversized gaps) while
   indenting the list less than the email. Override to the email's tighter,
   clearly-indented default-list look with solid dark markers. */
.tiptap.prose :is(ul, ol) {
    margin-top: 0.75em;
    margin-bottom: 0.75em;
    padding-inline-start: 2.25em;
}

.tiptap.prose li {
    margin-top: 0.15em;
    margin-bottom: 0.15em;
}

.tiptap.prose li > p {
    margin-top: 0;
    margin-bottom: 0;
}

.tiptap.prose li::marker {
    color: #1f2937;
}
</style>
