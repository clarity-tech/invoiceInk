<?php

namespace App\Http\Controllers;

use App\Currency;
use App\Enums\Country;
use App\Enums\FinancialYearType;
use App\Models\Location;
use App\Models\Organization;
use App\Rules\CurrencyCode;
use App\ValueObjects\ContactCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSetupController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $organization = $request->user()->currentTeam;

        if (! $organization) {
            abort(403, __('messages.authorization.no_organization_context'));
        }

        if ($organization->isSetupComplete()) {
            return redirect()->route('dashboard');
        }

        $organization->load('primaryLocation');

        $countries = collect(Country::cases())->map(fn ($country) => [
            'value' => $country->value,
            'label' => $country->flag().' '.$country->name(),
            'currency' => $country->getDefaultCurrency()->value,
            'financial_year_options' => $country->getFinancialYearOptions(),
            'default_financial_year' => $country->getDefaultFinancialYearType()->value,
            'supported_currencies' => collect($country->getSupportedCurrencies())
                ->mapWithKeys(fn ($c) => [$c->value => $c->name().' ('.$c->symbol().')'])
                ->toArray(),
            'tax_system' => $country->getTaxSystemInfo(),
            'recommended_numbering' => $country->getRecommendedNumberingFormat(),
        ]);

        return Inertia::render('Organizations/Setup', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'company_name' => $organization->company_name ?? '',
                'tax_number' => $organization->tax_number ?? '',
                'registration_number' => $organization->registration_number ?? '',
                'website' => $organization->website ?? '',
                'notes' => $organization->notes ?? '',
                'phone' => $organization->phone ?? '',
                'emails' => $organization->emails ? $organization->emails->getEmails() : [],
                'currency' => $organization->currency?->value ?? '',
                'country_code' => $organization->country_code?->value ?? '',
                'financial_year_type' => $organization->financial_year_type?->value ?? '',
                'financial_year_start_month' => $organization->financial_year_start_month ?? 4,
                'financial_year_start_day' => $organization->financial_year_start_day ?? 1,
                'primary_location' => $organization->primaryLocation ? [
                    'name' => $organization->primaryLocation->name,
                    'tax_id' => $organization->primaryLocation->tax_id ?? '',
                    'address_line_1' => $organization->primaryLocation->address_line_1,
                    'address_line_2' => $organization->primaryLocation->address_line_2 ?? '',
                    'city' => $organization->primaryLocation->city,
                    'state' => $organization->primaryLocation->state,
                    'postal_code' => $organization->primaryLocation->postal_code,
                ] : null,
            ],
            'countries' => $countries,
            'currencies' => Currency::options(),
        ]);
    }

    public function saveStep(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();

        // Setup mutates the same core org settings that OrganizationController gates
        // behind "manage" — so only owners/admins may run the wizard, never editors
        // or viewers.
        if (! $user->hasTeamPermission($organization, 'manage')) {
            abort(403, __('messages.authorization.no_permission_edit_organization'));
        }

        // Once setup is complete, settings changes must go through the guarded
        // organization settings screen; the wizard endpoint is no longer a valid
        // path to overwrite them.
        if ($organization->isSetupComplete()) {
            return redirect()->route('dashboard');
        }

        $step = (int) $request->input('step', 1);

        $this->validateStep($request, $step);

        match ($step) {
            1 => $this->saveStep1($request, $organization),
            2 => $this->saveStep2($request, $organization),
            3 => $this->saveStep3($request, $organization),
            4 => $this->saveStep4($request, $organization),
            default => null,
        };

        if ($step >= 4) {
            $organization->markSetupComplete();

            return redirect()->route('dashboard')
                ->with('message', __('messages.notifications.organization_setup_complete'));
        }

        return back()->with('message', 'Step saved successfully.');
    }

    private function validateStep(Request $request, int $step): void
    {
        // Accept a bare domain (e.g. "example.com") and normalize to a full URL
        // before validation so the `url` rule doesn't reject scheme-less input.
        if ($step === 1) {
            $request->merge(['website' => Organization::normalizeWebsite($request->input('website'))]);
        }

        match ($step) {
            1 => $request->validate([
                'company_name' => ['required', 'string', 'max:255'],
                'tax_number' => ['nullable', 'string', 'max:255'],
                'registration_number' => ['nullable', 'string', 'max:255'],
                'website' => ['nullable', 'string', 'max:255', 'url'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]),
            2 => $request->validate([
                'location_name' => ['nullable', 'string', 'max:255'],
                'tax_id' => ['nullable', 'string', 'max:50'],
                'country_code' => ['required', 'string', ValidationRule::enum(Country::class)],
                'address_line_1' => ['required', 'string', 'max:500'],
                'address_line_2' => ['nullable', 'string', 'max:500'],
                'city' => ['required', 'string', 'max:100'],
                'state' => ['required', 'string', 'max:100'],
                'postal_code' => ['required', 'string', 'max:20'],
            ]),
            3 => $request->validate([
                'currency' => ['required', 'string', new CurrencyCode],
                'financial_year_type' => ['nullable', 'string', ValidationRule::enum(FinancialYearType::class)],
                'financial_year_start_month' => ['nullable', 'integer', 'min:1', 'max:12'],
                'financial_year_start_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            ]),
            4 => $request->validate([
                'emails' => ['required', 'array', 'min:1'],
                'emails.*' => ['required', 'email'],
                'phone' => ['nullable', 'string', 'max:20'],
            ]),
            default => null,
        };
    }

    private function saveStep1(Request $request, Organization $organization): void
    {
        // Only set company_name (the legal/business name on invoices). The org's
        // `name` is the switcher label set at creation and must not be overwritten.
        $organization->update([
            'company_name' => $request->input('company_name'),
            'tax_number' => $request->input('tax_number') ?: null,
            'registration_number' => $request->input('registration_number') ?: null,
            'website' => $request->input('website') ?: null,
            'notes' => $request->input('notes') ?: null,
        ]);
    }

    private function saveStep2(Request $request, Organization $organization): void
    {
        $countryCode = $request->input('country_code');

        $organization->update(['country_code' => $countryCode]);

        $locationData = [
            'name' => $request->input('location_name') ?: 'Head Office',
            'tax_id' => $request->input('tax_id') ?: null,
            'address_line_1' => $request->input('address_line_1'),
            'address_line_2' => $request->input('address_line_2') ?: null,
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'country' => $countryCode,
            'postal_code' => $request->input('postal_code'),
        ];

        if ($organization->primaryLocation) {
            $organization->primaryLocation->update($locationData);
        } else {
            $location = Location::create(array_merge($locationData, [
                'locatable_type' => Organization::class,
                'locatable_id' => $organization->id,
            ]));
            $organization->update(['primary_location_id' => $location->id]);
        }
    }

    private function saveStep3(Request $request, Organization $organization): void
    {
        $countryCode = $organization->country_code?->value;
        $currency = $request->input('currency');

        if (! $countryCode) {
            throw ValidationException::withMessages([
                'currency' => __('forms.validation.country_required_before_currency'),
            ]);
        }

        $country = Country::from($countryCode);

        // Validate currency-country match
        if ($currency) {
            $supportedCurrencies = collect($country->getSupportedCurrencies())->pluck('value')->toArray();

            if (! in_array($currency, $supportedCurrencies)) {
                throw ValidationException::withMessages([
                    'currency' => __('forms.validation.currency_not_supported', [
                        'country' => $country->name(),
                        'currencies' => implode(', ', $supportedCurrencies),
                    ]),
                ]);
            }
        }

        $defaultFinancialYearType = $request->input('financial_year_type') ?: $country->getDefaultFinancialYearType()->value;
        $defaultCurrency = $currency ?: $country->getDefaultCurrency()->value;

        $organization->update([
            'currency' => $defaultCurrency,
            'financial_year_type' => $defaultFinancialYearType,
            'financial_year_start_month' => $request->input('financial_year_start_month'),
            'financial_year_start_day' => $request->input('financial_year_start_day'),
        ]);
    }

    private function saveStep4(Request $request, Organization $organization): void
    {
        $filteredEmails = array_filter(
            array_map(fn ($email) => is_string($email) ? trim($email) : '', $request->input('emails', [])),
            fn ($email) => $email !== ''
        );

        if (empty($filteredEmails)) {
            throw ValidationException::withMessages([
                'emails.0' => __('forms.validation.at_least_one_email'),
            ]);
        }

        $contactData = array_map(fn ($email) => ['name' => '', 'email' => $email], $filteredEmails);
        $contactCollection = new ContactCollection($contactData);

        $organization->update([
            'emails' => $contactCollection,
            'phone' => $request->input('phone') ?: null,
        ]);
    }
}
