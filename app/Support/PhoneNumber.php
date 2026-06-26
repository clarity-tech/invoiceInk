<?php

namespace App\Support;

use libphonenumber\NumberParseException;

/**
 * Helpers for converting user-entered phone numbers into a single canonical
 * representation. Numbers are stored in E.164 (e.g. "+14155552671") and rendered
 * in a readable international format (e.g. "+1 415 555 2671").
 *
 * Wraps the propaganistas/laravel-phone {@see phone()} helper so callers never
 * have to deal with parse exceptions directly.
 */
class PhoneNumber
{
    /**
     * Normalize a national or international number to canonical E.164.
     *
     * @param  string|null  $number  The raw number (national or already E.164).
     * @param  string|null  $country  ISO 3166-1 alpha-2 hint for national input.
     * @return string|null The E.164 string, or null when blank/unparseable.
     */
    public static function normalize(?string $number, ?string $country = null): ?string
    {
        if (blank($number)) {
            return null;
        }

        try {
            return phone($number, $country)->formatE164();
        } catch (NumberParseException) {
            return null;
        }
    }

    /**
     * Render a stored E.164 number in international display format.
     *
     * @param  string|null  $number  An E.164 number.
     * @return string|null The international format, the raw value when it can't
     *                     be parsed, or null when blank.
     */
    public static function format(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        try {
            return phone($number)->formatInternational();
        } catch (NumberParseException) {
            return $number;
        }
    }
}
