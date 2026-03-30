<script setup lang="ts">
import { ref, watch } from 'vue';
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
</script>

<template>
    <div
        v-if="visible && hasFlash"
        class="mb-4 px-4 sm:px-0"
    >
        <div v-if="flash.success" class="rounded-md bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800">
                {{ flash.success }}
            </p>
        </div>
        <div v-if="flash.error" class="rounded-md bg-red-50 p-4">
            <p class="text-sm font-medium text-red-800">{{ flash.error }}</p>
        </div>
        <div v-if="flash.message" class="rounded-md bg-blue-50 p-4">
            <p class="text-sm font-medium text-blue-800">{{ flash.message }}</p>
        </div>
    </div>
</template>
