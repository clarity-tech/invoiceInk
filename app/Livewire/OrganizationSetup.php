<?php

namespace App\Livewire;

use App\Currency;
use App\Enums\Country;
use App\Enums\FinancialYearType;
use App\Models\Location;
use App\Models\Organization;
use App\Rules\CurrencyCode;
use App\ValueObjects\ContactCollection;
use Illuminate\Validation\Rule as ValidationRule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;

class OrganizationSetup extends Component
{
    public int $currentStep = 1;

    public int $totalSteps = 4;

    public ?Organization $organization = null;

    // Step 1: Company Information
    #[Rule('required|string|max:255')]
    public string $company_name = '';

    #[Rule('nullable|string|max:255')]
    public string $tax_number = '';

    #[Rule('nullable|string|max:255')]
    public string $registration_number = '';

    #[Rule('nullable|string|max:255|url')]
    public string $website = '';

    #[Rule('nullable|string|max:1000')]
    public string $notes = '';

    // Step 2: Primary Location
    #[Rule('required|string|max:255')]
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

    #[Rule('required|string|max:20')]
    public string $postal_code = '';

    // Step 3: Currency & Financial Year Configuration
    public string $currency = '';

    #[Rule('required|string')]
    public ?string $country_code = null;

    public ?string $financial_year_type = null;

    public int $financial_year_start_month = 4;

    public int $financial_year_start_day = 1;

    // Step 4: Contact Information
    public array $emails = [''];

    #[Rule('nullable|string|max:20')]
    public string $phone = '';

    public function mount(?Organization $organization): void
    {
        // Get current team as organization or create new one
        $this->organization = $organization ?? auth()->user()->currentTeam;

        if (! $this->organization) {
            abort(403, 'No organization context available.');
        }

        // If organization already has setup completed, we'll handle this in the view
        // Cannot redirect from mount method due to void return type

        $this->loadExistingData();
    }

    private function loadExistingData(): void
    {
        if ($this->organization) {
            // Load existing data if available
            $this->company_name = $this->organization->company_name ?? '';
            $this->tax_number = $this->organization->tax_number ?? '';
            $this->registration_number = $this->organization->registration_number ?? '';
            $this->website = $this->organization->website ?? '';
            $this->notes = $this->organization->notes ?? '';
            $this->phone = $this->organization->phone ?? '';
            $this->emails = $this->organization->emails ? $this->organization->emails->getEmails() : [''];
            $this->currency = $this->organization->currency?->value ?? Currency::default()->value;
            $this->country_code = $this->organization->country_code?->value ?? null;
            $this->financial_year_type = $this->organization->financial_year_type?->value ?? null;
            $this->financial_year_start_month = $this->organization->financial_year_start_month ?? 4;
            $this->financial_year_start_day = $this->organization->financial_year_start_day ?? 1;

            // Load primary location data if exists
            if ($this->organization->primaryLocation) {
                $location = $this->organization->primaryLocation;
                $this->location_name = $location->name;
                $this->gstin = $location->gstin ?? '';
                $this->address_line_1 = $location->address_line_1;
                $this->address_line_2 = $location->address_line_2 ?? '';
                $this->city = $location->city;
                $this->state = $location->state;
                $this->postal_code = $location->postal_code;
            }
        }
    }

    public function updatedCountryCode(): void
    {
        if ($this->country_code) {
            try {
                $country = Country::from($this->country_code);

                // Auto-set currency to country's default when country changes
                $this->currency = $country->getDefaultCurrency()->value;

                // Auto-set financial year type based on country
                $defaultFYType = $country->getDefaultFinancialYearType();
                $this->financial_year_type = $defaultFYType->value;

                // Set the start month and day based on the financial year type
                $this->financial_year_start_month = $defaultFYType->getStartMonth();
                $this->financial_year_start_day = $defaultFYType->getStartDay();
            } catch (\ValueError $e) {
                // Invalid country code, ignore
            }
        }
    }

    public function addEmailField(): void
    {
        $this->emails[] = '';
    }

    public function removeEmailField(int $index): void
    {
        if (count($this->emails) > 1) {
            unset($this->emails[$index]);
            $this->emails = array_values($this->emails);
        }
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        // Validate all steps up to the target step
        for ($i = 1; $i < $step; $i++) {
            $this->validateStep($i);
        }

        $this->currentStep = $step;
    }

    private function validateCurrentStep(): void
    {
        $this->validateStep($this->currentStep);
    }

