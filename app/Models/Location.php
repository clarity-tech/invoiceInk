<?php

namespace App\Models;

use App\Enums\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        // Per-location tax *registration* number — GSTIN (IN) / VAT (EU/GB) / TRN (UAE) / EIN (US)
        // equivalent. Distinct from the organization's `tax_number`, which is the entity-level
        // tax *identity* number (the PAN equivalent in India).
        'tax_id',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'postal_code',
        'locatable_type',
        'locatable_id',
    ];

    public function locatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Country-appropriate label for this location's tax-id (GSTIN/VAT/TRN/etc.).
     * Falls back to "Tax ID" when the country isn't a known enum case.
     */
    public function taxIdLabel(): string
    {
        $country = Country::tryFrom((string) $this->country);

        return $country?->getTaxSystemInfo()['number_format'] ?? 'Tax ID';
    }

    public function isIndian(): bool
    {
        return $this->country === Country::IN->value;
    }

    public function countryName(): string
    {
        return Country::tryFrom((string) $this->country)?->name() ?? (string) $this->country;
    }
}
