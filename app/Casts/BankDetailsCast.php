<?php

namespace App\Casts;

use App\ValueObjects\BankDetails;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class BankDetailsCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?BankDetails
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                return null;
            }

            return BankDetails::fromArray($decoded);
        }

        if (is_array($value)) {
            return BankDetails::fromArray($value);
        }

        return null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BankDetails) {
            return $value->isEmpty() ? null : json_encode($value->toArray());
        }

        if (is_array($value)) {
            $bankDetails = BankDetails::fromArray($value);

            return $bankDetails->isEmpty() ? null : json_encode($bankDetails->toArray());
        }

        return null;
    }
}
