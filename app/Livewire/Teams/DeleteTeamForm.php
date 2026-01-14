<?php

namespace App\Livewire\Teams;

use App\Actions\Jetstream\ValidateTeamDeletion;
use App\Contracts\Teams\DeletesTeams;
use App\Traits\RedirectsActions;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteTeamForm extends Component
{
    use RedirectsActions;

    public $team;

    public $confirmingTeamDeletion = false;

    public function mount($team): void
    {
        $this->team = $team;
    }

    public function deleteTeam(ValidateTeamDeletion $validator, DeletesTeams $deleter)
    {
        $validator->validate(Auth::user(), $this->team);

        $deleter->delete($this->team);

        $this->team = null;

        return $this->redirectPath($deleter);
    }

    public function render()
    {
        return view('teams.delete-team-form');
    }
}
