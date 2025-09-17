<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'emails',
        'primary_location_id',
        'organization_id',
    ];

    protected function casts(): array
    {
        return [
            'emails' => \App\Casts\EmailCollectionCast::class,
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope);
    }
}
