<?php

namespace App\Casts;

use App\ValueObjects\EmailCollection;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class EmailCollectionCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EmailCollection
    {
        if ($value === null) {
            return new EmailCollection([]);
        }

        if (is_string($value)) {
            try {
                return EmailCollection::fromJson($value);
            } catch (InvalidArgumentException) {
                return new EmailCollection([]);
            }
        }

        if (is_array($value)) {
            return EmailCollection::fromArray($value);
        }

        return new EmailCollection([]);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return json_encode([]);
        }

        if ($value instanceof EmailCollection) {
            return $value->toJson();
        }

        if (is_array($value)) {
            return (new EmailCollection($value))->toJson();
        }

        if (is_string($value)) {
            return (new EmailCollection([$value]))->toJson();
        }

        return json_encode([]);
    }
}
