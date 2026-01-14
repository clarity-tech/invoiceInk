<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Membership extends \Illuminate\Database\Eloquent\Relations\Pivot
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'team_user';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
}
