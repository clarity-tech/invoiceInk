<?php

namespace App\Http\Requests;

use App\Currency;
use App\Enums\GstTreatment;
use App\Enums\IndianState;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Customer::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_country' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', (new Phone)->countryField('phone_country')->international()],
            'currency' => ['required', 'string', Rule::enum(Currency::class)],
            'place_of_supply' => ['nullable', Rule::enum(IndianState::class)],
            'gst_treatment' => ['nullable', Rule::enum(GstTreatment::class)],
            'contacts' => ['required', 'array', 'min:1'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['required', 'email', 'max:255'],
            'contacts.*.receives_emails' => ['sometimes', 'boolean'],
        ];
    }
}
