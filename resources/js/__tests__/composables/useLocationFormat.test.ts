import { describe, it, expect } from 'vitest';
import {
    countryNameFor,
    formatLocationLine,
    formatLocationSummary,
} from '@/composables/useLocationFormat';

const countryNames = {
    IN: 'India',
    GB: 'United Kingdom',
    US: 'United States',
    AE: 'United Arab Emirates',
};

const dubai = {
    address_line_1: 'Unit 4, Palm Court, Al Barsha',
    address_line_2: null,
    city: 'DUBAI',
    state: 'Dubai',
    country: 'AE',
};

const hyderabad = {
    address_line_1: '8-2-618/1, Road No. 11',
    address_line_2: null,
    city: 'Secunderabad',
    state: 'Telangana',
    country: 'IN',
};

describe('countryNameFor', () => {
    it('resolves a known country code to its name', () => {
        expect(countryNameFor('AE', countryNames)).toBe('United Arab Emirates');
    });

    it('falls back to the raw code for an unknown country', () => {
        expect(countryNameFor('ZZ', countryNames)).toBe('ZZ');
    });

    it('returns an empty string when the country is missing', () => {
        expect(countryNameFor(null, countryNames)).toBe('');
        expect(countryNameFor(undefined, countryNames)).toBe('');
        expect(countryNameFor('', countryNames)).toBe('');
    });
});

describe('formatLocationLine', () => {
    it('drops the state when it only echoes the city, in any case', () => {
        expect(formatLocationLine(dubai, countryNames)).toBe(
            'Unit 4, Palm Court, Al Barsha, DUBAI, United Arab Emirates',
        );
    });

    it('keeps a state that differs from the city', () => {
        expect(formatLocationLine(hyderabad, countryNames)).toBe(
            '8-2-618/1, Road No. 11, Secunderabad, Telangana, India',
        );
    });

    it('includes the second address line when present', () => {
        expect(
            formatLocationLine(
                { ...hyderabad, address_line_2: 'Prakash Nagar' },
                countryNames,
            ),
        ).toBe(
            '8-2-618/1, Road No. 11, Prakash Nagar, Secunderabad, Telangana, India',
        );
    });

    it('leaves no dangling comma when the state is empty', () => {
        expect(formatLocationLine({ ...dubai, state: '' }, countryNames)).toBe(
            'Unit 4, Palm Court, Al Barsha, DUBAI, United Arab Emirates',
        );
    });

    it('falls back to the raw code for an unknown country', () => {
        expect(
            formatLocationLine({ ...hyderabad, country: 'ZZ' }, countryNames),
        ).toBe('8-2-618/1, Road No. 11, Secunderabad, Telangana, ZZ');
    });

    it('returns an empty string for a null location', () => {
        expect(formatLocationLine(null, countryNames)).toBe('');
    });
});

describe('formatLocationSummary', () => {
    it('shows the country when the state duplicates the city', () => {
        expect(formatLocationSummary(dubai, countryNames)).toBe(
            'DUBAI, United Arab Emirates',
        );
    });

    it('shows the state when it differs from the city', () => {
        expect(formatLocationSummary(hyderabad, countryNames)).toBe(
            'Secunderabad, Telangana',
        );
    });

    it('falls back to the country when the state is missing', () => {
        expect(
            formatLocationSummary({ ...hyderabad, state: '' }, countryNames),
        ).toBe('Secunderabad, India');
    });

    it('returns just the city when the country is unknown too', () => {
        expect(
            formatLocationSummary(
                { ...hyderabad, state: '', country: '' },
                countryNames,
            ),
        ).toBe('Secunderabad');
    });
});
