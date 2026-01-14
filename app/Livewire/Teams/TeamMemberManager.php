<?php

namespace App\Livewire\Teams;

use App\Actions\Jetstream\UpdateTeamMemberRole;
use App\Contracts\Teams\AddsTeamMembers;
use App\Contracts\Teams\InvitesTeamMembers;
use App\Contracts\Teams\RemovesTeamMembers;
use App\Support\Jetstream;
use App\Support\JetstreamFeatures;
use App\Support\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TeamMemberManager extends Component
{
    public $team;

    public $currentlyManagingRole = false;

    public $managingRoleFor;

    public $currentRole;

    public $confirmingLeavingTeam = false;

    public $confirmingTeamMemberRemoval = false;

    public $teamMemberIdBeingRemoved = null;

    public $addTeamMemberForm = [
        'email' => '',
        'role' => null,
    ];

    public function mount($team): void
    {
        $this->team = $team;
    }

    public function addTeamMember(): void
    {
        $this->resetErrorBag();

        if (JetstreamFeatures::sendsTeamInvitations()) {
            app(InvitesTeamMembers::class)->invite(
                $this->user,
                $this->team,
                $this->addTeamMemberForm['email'],
                $this->addTeamMemberForm['role']
            );
        } else {
            app(AddsTeamMembers::class)->add(
                $this->user,
                $this->team,
                $this->addTeamMemberForm['email'],
                $this->addTeamMemberForm['role']
            );
        }

        $this->addTeamMemberForm = [
            'email' => '',
            'role' => null,
        ];

        $this->team = $this->team->fresh();

        $this->dispatch('saved');
    }

    public function cancelTeamInvitation(int $invitationId): void
    {
        if (! empty($invitationId)) {
            $model = Jetstream::teamInvitationModel();

            $model::whereKey($invitationId)
                ->where('team_id', $this->team->id)
                ->delete();
        }

        $this->team = $this->team->fresh();
    }

    public function manageRole(int $userId): void
    {
        $this->currentlyManagingRole = true;
        $this->managingRoleFor = Jetstream::findUserByIdOrFail($userId);
        $this->currentRole = $this->managingRoleFor->teamRole($this->team)->key;
    }

    public function updateRole(UpdateTeamMemberRole $updater): void
    {
        $updater->update(
            $this->user,
            $this->team,
            $this->managingRoleFor->id,
            $this->currentRole
        );

        $this->team = $this->team->fresh();

        $this->stopManagingRole();
    }

    public function stopManagingRole(): void
    {
        $this->currentlyManagingRole = false;
    }

    public function leaveTeam(RemovesTeamMembers $remover)
    {
        $remover->remove(
            $this->user,
            $this->team,
            $this->user
        );

        $this->confirmingLeavingTeam = false;

        $this->team = $this->team->fresh();

        return redirect(config('fortify.home'));
    }

    public function confirmTeamMemberRemoval(int $userId): void
    {
        $this->confirmingTeamMemberRemoval = true;

        $this->teamMemberIdBeingRemoved = $userId;
    }

    public function removeTeamMember(RemovesTeamMembers $remover): void
    {
        $remover->remove(
            $this->user,
            $this->team,
            $user = Jetstream::findUserByIdOrFail($this->teamMemberIdBeingRemoved)
        );

        $this->confirmingTeamMemberRemoval = false;

        $this->teamMemberIdBeingRemoved = null;

        $this->team = $this->team->fresh();
    }

    public function getUserProperty()
    {
        return Auth::user();
    }

    public function getRolesProperty(): array
    {
        return collect(Jetstream::$roles)->transform(function ($role) {
            return with($role->jsonSerialize(), function ($data) {
                return (new Role(
                    $data['key'],
                    $data['name'],
                    $data['permissions']
                ))->description($data['description']);
            });
        })->values()->all();
    }

    public function render()
    {
        return view('teams.team-member-manager');
    }
}
