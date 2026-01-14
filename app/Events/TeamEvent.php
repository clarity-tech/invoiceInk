<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class TeamEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The team instance.
     *
     * @var \App\Models\Organization
     */
    public $team;

    public function __construct($team)
    {
        $this->team = $team;
    }
}
