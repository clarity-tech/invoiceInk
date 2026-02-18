<?php

namespace App\Http\Controllers;

use App\Actions\Jetstream\UpdateTeamMemberRole;
use App\Actions\Jetstream\ValidateTeamDeletion;
use App\Contracts\Teams\AddsTeamMembers;
use App\Contracts\Teams\CreatesTeams;
use App\Contracts\Teams\DeletesTeams;
use App\Contracts\Teams\InvitesTeamMembers;
use App\Contracts\Teams\RemovesTeamMembers;
use App\Contracts\Teams\UpdatesTeamNames;
use App\Models\Organization;
use App\Support\Jetstream;
use App\Support\JetstreamFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function show(Request $request, int $teamId): Response
    {
        $team = Organization::findOrFail($teamId);

        Gate::authorize('view', $team);

        $team->load(['owner', 'users', 'teamInvitations']);

        return Inertia::render('Teams/Show', [
            'team' => $team,
            'availableRoles' => $this->availableRoles(),
            'permissions' => [
                'canAddTeamMembers' => Gate::check('addTeamMember', $team),
                'canDeleteTeam' => Gate::check('delete', $team),
                'canRemoveTeamMembers' => Gate::check('removeTeamMember', $team),
                'canUpdateTeam' => Gate::check('update', $team),
                'canUpdateTeamMembers' => Gate::check('updateTeamMember', $team),
            ],
            'hasRoles' => Jetstream::hasRoles(),
            'sendsTeamInvitations' => JetstreamFeatures::sendsTeamInvitations(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', new Organization);

        return Inertia::render('Teams/Create');
    }

    public function store(Request $request, CreatesTeams $creator): RedirectResponse
    {
        $team = $creator->create($request->user(), $request->all());

        return redirect()->route('teams.show', $team->id)->banner('Team created successfully.');
    }

    public function update(Request $request, int $teamId): RedirectResponse
    {
        $team = Organization::findOrFail($teamId);

        Gate::authorize('update', $team);

        app(UpdatesTeamNames::class)->update($request->user(), $team, $request->all());

        return back()->banner('Team name updated.');
    }

    public function destroy(Request $request, int $teamId): RedirectResponse
    {
        $team = Organization::findOrFail($teamId);

        app(ValidateTeamDeletion::class)->validate($request->user(), $team);
        app(DeletesTeams::class)->delete($team);

        return redirect(config('fortify.home'))->banner('Team deleted.');
    }

    public function addMember(Request $request, int $teamId): RedirectResponse
    {
        $team = Organization::findOrFail($teamId);

        Gate::authorize('addTeamMember', $team);

        if (JetstreamFeatures::sendsTeamInvitations()) {
            app(InvitesTeamMembers::class)->invite(
                $request->user(),
                $team,
                $request->input('email'),
                $request->input('role')
            );
        } else {
            app(AddsTeamMembers::class)->add(
                $request->user(),
                $team,
                $request->input('email'),
                $request->input('role')
            );
        }

        return back()->banner('Team member added.');
    }

    public function updateMemberRole(Request $request, int $teamId, int $userId): RedirectResponse
    {
        $team = Organization::findOrFail($teamId);

        Gate::authorize('updateTeamMember', $team);

        app(UpdateTeamMemberRole::class)->update(
            $request->user(),
            $team,
            $userId,
            $request->input('role')
        );

        return back()->banner('Role updated.');
    }

    public function removeMember(Request $request, int $teamId, int $userId): RedirectResponse
    {
        $team = Organization::findOrFail($teamId);

        app(RemovesTeamMembers::class)->remove(
            $request->user(),
            $team,
            Jetstream::findUserByIdOrFail($userId)
        );

        if ($request->user()->id === $userId) {
            return redirect(config('fortify.home'));
        }

        return back()->banner('Team member removed.');
    }

    public function cancelInvitation(Request $request, int $teamId, int $invitationId): RedirectResponse
    {
        $team = Organization::findOrFail($teamId);

        Gate::authorize('removeTeamMember', $team);

        $model = Jetstream::teamInvitationModel();
        $model::whereKey($invitationId)->where('team_id', $team->id)->delete();

        return back()->banner('Invitation cancelled.');
    }

    /**
     * @return array<int, array{key: string, name: string, description: string, permissions: array<int, string>}>
     */
    protected function availableRoles(): array
    {
        return collect(Jetstream::$roles)->map(function ($role) {
            $data = $role->jsonSerialize();

            return [
                'key' => $data['key'],
                'name' => $data['name'],
                'description' => $data['description'],
                'permissions' => $data['permissions'],
            ];
        })->values()->all();
    }
}
