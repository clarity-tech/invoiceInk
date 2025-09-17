<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'rate',
        'category',
        'country_code',
        'description',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
            'is_active' => 'boolean',
            'metadata' => 'json',
        ];
    }

    /**
     * Get the organization that owns this tax template.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope to filter active tax templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope to filter by tax type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the formatted tax rate as a percentage.
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->rate, 2).'%';
    }

    /**
     * Check if this is a GST tax template.
     */
    public function isGST(): bool
    {
        return in_array($this->type, ['GST', 'CGST', 'SGST', 'IGST']);
    }

    /**
     * Check if this is a VAT tax template.
     */
    public function isVAT(): bool
    {
        return $this->type === 'VAT';
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }
}
