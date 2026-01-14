<?php

namespace App\Livewire\Teams;

use App\Contracts\Teams\CreatesTeams;
use App\Traits\RedirectsActions;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateTeamForm extends Component
{
    use RedirectsActions;

    public $state = [];

    public function createTeam(CreatesTeams $creator)
    {
        $this->resetErrorBag();

        $creator->create(Auth::user(), $this->state);

        return $this->redirectPath($creator);
    }

    public function getUserProperty()
    {
        return Auth::user();
    }

    public function render()
    {
        return view('teams.create-team-form');
    }
}
