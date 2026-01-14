<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class InvitingTeamMember
{
    use Dispatchable;

    /**
     * @var mixed
     */
    public $team;

    /**
     * @var mixed
     */
    public $email;

    /**
     * @var mixed
     */
    public $role;

    public function __construct($team, $email, $role)
    {
        $this->team = $team;
        $this->email = $email;
        $this->role = $role;
    }
}
