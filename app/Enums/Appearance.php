<?php

namespace App\Enums;

/**
 * The colour theme a person has chosen. `SYSTEM` follows the operating system's
 * light/dark setting, so it is resolved in the browser rather than here.
 */
enum Appearance: string
{
    case LIGHT = 'light';
    case DARK = 'dark';
    case SYSTEM = 'system';

    /**
     * The cookie the browser stores the choice in. It is read by the server so
     * the first paint already carries the right theme, and written by
     * `useAppearance` in the browser — which is why it is not encrypted.
     */
    public const COOKIE = 'appearance';

    /**
     * An unknown or missing value means the person has never chosen, which is
     * the same as following the system.
     */
    public static function fromCookie(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::SYSTEM;
    }
}
