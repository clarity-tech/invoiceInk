<script setup lang="ts">
import type { Location } from '@/types';

defineProps<{
    location: Partial<Location>;
}>();

defineEmits<{
    (e: 'update:location', value: Partial<Location>): void;
}>();
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700"
                >Address Line 1</label
            >
            <input
                type="text"
                :value="location.address_line_1"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                @input="
                    $emit('update:location', {
                        ...location,
                        address_line_1: ($event.target as HTMLInputElement)
                            .value,
                    })
                "
            />
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700"
                >Address Line 2</label
            >
            <input
                type="text"
                :value="location.address_line_2"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                @input="
                    $emit('update:location', {
                        ...location,
                        address_line_2: ($event.target as HTMLInputElement)
                            .value,
                    })
                "
            />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">City</label>
            <input
                type="text"
                :value="location.city"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                @input="
                    $emit('update:location', {
                        ...location,
                        city: ($event.target as HTMLInputElement).value,
                    })
                "
            />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">State</label>
            <input
                type="text"
                :value="location.state"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                @input="
                    $emit('update:location', {
                        ...location,
                        state: ($event.target as HTMLInputElement).value,
                    })
                "
            />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700"
                >Country</label
            >
            <input
                type="text"
                :value="location.country"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                @input="
                    $emit('update:location', {
                        ...location,
                        country: ($event.target as HTMLInputElement).value,
                    })
                "
            />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700"
                >Postal Code</label
            >
            <input
                type="text"
                :value="location.postal_code"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                @input="
                    $emit('update:location', {
                        ...location,
                        postal_code: ($event.target as HTMLInputElement).value,
                    })
                "
            />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">GSTIN</label>
            <input
                type="text"
                :value="location.gstin"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                @input="
                    $emit('update:location', {
                        ...location,
                        gstin: ($event.target as HTMLInputElement).value,
                    })
                "
            />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700"
                >Location Name</label
            >
            <input
                type="text"
                :value="location.name"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                placeholder="e.g., Head Office"
                @input="
                    $emit('update:location', {
                        ...location,
                        name: ($event.target as HTMLInputElement).value,
                    })
                "
            />
        </div>
    </div>
</template>
