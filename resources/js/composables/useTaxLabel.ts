import { computed, type Ref } from 'vue';

export type TaxLabels = Record<string, string>;

const FALLBACK_LABEL = 'Tax ID';

/**
 * Resolve the country-appropriate tax-id label for a single country code.
 * Returns "GSTIN" for India, "VAT Registration Number" for GB/DE, "TRN" for
 * UAE, etc., falling back to a generic "Tax ID" when the country is missing or
 * unknown. Use this in `v-for` loops where a reactive ref isn't convenient.
 *
 * @param country an ISO 3166-1 alpha-2 country code
 * @param taxLabels map of country code => label, typically passed as an Inertia prop
 */
export function taxLabelFor(
    country: string | null | undefined,
    taxLabels: TaxLabels,
): string {
    return taxLabels[country ?? ''] ?? FALLBACK_LABEL;
}

/**
 * Reactive variant of {@link taxLabelFor} for use with a single form/country
 * ref. Returns the country-appropriate tax-id label plus an `isIndia` flag.
 *
 * @param country reactive ref holding an ISO 3166-1 alpha-2 country code
 * @param taxLabels map of country code => label, typically passed as an Inertia prop
 */
export function useTaxLabel(
    country: Ref<string | null | undefined>,
    taxLabels: TaxLabels,
) {
    const label = computed(() => taxLabelFor(country.value, taxLabels));

    const isIndia = computed(() => country.value === 'IN');

    return { label, isIndia };
}
