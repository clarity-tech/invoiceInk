<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = ['date', 'base', 'quote', 'rate'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'rate' => 'float',
        ];
    }
}
