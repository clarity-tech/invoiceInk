<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvitation extends \Illuminate\Database\Eloquent\Model
{
    /** @use HasFactory<\Database\Factories\TeamInvitationFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'team_invitations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'role',
    ];

    /**
     * Get the team that the invitation belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'team_id');
    }
}
