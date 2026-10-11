<script setup lang="ts">
import { computed } from 'vue';
import { taxLabelFor } from '@/composables/useTaxLabel';
import type { Location } from '@/types';

/**
 * One rendering of a postal address, used for both the billing and the
 * shipping block. The two were written out separately and had already drifted:
 * the same address could gain or lose a line depending on which side it was
 * shown on.
 */
const props = defineProps<{
    location: Location;
    taxLabels: Record<string, string>;
    /**
     * One wrapping line instead of five stacked ones. The form shows an
     * address to confirm the right one was picked, not to be read off; five
     * lines apiece made the two address blocks the tallest thing on the page.
     */
    compact?: boolean;
}>();

const oneLine = computed(() =>
    [
        props.location.address_line_1,
        props.location.address_line_2,
        props.location.city,
        props.location.state,
        props.location.postal_code,
        props.location.country,
    ]
        .map((part) => (part ?? '').trim())
        .filter(Boolean)
        .join(', '),
);
</script>

<template>
    <p v-if="compact" class="text-xs text-muted-foreground">
        {{ oneLine }}
        <span v-if="location.tax_id" class="font-medium text-foreground">
            · {{ taxLabelFor(location.country, taxLabels) }}
            {{ location.tax_id }}
        </span>
    </p>

    <div v-else class="space-y-0.5 text-sm text-muted-foreground">
        <p v-if="location.address_line_1">{{ location.address_line_1 }}</p>
        <p v-if="location.address_line_2">{{ location.address_line_2 }}</p>
        <p>
            {{ location.city
            }}<span v-if="location.state">, {{ location.state }}</span
            ><span v-if="location.postal_code">
                - {{ location.postal_code }}</span
            >
        </p>
        <p v-if="location.country">{{ location.country }}</p>
        <p v-if="location.tax_id" class="pt-1 font-medium text-foreground">
            <span class="text-muted-foreground"
                >{{ taxLabelFor(location.country, taxLabels) }}:</span
            >
            {{ location.tax_id }}
        </p>
    </div>
</template>
