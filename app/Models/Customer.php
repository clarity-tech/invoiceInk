<?php

namespace App\Models;

use App\Casts\ContactCollectionCast;
use App\Currency;
use App\Enums\GstTreatment;
use App\Models\Scopes\OrganizationScope;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'phone_country',
        'currency',
        'place_of_supply',
        'gst_treatment',
        'emails',
        'primary_location_id',
        'organization_id',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'formatted_phone',
    ];

    protected function casts(): array
    {
        return [
            'emails' => ContactCollectionCast::class,
            'currency' => Currency::class,
            'gst_treatment' => GstTreatment::class,
        ];
    }

    public function isOverseas(): bool
    {
        return $this->gst_treatment === GstTreatment::Overseas;
    }

    /**
     * The phone number rendered in a human-readable international format
     * (e.g. "+1 415 555 2671"). Falls back to the raw stored value when the
     * E.164 string can't be parsed, and null when no phone is set.
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        return PhoneNumber::format($this->phone);
    }

    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    public function archivedLocations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable')->onlyTrashed();
    }

    public function primaryLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'primary_location_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @param Builder<Customer> $query */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }
}
