import { describe, it, expect } from 'vitest';
import { ref } from 'vue';
import { useTaxLabel } from '@/composables/useTaxLabel';

const labels = {
    IN: 'GSTIN',
    GB: 'VAT Registration Number',
    US: 'EIN',
    AE: 'TRN',
    AU: 'ABN',
};

describe('useTaxLabel', () => {
    it('returns GSTIN for India', () => {
        const country = ref<string | null>('IN');
        const { label, isIndia } = useTaxLabel(country, labels);
        expect(label.value).toBe('GSTIN');
        expect(isIndia.value).toBe(true);
    });

    it('returns country-specific name for non-Indian country', () => {
        const country = ref<string | null>('AE');
        const { label, isIndia } = useTaxLabel(country, labels);
        expect(label.value).toBe('TRN');
        expect(isIndia.value).toBe(false);
    });

    it('falls back to "Tax ID" for unknown country code', () => {
        const country = ref<string | null>('ZZ');
        const { label } = useTaxLabel(country, labels);
        expect(label.value).toBe('Tax ID');
    });

    it('falls back to "Tax ID" when country is empty', () => {
        const country = ref<string | null>('');
        const { label } = useTaxLabel(country, labels);
        expect(label.value).toBe('Tax ID');
    });

    it('falls back to "Tax ID" when country is null', () => {
        const country = ref<string | null>(null);
        const { label } = useTaxLabel(country, labels);
        expect(label.value).toBe('Tax ID');
    });

    it('reacts to country change', async () => {
        const country = ref<string | null>('IN');
        const { label, isIndia } = useTaxLabel(country, labels);
        expect(label.value).toBe('GSTIN');
        expect(isIndia.value).toBe(true);

        country.value = 'GB';
        expect(label.value).toBe('VAT Registration Number');
        expect(isIndia.value).toBe(false);
    });
});
