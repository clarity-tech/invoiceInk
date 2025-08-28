<?php

namespace App\Models;

use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'emails',
        'primary_location_id',
        'team_id',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'emails' => \App\Casts\EmailCollectionCast::class,
            'currency' => \App\Currency::class,
        ];
    }

    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }

    public function primaryLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'primary_location_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TeamScope);
    }
}
