<?php

namespace App\Rules;

use App\Support\Jetstream;
use Illuminate\Contracts\Validation\Rule;

class Role implements Rule
{
    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        return in_array($value, array_keys(Jetstream::$roles));
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return __('The :attribute must be a valid role.');
    }
}
