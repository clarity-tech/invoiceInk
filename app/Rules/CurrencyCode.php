<?php

namespace App\Rules;

use Akaunting\Money\Currency;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CurrencyCode implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid currency code.');

            return;
        }

        $currencies = Currency::getCurrencies();

        if (! array_key_exists($value, $currencies)) {
            $fail('The :attribute must be a valid currency code.');
        }
    }
}
