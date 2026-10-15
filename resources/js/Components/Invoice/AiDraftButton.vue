<script setup lang="ts">
import { Sparkles } from 'lucide-vue-next';
import { ref } from 'vue';
import DraftFromDocumentDialog from './DraftFromDocumentDialog.vue';

withDefaults(defineProps<{ type?: 'invoice' | 'estimate' }>(), {
    type: 'invoice',
});

const open = ref(false);
</script>

<template>
    <div class="contents">
        <button
            type="button"
            :title="`Fill in an ${type} from a message, PDF or photo`"
            class="inline-flex items-center gap-1.5 rounded-md bg-primary-soft px-4 py-2 text-sm font-medium text-primary-soft-foreground shadow-sm ring-1 ring-primary/30 transition hover:ring-primary/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none [&_svg]:size-4 [&_svg]:text-primary"
            @click="open = true"
        >
            <Sparkles />
            Draft with AI
        </button>

        <Teleport to="body">
            <DraftFromDocumentDialog
                :show="open"
                :type="type"
                @close="open = false"
            />
        </Teleport>
    </div>
</template>
