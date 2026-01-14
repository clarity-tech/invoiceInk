<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TeamMemberRemoved
{
    use Dispatchable;

    /**
     * @var mixed
     */
    public $team;

    /**
     * @var mixed
     */
    public $user;

    public function __construct($team, $user)
    {
        $this->team = $team;
        $this->user = $user;
    }
}
