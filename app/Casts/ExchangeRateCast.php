<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateCast implements CastsAttributes
{
    /**
     * Cast the given value from micro-units to decimal string.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value === null) {
            return '1.000000';
        }

        // Convert micro-units to decimal string (1234567 -> "1.234567")
        return number_format($value / 1000000, 6, '.', '');
    }

    /**
     * Prepare the given value for storage as micro-units.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if ($value === null) {
            return 1000000; // Default 1.000000 as micro-units
        }

        // Convert decimal to micro-units (1.234567 -> 1234567)
        $decimal = (float) $value;

        return (int) round($decimal * 1000000);
    }
}
