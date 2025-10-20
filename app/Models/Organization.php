<?php

namespace App\Models;

use App\Casts\EmailCollectionCast;
use App\Currency;
use App\Enums\Country;
use App\Enums\FinancialYearType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Organization extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'teams';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Database\Factories\OrganizationFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_id',
        'personal_team',
        'company_name',
        'tax_number',
        'registration_number',
        'emails',
        'phone',
        'website',
        'currency',
        'notes',
        'primary_location_id',
        'custom_domain',
        'country_code',
        'financial_year_type',
        'financial_year_start_month',
        'financial_year_start_day',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * The model's default attribute values.
     */
    protected $attributes = [];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'emails' => EmailCollectionCast::class,
            'currency' => \App\Currency::class,
            'country_code' => Country::class,
            'financial_year_type' => FinancialYearType::class,
            'financial_year_start_month' => 'integer',
            'financial_year_start_day' => 'integer',
        ];
    }

    /**
     * Get the organization's primary location.
     */
    public function primaryLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'primary_location_id');
    }

    /**
     * Get all customers for this organization.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get all invoices for this organization.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all tax templates for this organization.
     */
    public function taxTemplates(): HasMany
    {
        return $this->hasMany(TaxTemplate::class);
    }

    /**
     * Get all invoice numbering series for this organization.
     */
    public function numberingSeries(): HasMany
    {
        return $this->hasMany(InvoiceNumberingSeries::class);
    }

    /**
     * Get all locations for this organization.
     */
    public function locations()
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    /**
     * Get the organization's public URL.
     */
    public function getUrlAttribute(): string
    {
        if ($this->custom_domain) {
            return "https://{$this->custom_domain}";
        }

        return "https://clarity-invoicing.com/organizations/{$this->id}";
    }

    /**
     * Get the display name for the organization.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }

    /**
     * Check if this is a business organization (not personal).
     */
    public function isBusinessOrganization(): bool
    {
        return ! $this->personal_team && ! empty($this->company_name);
    }

    /**
     * Get the organization's currency symbol.
     */
    public function getCurrencySymbolAttribute(): string
    {
        return match ($this->currency) {
            Currency::USD => '$',
            Currency::EUR => '€',
            Currency::GBP => '£',
            Currency::INR => '₹',
            default => $this->currency->value,
        };
    }

    /**
     * Get all of the users that belong to the organization.
     *
     * Override the JetstreamTeam method to specify correct foreign key names.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            \Laravel\Jetstream\Jetstream::userModel(),
            \Laravel\Jetstream\Jetstream::membershipModel(),
            'team_id',     // Foreign key on pivot table for Team/Organization model
            'user_id'      // Foreign key on pivot table for User model
        )->withPivot('role')
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * Get all of the pending user invitations for the organization.
     *
     * Override the JetstreamTeam method to specify correct foreign key names.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function teamInvitations()
    {
        return $this->hasMany(\Laravel\Jetstream\Jetstream::teamInvitationModel(), 'team_id');
    }

    /**
     * Get the current financial year for this organization.
     */
    public function getCurrentFinancialYear(?Carbon $date = null): string
    {
        $financialYearType = $this->financial_year_type ?? FinancialYearType::APRIL_MARCH;

        return $financialYearType->getCurrentFinancialYear($date);
    }

    /**
     * Get the financial year label for this organization.
     */
    public function getFinancialYearLabel(?Carbon $date = null): string
    {
        $financialYearType = $this->financial_year_type ?? FinancialYearType::APRIL_MARCH;

        return $financialYearType->getFinancialYearLabel($date);
    }

    /**
     * Get the financial year start date for this organization.
     */
    public function getFinancialYearStartDate(?int $year = null): Carbon
    {
        $financialYearType = $this->financial_year_type ?? FinancialYearType::APRIL_MARCH;

        return $financialYearType->getFinancialYearStartDate($year);
    }

    /**
     * Get the financial year end date for this organization.
     */
    public function getFinancialYearEndDate(?int $year = null): Carbon
    {
        $financialYearType = $this->financial_year_type ?? FinancialYearType::APRIL_MARCH;

        return $financialYearType->getFinancialYearEndDate($year);
    }

    /**
     * Get the next financial year reset date for this organization.
     */
    public function getNextFinancialYearResetDate(): Carbon
    {
        $currentFYStart = $this->getFinancialYearStartDate();
        $currentFYEnd = $this->getFinancialYearEndDate();

        // If we're past the current financial year end, return next year's start
        if (now()->gt($currentFYEnd)) {
            return $this->getFinancialYearStartDate(now()->year + 1);
        }

        // If we're before the current financial year start, return current year's start
        if (now()->lt($currentFYStart)) {
            return $currentFYStart;
        }

        // We're in the current financial year, so return next year's start
        return $this->getFinancialYearStartDate($currentFYStart->year + 1);
    }

    /**
     * Check if the financial year has changed since the last reset.
     */
    public function hasFinancialYearChanged(Carbon $lastResetDate, ?Carbon $currentDate = null): bool
    {
        $financialYearType = $this->financial_year_type ?? FinancialYearType::APRIL_MARCH;

        return $financialYearType->hasFinancialYearChanged($lastResetDate, $currentDate);
    }

    /**
     * Get the country information for this organization.
     */
    public function getCountryAttribute(): ?Country
    {
        return $this->country_code;
    }

    /**
     * Get setup recommendations for this organization based on its country.
     */
    public function getSetupRecommendations(): array
    {
        if (! $this->country_code) {
            return [];
        }

        $country = $this->country_code;
        $taxInfo = $country->getTaxSystemInfo();
        $fyOptions = $country->getFinancialYearOptions();
        $defaultFY = $country->getDefaultFinancialYearType();

        return [
            'currency' => $country->getDefaultCurrency(),
            'financial_year_type' => $defaultFY,
            'recommended_numbering_format' => $country->getRecommendedNumberingFormat(),
            'tax_system' => $taxInfo['name'],
            'common_tax_rates' => $taxInfo['rates'],
            'financial_year_options' => $fyOptions,
            'default_financial_year_option' => $fyOptions[$defaultFY->value] ?? null,
        ];
    }

    /**
     * Get the tax system information for this organization's country.
     */
    public function getTaxSystemInfo(): array
    {
        if (! $this->country_code) {
            return [];
        }

        return $this->country_code->getTaxSystemInfo();
    }
}
