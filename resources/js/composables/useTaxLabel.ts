import { computed, type Ref } from 'vue';

export type TaxLabels = Record<string, string>;

const FALLBACK_LABEL = 'Tax ID';

/**
 * Resolve the country-appropriate label for the tax-identification field on a
 * location form or invoice PDF. Returns "GSTIN" for India, "VAT Registration
 * Number" for GB/DE, "TRN" for UAE, etc., falling back to a generic "Tax ID"
 * when the country is missing or unknown.
 *
 * @param country reactive ref holding an ISO 3166-1 alpha-2 country code
 * @param taxLabels map of country code => label, typically passed as an Inertia prop
 */
export function useTaxLabel(
    country: Ref<string | null | undefined>,
    taxLabels: TaxLabels,
) {
    const label = computed(
        () => taxLabels[country.value ?? ''] ?? FALLBACK_LABEL,
    );

    const isIndia = computed(() => country.value === 'IN');

    return { label, isIndia };
}
