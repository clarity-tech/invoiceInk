<?php

namespace App\Casts;

use App\ValueObjects\ContactCollection;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ContactCollectionCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ContactCollection
    {
        if ($value === null) {
            return new ContactCollection([]);
        }

        if (is_string($value)) {
            try {
                return ContactCollection::fromJson($value);
            } catch (InvalidArgumentException) {
                return new ContactCollection([]);
            }
        }

        if (is_array($value)) {
            return ContactCollection::fromArray($value);
        }

        return new ContactCollection([]);
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

        if ($value instanceof ContactCollection) {
            return $value->toJson();
        }

        if (is_array($value)) {
            // Handle array of contacts or simple emails for backward compatibility during development
            if (!empty($value) && is_array($value[0])) {
                // Already in contact format
                return (new ContactCollection($value))->toJson();
            } else {
                // Convert simple email array to contact array with empty names
                $contacts = array_map(fn($email) => ['name' => '', 'email' => $email], $value);
                return (new ContactCollection($contacts))->toJson();
            }
        }

        if (is_string($value)) {
            return (new ContactCollection([['name' => '', 'email' => $value]]))->toJson();
        }

        return json_encode([]);
    }
}