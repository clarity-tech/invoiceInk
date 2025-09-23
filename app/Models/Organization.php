<?php

namespace App\Models;

use App\Casts\EmailCollectionCast;
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
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'emails' => EmailCollectionCast::class,
            'currency' => \App\Currency::class,
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
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            default => $this->currency,
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
}
