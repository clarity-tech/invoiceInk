<?php

namespace App\Http\Controllers;

use App\Currency;
use App\Enums\Country;
use App\Enums\FinancialYearType;
use App\Models\Location;
use App\Models\Organization;
use App\Rules\CurrencyCode;
use App\ValueObjects\BankDetails;
use App\ValueObjects\ContactCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $userTeamIds = $user->allTeams()->pluck('id');

        $organizations = Organization::with('primaryLocation')
            ->whereIn('id', $userTeamIds)
            ->latest()
            ->paginate(10);

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

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
            'countries' => $countries,
            'currencies' => Currency::options(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeAccess($request, $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[+]?[\d\s\-().]+$/'],
            'emails' => ['required', 'array', 'min:1'],
            'emails.*' => ['nullable', 'email'],
            'currency' => ['required', 'string', new CurrencyCode],
            'country_code' => ['required', 'string', ValidationRule::enum(Country::class)],
            'financial_year_type' => ['nullable', 'string', ValidationRule::enum(FinancialYearType::class)],
            'financial_year_start_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'financial_year_start_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'tax_number' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255', 'url'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->validateCurrencyCountryMatch($validated['country_code'] ?? null, $validated['currency'] ?? null);

        $filteredEmails = array_filter(
            array_map(fn ($email) => is_string($email) ? trim($email) : '', $validated['emails'] ?? []),
            fn ($email) => $email !== ''
        );

        if (empty($filteredEmails)) {
            throw ValidationException::withMessages([
                'emails.0' => __('forms.validation.at_least_one_email'),
            ]);
        }

        $contactCollection = new ContactCollection(
            array_map(fn ($email) => ['name' => '', 'email' => $email], $filteredEmails)
        );

        $organization->update([
            'name' => $validated['name'],
            'phone' => ($validated['phone'] ?? '') ?: null,
            'emails' => $contactCollection,
            'currency' => $validated['currency'],
            'country_code' => $validated['country_code'],
            'financial_year_type' => $validated['financial_year_type'] ?? null,
            'financial_year_start_month' => $validated['financial_year_start_month'] ?? null,
            'financial_year_start_day' => $validated['financial_year_start_day'] ?? null,
            'tax_number' => ($validated['tax_number'] ?? '') ?: null,
            'registration_number' => ($validated['registration_number'] ?? '') ?: null,
            'website' => ($validated['website'] ?? '') ?: null,
            'notes' => ($validated['notes'] ?? '') ?: null,
        ]);

        return back()->with('message', __('messages.notifications.organization_updated'));
    }

    public function updateLocation(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeAccess($request, $organization);

        $validated = $request->validate([
            'location_name' => ['nullable', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:500'],
            'address_line_2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', ValidationRule::enum(Country::class)],
            'postal_code' => ['required', 'string', 'max:20'],
        ]);

        $locationData = [
            'name' => ($validated['location_name'] ?? '') ?: ($organization->name ?: 'Main Office'),
            'gstin' => ($validated['gstin'] ?? '') ?: null,
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => ($validated['address_line_2'] ?? '') ?: null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
            'postal_code' => $validated['postal_code'],
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

        return back()->with('message', __('messages.notifications.organization_updated'));
    }

    public function updateBankDetails(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeAccess($request, $organization);

        $validated = $request->validate([
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_ifsc' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_swift' => ['nullable', 'string', 'max:255'],
            'bank_pan' => ['nullable', 'string', 'max:255'],
        ]);

        $organization->update([
            'bank_details' => new BankDetails(
                accountName: $validated['bank_account_name'] ?? '',
                accountNumber: $validated['bank_account_number'] ?? '',
                bankName: $validated['bank_name'] ?? '',
                ifsc: $validated['bank_ifsc'] ?? '',
                branch: $validated['bank_branch'] ?? '',
                swift: $validated['bank_swift'] ?? '',
                pan: $validated['bank_pan'] ?? '',
            ),
        ]);

        return back()->with('message', __('messages.notifications.organization_updated'));
    }

    public function uploadLogo(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeAccess($request, $organization);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        $organization->addMedia($request->file('logo'))
            ->toMediaCollection('logo');

        return back()->with('message', __('messages.notifications.organization_updated'));
    }

    public function removeLogo(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeAccess($request, $organization);

        $organization->clearMediaCollection('logo');

        return back()->with('message', __('messages.notifications.organization_updated'));
    }

    public function destroy(Request $request, Organization $organization): RedirectResponse
    {
        $user = $request->user();

        if (! $user->ownsTeam($organization)) {
            abort(403, __('messages.authorization.unauthorized_organization'));
        }

        if ($organization->personal_team) {
            return back()->with('error', 'Personal organizations cannot be deleted.');
        }

        $organization->primary_location_id = null;
        $organization->save();
        $organization->locations()->delete();
        $organization->delete();

        return redirect()->route('organizations.index')
            ->with('message', __('messages.notifications.organization_deleted'));
    }

    private function authorizeAccess(Request $request, Organization $organization): void
    {
        $user = $request->user();
        $hasAccess = $user->allTeams()->contains('id', $organization->id);

        if (! $hasAccess) {
            abort(403, __('messages.authorization.no_permission_edit_organization'));
        }
    }

    private function validateCurrencyCountryMatch(?string $countryCode, ?string $currency): void
    {
        if (! $countryCode || ! $currency) {
            return;
        }

        try {
            $country = Country::from($countryCode);
            $supportedCurrencies = collect($country->getSupportedCurrencies())->pluck('value')->toArray();

            if (! in_array($currency, $supportedCurrencies)) {
                throw ValidationException::withMessages([
                    'currency' => __('forms.validation.currency_not_supported', [
                        'country' => $country->name(),
                        'currencies' => implode(', ', $supportedCurrencies),
                    ]),
                ]);
            }
        } catch (\ValueError) {
            // Invalid country code handled by validation rules
        }
    }
}
