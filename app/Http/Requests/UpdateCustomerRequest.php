<?php

namespace App\Http\Requests;

use App\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer && ($this->user()?->can('update', $customer) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_country' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', (new Phone)->countryField('phone_country')->international()],
            'currency' => ['required', 'string', Rule::enum(Currency::class)],
            'contacts' => ['required', 'array', 'min:1'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['required', 'email', 'max:255'],
            'contacts.*.receives_emails' => ['sometimes', 'boolean'],
        ];
    }
}
