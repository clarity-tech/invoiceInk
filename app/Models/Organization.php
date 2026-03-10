<?php

namespace App\Models;

use App\Casts\BankDetailsCast;
use App\Casts\ContactCollectionCast;
use App\Currency;
use App\Enums\Country;
use App\Enums\FinancialYearType;
use Carbon\Carbon;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Organization extends Model implements HasMedia
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * The table associated with the model.
     */
    protected $table = 'teams';

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return OrganizationFactory::new();
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
        'bank_details',
        'primary_location_id',
        'custom_domain',
        'country_code',
        'financial_year_type',
        'financial_year_start_month',
        'financial_year_start_day',
        'setup_completed_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'logo_url',
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
            'emails' => ContactCollectionCast::class,
            'currency' => Currency::class,
            'country_code' => Country::class,
            'financial_year_type' => FinancialYearType::class,
            'financial_year_start_month' => 'integer',
            'financial_year_start_day' => 'integer',
            'bank_details' => BankDetailsCast::class,
            'setup_completed_at' => 'datetime',
        ];
    }

    /**
     * Get the owner of the team.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all of the team's users including its owner.
     */
    public function allUsers(): Collection
    {
        return $this->users->merge([$this->owner]);
    }

    /**
     * Determine if the given user belongs to the team.
     */
    public function hasUser($user): bool
    {
        return $this->users->contains($user) || $user->ownsTeam($this);
    }

    /**
     * Determine if the given email address belongs to a user on the team.
     */
    public function hasUserWithEmail(string $email): bool
    {
        return $this->allUsers()->contains(function ($user) use ($email) {
            return $user->email === $email;
        });
    }

    /**
     * Determine if the given user has the given permission on the team.
     */
    public function userHasPermission($user, $permission): bool
    {
        return $user->hasTeamPermission($this, $permission);
    }

    /**
     * Remove the given user from the team.
     */
    public function removeUser($user): void
    {
        if ($user->current_team_id === $this->id) {
            $user->forceFill([
                'current_team_id' => null,
            ])->save();
        }

        $this->users()->detach($user);
    }

    /**
     * Purge all of the team's resources.
     */
    public function purge(): void
    {
        $this->owner()->where('current_team_id', $this->id)
            ->update(['current_team_id' => null]);

        $this->users()->where('current_team_id', $this->id)
            ->update(['current_team_id' => null]);

        $this->users()->detach();

        $this->delete();
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

    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
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
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            Membership::class,
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
     * @return HasMany
     */
    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'team_id');
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

    /**
     * Check if the organization has bank details configured.
     */
    public function hasBankDetails(): bool
    {
        return $this->bank_details?->isConfigured() ?? false;
    }

    /**
     * Check if the organization setup is complete.
     */
    public function isSetupComplete(): bool
    {
        return ! is_null($this->setup_completed_at);
    }

    /**
     * Mark the organization setup as complete.
     */
    public function markSetupComplete(): void
    {
        $this->update([
            'setup_completed_at' => now(),
        ]);
    }

    /**
     * Check if the organization needs setup completion.
     */
    public function needsSetup(): bool
    {
        // Personal teams typically don't need full setup
        if ($this->personal_team) {
            return false;
        }

        return is_null($this->setup_completed_at);
    }

    /**
     * Register media collections for the organization.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->useDisk('public');
    }

    /**
     * Register media conversions for optimized logo sizes.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(200)
            ->height(200)
            ->nonQueued();
    }

    /**
     * Get the organization's logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        $logo = $this->getFirstMedia('logo');

        return $logo ? $logo->getUrl() : null;
    }

    /**
     * Get the organization's logo thumbnail URL.
     */
    public function getLogoThumbUrlAttribute(): ?string
    {
        $logo = $this->getFirstMedia('logo');

        return $logo ? $logo->getUrl('thumb') : null;
    }

    /**
     * Get the organization's logo as base64 for PDF embedding.
     */
    public function getLogoBase64Attribute(): ?string
    {
        $logo = $this->getFirstMedia('logo');

        if (! $logo) {
            return null;
        }

        $path = $logo->getPath();

        if (! file_exists($path)) {
            return null;
        }

        $mimeType = $logo->mime_type;
        $content = file_get_contents($path);

        return 'data:'.$mimeType.';base64,'.base64_encode($content);
    }

    /**
     * Get the setup completion percentage.
     */
    public function getSetupCompletionPercentage(): int
    {
        if ($this->isSetupComplete()) {
            return 100;
        }

        $requiredFields = [
            'company_name' => ! empty($this->company_name),
            'emails' => $this->emails && $this->emails->count() > 0,
            'primary_location' => $this->primary_location_id && $this->primaryLocation,
            'currency' => ! empty($this->currency),
            'country_code' => ! empty($this->country_code),
            'financial_year_type' => ! empty($this->financial_year_type),
        ];

        $completedCount = count(array_filter($requiredFields));
        $totalCount = count($requiredFields);

        return (int) round(($completedCount / $totalCount) * 100);
    }

    /**
     * Get missing setup fields.
     */
    public function getMissingSetupFields(): array
    {
        if ($this->isSetupComplete()) {
            return [];
        }

        $missingFields = [];

        if (empty($this->company_name)) {
            $missingFields[] = 'Company Information';
        }

        if (! $this->emails || $this->emails->count() === 0) {
            $missingFields[] = 'Contact Emails';
        }

        if (! $this->primary_location_id || ! $this->primaryLocation) {
            $missingFields[] = 'Primary Location';
        }

        if (empty($this->currency)) {
            $missingFields[] = 'Currency';
        }

        if (empty($this->country_code)) {
            $missingFields[] = 'Country';
        }

        if (empty($this->financial_year_type)) {
            $missingFields[] = 'Financial Year Configuration';
        }

        return $missingFields;
    }
}
