<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AddingTeam
{
    use Dispatchable;

    /**
     * The team owner.
     *
     * @var mixed
     */
    public $owner;

    public function __construct($owner)
    {
        $this->owner = $owner;
    }
}
