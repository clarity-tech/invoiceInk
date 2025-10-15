<?php

namespace App\Models;

use App\Enums\ResetFrequency;
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
        'format_pattern' => '{PREFIX}-{YEAR}-{MONTH}-{SEQUENCE:4}',
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

        if (!$this->last_reset_at) {
            return true;
        }

        return match ($this->reset_frequency) {
            ResetFrequency::YEARLY => now()->year > $this->last_reset_at->year,
            ResetFrequency::MONTHLY => now()->startOfMonth()->gt($this->last_reset_at->startOfMonth()),
            default => false,
        };
    }

    public function getNextSequenceNumber(): int
    {
        if ($this->shouldReset()) {
            $this->resetSequence();
        }

        return $this->current_number + 1;
    }

    public function incrementAndSave(): void
    {
        $this->increment('current_number');
        
        if ($this->shouldReset()) {
            $this->update(['last_reset_at' => now()]);
        }
    }

    private function resetSequence(): void
    {
        $this->update([
            'current_number' => 0,
            'last_reset_at' => now(),
        ]);
    }
}
