<?php

namespace App\Http\Controllers;

use App\Currency;
use App\Enums\Country;
use App\Enums\FinancialYearType;
use App\Enums\ResetFrequency;
use App\Models\Location;
use App\Models\Organization;
use App\Rules\CurrencyCode;
use App\Services\SvgSanitizer;
use App\Support\Jetstream;
use App\Support\JetstreamFeatures;
use App\Support\PhoneNumber;
use App\ValueObjects\BankDetails;
use App\ValueObjects\ContactCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule as ValidationRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Propaganistas\LaravelPhone\Rules\Phone;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $teams = $user->allTeams();

        $organizations = Organization::with('primaryLocation')
            ->whereIn('id', $teams->pluck('id'))
            ->latest()
            ->paginate(10);

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
        ]);
    }

    public function show(Request $request, Organization $organization): Response
    {
        // Viewing the org overview only requires membership (any role).
        abort_unless($request->user()->belongsToTeam($organization), 403, __('messages.authorization.no_permission_edit_organization'));

        $organization->load('primaryLocation');

        $numberingSeries = $organization->numberingSeries()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return Inertia::render('Organizations/Show', [
            'organization' => $organization,
            'numberingSeries' => $numberingSeries,
            'canManage' => $request->user()->hasTeamPermission($organization, 'manage'),
            'taxLabels' => Country::taxLabels(),
        ]);
    }

    public function edit(Request $request, Organization $organization): Response
    {
        $this->authorizeAccess($request, $organization);

        $organization->load(['primaryLocation', 'owner', 'users', 'teamInvitations']);

        $numberingSeries = $organization->numberingSeries()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return Inertia::render('Organizations/Edit', [
            'organization' => $organization,
            'countries' => $this->getCountryOptions(),
            'currencies' => Currency::options(),
            'tab' => $request->query('tab', 'basics'),
            'numberingSeries' => $numberingSeries,
            'resetFrequencyOptions' => ResetFrequency::getOptions(),
            'availableRoles' => Jetstream::availableRoles(),
            'memberPermissions' => [
                'canAddTeamMembers' => Gate::check('addTeamMember', $organization),
                'canDeleteTeam' => Gate::check('delete', $organization),
                'canRemoveTeamMembers' => Gate::check('removeTeamMember', $organization),
                'canUpdateTeam' => Gate::check('update', $organization),
                'canUpdateTeamMembers' => Gate::check('updateTeamMember', $organization),
            ],
            'hasRoles' => Jetstream::hasRoles(),
            'sendsTeamInvitations' => JetstreamFeatures::sendsTeamInvitations(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeAccess($request, $organization);

        // Accept a bare domain (e.g. "example.com") and normalize to a full URL
        // before validation so the `url` rule doesn't reject scheme-less input.
        $request->merge(['website' => Organization::normalizeWebsite($request->input('website'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone_country' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', (new Phone)->countryField('phone_country')->international()],
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
            'ai_drafting_enabled' => ['sometimes', 'boolean'],
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

        $attributes = [
            'name' => $validated['name'],
            'phone' => PhoneNumber::normalize($validated['phone'] ?? null, $validated['phone_country'] ?? null),
            'phone_country' => $validated['phone_country'] ?? null,
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
        ];

        // Only touch company_name when the form actually submits it, so callers
        // that omit the field don't accidentally clear the legal business name.
        if ($request->has('company_name')) {
            $attributes['company_name'] = ($validated['company_name'] ?? '') ?: null;
        }

        if ($request->has('ai_drafting_enabled')) {
            $attributes['ai_drafting_enabled'] = $request->boolean('ai_drafting_enabled');
        }

        $organization->update($attributes);

        return back()->with('message', __('messages.notifications.organization_updated'));
    }

    public function updateLocation(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizeAccess($request, $organization);

        $validated = $request->validate([
            'location_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['required', 'string', 'max:500'],
            'address_line_2' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', ValidationRule::enum(Country::class)],
            'postal_code' => ['required', 'string', 'max:20'],
        ]);

        $locationData = [
            'name' => ($validated['location_name'] ?? '') ?: ($organization->name ?: 'Main Office'),
            'tax_id' => ($validated['tax_id'] ?? '') ?: null,
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

    public function uploadLogo(Request $request, Organization $organization, SvgSanitizer $svgSanitizer): RedirectResponse
    {
        $this->authorizeAccess($request, $organization);

        $request->validate([
            'logo' => ['required', 'image:allow_svg', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ]);

        $file = $request->file('logo');

        if (! $svgSanitizer->sanitizeInPlace($file)) {
            throw ValidationException::withMessages([
                'logo' => __('forms.validation.invalid_svg'),
            ]);
        }

        $organization->addMedia($file)
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

        if ($user->ownedTeams()->count() <= 1) {
            return back()->with('error', __('messages.notifications.cannot_delete_last_organization'));
        }

        if ($user->current_team_id === $organization->id) {
            $next = $user->ownedTeams()->where('id', '!=', $organization->id)->first()
                ?? $user->teams()->where('teams.id', '!=', $organization->id)->first();

            if ($next) {
                $user->switchTeam($next);
            } else {
                $user->forceFill(['current_team_id' => null])->save();
            }
        }

        // Soft delete only: leave the organization's locations and child data
        // (invoices, customers, ...) intact so it can be restored. Hard-deleting
        // here would also fire the cascade FKs and permanently destroy that data.
        $organization->delete();

        return redirect()->route('organizations.index')
            ->with('message', __('messages.notifications.organization_deleted'));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getCountryOptions(): Collection
    {
        return collect(Country::cases())->map(fn ($country) => [
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
    }

    /**
     * Editing organization settings requires the "manage" permission (owner/admin).
     */
    private function authorizeAccess(Request $request, Organization $organization): void
    {
        $user = $request->user();

        if (! $user->hasTeamPermission($organization, 'manage')) {
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
