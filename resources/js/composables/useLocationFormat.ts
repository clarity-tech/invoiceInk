import type { Location } from '@/types';

export type CountryNames = Record<string, string>;

/** A location whose address parts may be partially filled in. */
type PartialLocation = Partial<
    Pick<
        Location,
        'address_line_1' | 'address_line_2' | 'city' | 'state' | 'country'
    >
> | null;

/**
 * Resolve the display name for a country code, falling back to the raw code
 * when it isn't in the map (an unsupported or legacy country).
 *
 * @param country an ISO 3166-1 alpha-2 country code
 * @param countryNames map of country code => name, typically passed as an Inertia prop
 */
export function countryNameFor(
    country: string | null | undefined,
    countryNames: CountryNames,
): string {
    const code = (country ?? '').trim();

    return countryNames[code] ?? code;
}

/**
 * In several countries the city and the state/emirate carry the same name
 * (Dubai, Singapore, Delhi). Repeating it reads as a data-entry mistake, so the
 * state is dropped whenever it merely echoes the city.
 */
function stateUnlessEchoingCity(loc: PartialLocation): string {
    const city = (loc?.city ?? '').trim();
    const state = (loc?.state ?? '').trim();

    if (!state || state.toLowerCase() === city.toLowerCase()) {
        return '';
    }

    return state;
}

/**
 * Full single-line address for a location:
 * "Unit 4, Palm Court, Al Barsha, DUBAI, United Arab Emirates".
 *
 * Empty parts are skipped rather than leaving dangling commas, and the
 * state is omitted when it duplicates the city.
 *
 * @param countryNames map of country code => name, typically passed as an Inertia prop
 */
export function formatLocationLine(
    loc: PartialLocation,
    countryNames: CountryNames,
): string {
    return [
        loc?.address_line_1,
        loc?.address_line_2,
        loc?.city,
        stateUnlessEchoingCity(loc),
        countryNameFor(loc?.country, countryNames),
    ]
        .map((part) => (part ?? '').trim())
        .filter(Boolean)
        .join(', ');
}

/**
 * Condensed "where is this" label for table columns: city plus the state, or
 * the country name when the state adds nothing (missing, or the same as the
 * city). Yields "Secunderabad, Telangana" for India and "DUBAI, United Arab
 * Emirates" for the UAE.
 *
 * @param countryNames map of country code => name, typically passed as an Inertia prop
 */
export function formatLocationSummary(
    loc: PartialLocation,
    countryNames: CountryNames,
): string {
    const city = (loc?.city ?? '').trim();
    const state = stateUnlessEchoingCity(loc);

    return [city, state || countryNameFor(loc?.country, countryNames)]
        .filter(Boolean)
        .join(', ');
}
