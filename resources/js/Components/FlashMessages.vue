<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useFlash } from '@/composables/useFlash';

const { flash, hasFlash } = useFlash();
const visible = ref(false);

watch(
    hasFlash,
    (val) => {
        if (val) {
            visible.value = true;
            setTimeout(() => {
                visible.value = false;
            }, 4000);
        }
    },
    { immediate: true },
);

const showError = computed(() => visible.value && !!flash.value.error);
const showPolite = computed(
    () =>
        visible.value &&
        !!(flash.value.success || flash.value.info || flash.value.message),
);
</script>

<template>
    <div>
        <!--
        The live regions are always in the DOM and only their contents change.
        A region that is inserted and populated in the same tick is commonly
        missed by screen readers, which is what `v-if` on the wrapper used to
        do — so the wrapper stays and the messages come and go inside it.

        Errors are assertive because they report an action that did not happen
        (a failed delete or convert); everything else is polite.

        Layout classes are bound rather than static so an empty region occupies
        no space and adds no margin.
    -->
        <div
            role="status"
            aria-live="polite"
            aria-atomic="true"
            :class="showPolite ? 'mb-4 px-4 sm:px-0' : ''"
        >
            <template v-if="showPolite">
                <div v-if="flash.success" class="rounded-md bg-green-50 p-4">
                    <p class="text-sm font-medium text-green-800">
                        {{ flash.success }}
                    </p>
                </div>
                <div v-if="flash.info" class="rounded-md bg-blue-50 p-4">
                    <p class="text-sm font-medium text-blue-800">
                        {{ flash.info }}
                    </p>
                </div>
                <div v-if="flash.message" class="rounded-md bg-blue-50 p-4">
                    <p class="text-sm font-medium text-blue-800">
                        {{ flash.message }}
                    </p>
                </div>
            </template>
        </div>

        <div
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            :class="showError ? 'mb-4 px-4 sm:px-0' : ''"
        >
            <div v-if="showError" class="rounded-md bg-red-50 p-4">
                <p class="text-sm font-medium text-red-800">
                    {{ flash.error }}
                </p>
            </div>
        </div>
    </div>
</template>
