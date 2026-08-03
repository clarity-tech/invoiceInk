<?php

namespace App\Models;

use App\Enums\ResetFrequency;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceNumberingSeries extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'location_id',
        'name',
        'prefix',
        'format_pattern',
        'current_number',
        'reset_frequency',
        'is_active',
        'is_default',
        'last_reset_at',
    ];

    protected $attributes = [
        'prefix' => 'INV',
        'format_pattern' => '{PREFIX}-{YEAR:2}{SEQUENCE:3}',
        'current_number' => 0,
        'reset_frequency' => ResetFrequency::YEARLY->value,
        'is_active' => true,
        'is_default' => false,
    ];

    protected function casts(): array
    {
        return [
            'current_number' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'last_reset_at' => 'datetime',
            'reset_frequency' => ResetFrequency::class,
        ];
    }

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    // Helper methods
    public function shouldReset(): bool
    {
        if ($this->reset_frequency === ResetFrequency::NEVER) {
            return false;
        }

        if (! $this->last_reset_at) {
            return true;
        }

        return match ($this->reset_frequency) {
            ResetFrequency::YEARLY => now()->year > $this->last_reset_at->year,
            ResetFrequency::MONTHLY => now()->startOfMonth()->gt($this->last_reset_at->startOfMonth()),
            ResetFrequency::FINANCIAL_YEAR => $this->organization?->hasFinancialYearChanged($this->last_reset_at) ?? false,
            default => false,
        };
    }

    /**
     * Compute the next sequence number without mutating any state.
     * If a reset is due, the next sequence is 1 (off a virtual zero baseline).
     */
    public function getNextSequenceNumber(): int
    {
        if ($this->shouldReset()) {
            return 1;
        }

        return $this->current_number + 1;
    }

    /**
     * Persist the series counter to the given sequence number atomically.
     * Also handles `last_reset_at` bookkeeping when a reset is due.
     */
    public function commitSequenceNumber(int $sequenceNumber): void
    {
        $attributes = ['current_number' => $sequenceNumber];

        if ($this->shouldReset() || $this->last_reset_at === null) {
            $attributes['last_reset_at'] = now();
        }

        $this->update($attributes);
    }
}