    private function validateStep(int $step): void
    {
        match ($step) {
            1 => $this->validate([
                'company_name' => 'required|string|max:255',
                'tax_number' => 'nullable|string|max:255',
                'registration_number' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255|url',
                'notes' => 'nullable|string|max:1000',
            ]),
            2 => $this->validate([
                'location_name' => 'required|string|max:255',
                'gstin' => 'nullable|string|max:50',
                'address_line_1' => 'required|string|max:500',
                'address_line_2' => 'nullable|string|max:500',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
            ]),
            3 => $this->validate([
                'currency' => ['required', 'string', new CurrencyCode],
                'country_code' => ['required', 'string', ValidationRule::enum(Country::class)],
                'financial_year_type' => ['nullable', 'string', ValidationRule::enum(FinancialYearType::class)],
                'financial_year_start_month' => ['nullable', 'integer', 'min:1', 'max:12'],
                'financial_year_start_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            ]),
            4 => $this->validate([
                'emails' => 'required|array|min:1',
                'emails.*' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
            ]),
            default => null
        };
    }

    public function completeSetup(): void
    {
        // Validate all steps
        for ($i = 1; $i <= $this->totalSteps; $i++) {
            $this->validateStep($i);
        }

        // Additional validation for currency and country compatibility
        if ($this->country_code && $this->currency) {
            try {
                $country = Country::from($this->country_code);
                $supportedCurrencies = collect($country->getSupportedCurrencies())->pluck('value')->toArray();

                if (! in_array($this->currency, $supportedCurrencies)) {
                    $this->addError('currency', 'The selected currency is not supported by '.$country->name().'. Supported currencies: '.implode(', ', $supportedCurrencies));
                    $this->currentStep = 3;

                    return;
                }
            } catch (\ValueError $e) {
                // Invalid country code, but this will be caught by country_code validation above
            }
        }

        $filteredEmails = array_filter($this->emails, fn ($email) => ! empty(trim($email)));

        if (empty($filteredEmails)) {
            $this->addError('emails.0', 'At least one email is required.');
            $this->currentStep = 4;

            return;
        }

        // Convert simple emails to ContactCollection format (email with empty name)
        $contactData = array_map(fn($email) => ['name' => '', 'email' => $email], $filteredEmails);
        $contactCollection = new ContactCollection($contactData);

        // Create or update location
        if ($this->organization->primaryLocation) {
            $this->organization->primaryLocation->update([
                'name' => $this->location_name ?: ($this->company_name ?: 'Main Office'),
                'gstin' => $this->gstin ?: null,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country_code,
                'postal_code' => $this->postal_code,
            ]);
        } else {
            $location = Location::create([
                'name' => $this->location_name ?: ($this->company_name ?: 'Main Office'),
                'gstin' => $this->gstin ?: null,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2 ?: null,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country_code,
                'postal_code' => $this->postal_code,
                'locatable_type' => Organization::class,
                'locatable_id' => $this->organization->id,
            ]);

            $this->organization->update([
                'primary_location_id' => $location->id,
            ]);
        }

        // Get smart defaults based on country
        $country = Country::from($this->country_code);
        $defaultFinancialYearType = $this->financial_year_type ?: $country->getDefaultFinancialYearType()->value;
        $defaultCurrency = $this->currency ?: $country->getDefaultCurrency()->value;

        // Update organization with all setup data
        $this->organization->update([
            'company_name' => $this->company_name,
            'tax_number' => $this->tax_number ?: null,
            'registration_number' => $this->registration_number ?: null,
            'website' => $this->website ?: null,
            'notes' => $this->notes ?: null,
            'emails' => $contactCollection,
            'phone' => $this->phone ?: null,
            'currency' => $defaultCurrency,
            'country_code' => $this->country_code,
            'financial_year_type' => $defaultFinancialYearType,
            'financial_year_start_month' => $this->financial_year_start_month,
            'financial_year_start_day' => $this->financial_year_start_day,
        ]);

        // Mark setup as complete
        $this->organization->markSetupComplete();

        session()->flash('message', 'Organization setup completed successfully! Welcome to your invoicing system.');

        // Redirect to dashboard
        $this->redirect(route('dashboard'));
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

    #[Computed]
    public function stepProgress(): array
    {
        return [
            1 => [
                'title' => 'Company Information',
                'description' => 'Basic company details',
                'completed' => $this->isStepCompleted(1),
            ],
            2 => [
                'title' => 'Primary Location',
                'description' => 'Main business address',
                'completed' => $this->isStepCompleted(2),
            ],
            3 => [
                'title' => 'Configuration',
                'description' => 'Currency & financial settings',
                'completed' => $this->isStepCompleted(3),
            ],
            4 => [
                'title' => 'Contact Details',
                'description' => 'Email and phone information',
                'completed' => $this->isStepCompleted(4),
            ],
        ];
    }

    private function isStepCompleted(int $step): bool
    {
        try {
            $this->validateStep($step);

            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;
        }
    }

    public function render()
    {
        // If setup is complete, redirect to dashboard
        if ($this->organization && $this->organization->isSetupComplete()) {
            $this->redirect(route('dashboard'));

            return;
        }

        return view('livewire.organization-setup')
            ->layout('layouts.app', [
                'title' => 'Organization Setup - Step '.$this->currentStep.' of '.$this->totalSteps,
            ]);
    }
}
