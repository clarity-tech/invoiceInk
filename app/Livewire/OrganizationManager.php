<?php

namespace App\Livewire;

use App\Currency;
use App\Enums\Country;
use App\Enums\FinancialYearType;
use App\Models\Location;
use App\Models\Organization;
use App\Rules\CurrencyCode;
use App\ValueObjects\EmailCollection;
use Illuminate\Validation\Rule as ValidationRule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class OrganizationManager extends Component
{
    use WithPagination;

    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('nullable|string|max:20')]
    public string $phone = '';

    public array $emails = [''];

    #[Rule('nullable|string|max:255')]
    public string $location_name = '';

    #[Rule('nullable|string|max:50')]
    public string $gstin = '';

    #[Rule('required|string|max:500')]
    public string $address_line_1 = '';

    #[Rule('nullable|string|max:500')]
    public string $address_line_2 = '';

    #[Rule('required|string|max:100')]
    public string $city = '';

    #[Rule('required|string|max:100')]
    public string $state = '';

    #[Rule('required|string|max:3')]
    public string $country = '';

    #[Rule('required|string|max:20')]
    public string $postal_code = '';

    public string $currency = '';

    public ?string $country_code = null;

    public ?string $financial_year_type = null;

    public int $financial_year_start_month = 4;

    public int $financial_year_start_day = 1;

    public bool $showForm = false;

    public ?int $editingId = null;

    public function addEmailField(): void
    {
        $this->emails[] = '';
    }

    public function updatedCountryCode(): void
    {
        if ($this->country_code) {
            try {
                $country = Country::from($this->country_code);

                // Always set currency to country's default when country changes
                // This ensures currency is always aligned with the selected country
                $this->currency = $country->getDefaultCurrency()->value;

                // Auto-set location country to match organization country
                $this->country = $this->country_code;

                // Always reset financial year type based on country (regardless of previous selection)
                $defaultFYType = $country->getDefaultFinancialYearType();
                $this->financial_year_type = $defaultFYType->value;

                // Always reset the start month and day based on the financial year type
                $this->financial_year_start_month = $defaultFYType->getStartMonth();
                $this->financial_year_start_day = $defaultFYType->getStartDay();
            } catch (\ValueError $e) {
                // Invalid country code, ignore
            }
        }
    }

    public function removeEmailField(int $index): void
    {
        if (count($this->emails) > 1) {
            unset($this->emails[$index]);
            $this->emails = array_values($this->emails);
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(Organization $organization): void
    {
        // Security check: Ensure user has access to this organization
        if (! auth()->user()->allTeams()->contains('id', $organization->id)) {
            abort(403, 'Unauthorized access to organization.');
        }

        $organization->load('primaryLocation');

        $this->editingId = $organization->id;
        $this->name = $organization->name;
        $this->phone = $organization->phone ?? '';
        $this->emails = $organization->emails->toArray() ?: [''];
        $this->currency = $organization->currency?->value ?? Currency::default()->value;
        $this->country_code = $organization->country_code?->value ?? null;
        $this->financial_year_type = $organization->financial_year_type?->value ?? null;
        $this->financial_year_start_month = $organization->financial_year_start_month ?? 4;
        $this->financial_year_start_day = $organization->financial_year_start_day ?? 1;

        if ($organization->primaryLocation) {
            $this->location_name = $organization->primaryLocation->name;
            $this->gstin = $organization->primaryLocation->gstin ?? '';
            $this->address_line_1 = $organization->primaryLocation->address_line_1;
            $this->address_line_2 = $organization->primaryLocation->address_line_2 ?? '';
            $this->city = $organization->primaryLocation->city;
            $this->state = $organization->primaryLocation->state;
            $this->country = $organization->primaryLocation->country;
            $this->postal_code = $organization->primaryLocation->postal_code;
        }

        $this->showForm = true;
    }

    public function save(): void
    {
        // Ensure location country matches organization country
        if ($this->country_code) {
            $this->country = $this->country_code;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'currency' => ['required', 'string', new CurrencyCode],
            'country_code' => ['required', 'string', ValidationRule::enum(Country::class)],
            'financial_year_type' => ['nullable', 'string', ValidationRule::enum(FinancialYearType::class)],
            'financial_year_start_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'financial_year_start_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'location_name' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|max:50',
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => ['required', 'string', ValidationRule::enum(Country::class)],
            'postal_code' => 'required|string|max:20',
            'emails' => 'required|array|min:1',
            'emails.*' => 'nullable|email',
        ]);

        // Additional validation: ensure currency is supported by the selected country
        if ($this->country_code && $this->currency) {
            try {
                $country = Country::from($this->country_code);
                $supportedCurrencies = collect($country->getSupportedCurrencies())->pluck('value')->toArray();

                if (! in_array($this->currency, $supportedCurrencies)) {
                    $this->addError('currency', 'The selected currency is not supported by '.$country->name().'. Supported currencies: '.implode(', ', $supportedCurrencies));

                    return;
                }
            } catch (\ValueError $e) {
                // Invalid country code, but this will be caught by country_code validation above
            }
        }

        $filteredEmails = array_filter($this->emails, fn ($email) => ! empty(trim($email)));

        if (empty($filteredEmails)) {
            $this->addError('emails.0', 'At least one email is required.');

            return;
        }

        $emailCollection = new EmailCollection($filteredEmails);

        if ($this->editingId) {
            $organization = Organization::findOrFail($this->editingId);
            $organization->update([
                'name' => $this->name,
                'phone' => $this->phone ?: null,
                'emails' => $emailCollection,
                'currency' => $this->currency,
                'country_code' => $this->country_code,
                'financial_year_type' => $this->financial_year_type,
                'financial_year_start_month' => $this->financial_year_start_month,
                'financial_year_start_day' => $this->financial_year_start_day,
            ]);

            if ($organization->primaryLocation) {
                $organization->primaryLocation->update([
                    'name' => $this->location_name ?: ($this->name ?: 'Main Office'),
                    'gstin' => $this->gstin ?: null,
                    'address_line_1' => $this->address_line_1,
                    'address_line_2' => $this->address_line_2 ?: null,
                    'city' => $this->city,
                    'state' => $this->state,
                    'country' => $this->country,
                    'postal_code' => $this->postal_code,
                ]);
            }
        } else {
            $location = Location::create([
                'name' => $this->location_name ?: ($this->name ?: 'Main Office'),
                'gstin' => $this->gstin ?: null,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
                'locatable_type' => Organization::class,
                'locatable_id' => 0,
            ]);

            // Get smart defaults based on country
            $country = Country::from($this->country_code);
            $defaultFinancialYearType = $this->financial_year_type ?: $country->getDefaultFinancialYearType()->value;
            $defaultCurrency = $this->currency ?: $country->getDefaultCurrency()->value;

            $organization = Organization::create([
                'name' => $this->name,
                'user_id' => auth()->id(),
                'personal_team' => false,
                'phone' => $this->phone ?: null,
                'emails' => $emailCollection,
                'primary_location_id' => $location->id,
                'currency' => $defaultCurrency,
                'country_code' => $this->country_code,
                'financial_year_type' => $defaultFinancialYearType,
                'financial_year_start_month' => $this->financial_year_start_month,
                'financial_year_start_day' => $this->financial_year_start_day,
            ]);

            $location->update([
                'locatable_id' => $organization->id,
            ]);
        }

        $this->resetForm();
        $this->showForm = false;
        $this->resetPage();

        session()->flash('message', $this->editingId ? 'Organization updated successfully!' : 'Organization created successfully!');
    }

    public function delete(Organization $organization): void
    {
        // Security check: Ensure user has access to this organization
        if (! auth()->user()->allTeams()->contains('id', $organization->id)) {
            abort(403, 'Unauthorized access to organization.');
        }

        // Handle foreign key constraint by setting primary_location_id to null first
        $organization->primary_location_id = null;
        $organization->save();

        // Then delete locations and organization
        $organization->locations()->delete();
        $organization->delete();

        $this->resetPage();
        session()->flash('message', 'Organization deleted successfully!');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->phone = '';
        $this->emails = [''];
        $this->currency = Currency::default()->value;
        $this->location_name = '';
        $this->gstin = '';
        $this->address_line_1 = '';
        $this->address_line_2 = '';
        $this->city = '';
        $this->state = '';
        $this->country = '';
        $this->postal_code = '';
        $this->country_code = null;
        $this->financial_year_type = null;
        $this->financial_year_start_month = 4;
        $this->financial_year_start_day = 1;
        $this->resetValidation();
    }

    #[Computed]
    public function organizations()
    {
        // Only show organizations that the user has access to (their teams)
        if (! auth()->check()) {
            return collect();
        }

        // Get all teams the user is a member of or owns
        $userTeamIds = auth()->user()->allTeams()->pluck('id');

        return Organization::with('primaryLocation')
            ->whereIn('id', $userTeamIds)
            ->latest()
            ->paginate(10);
    }

    #[Computed]
    public function availableCountries()
    {
        return collect(Country::cases())->map(function ($country) {
            return [
                'value' => $country->value,
                'label' => $country->flag().' '.$country->name(),
                'currency' => $country->getDefaultCurrency()->value,
                'financial_year_options' => $country->getFinancialYearOptions(),
                'default_financial_year' => $country->getDefaultFinancialYearType()->value,
            ];
        });
    }

    #[Computed]
    public function selectedCountryInfo()
    {
        if (! $this->country_code) {
            return null;
        }

        try {
            $country = Country::from($this->country_code);

            return [
                'financial_year_options' => $country->getFinancialYearOptions(),
                'default_currency' => $country->getDefaultCurrency()->value,
                'tax_system' => $country->getTaxSystemInfo(),
                'recommended_numbering' => $country->getRecommendedNumberingFormat(),
            ];
        } catch (\ValueError $e) {
            return null;
        }
    }

    #[Computed]
    public function availableCurrencies()
    {
        if (! $this->country_code) {
            // If no country selected, show all currencies
            return \App\Currency::options();
        }

        try {
            $country = Country::from($this->country_code);
            $supportedCurrencies = $country->getSupportedCurrencies();

            return collect($supportedCurrencies)
                ->mapWithKeys(fn ($currency) => [
                    $currency->value => $currency->name().' ('.$currency->symbol().')',
                ])
                ->toArray();
        } catch (\ValueError $e) {
            // Invalid country code, fallback to all currencies
            return \App\Currency::options();
        }
    }

    public function render()
    {
        return view('livewire.organization-manager')
            ->layout('layouts.app', ['title' => 'Organizations']);
    }
}
