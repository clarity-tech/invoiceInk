<?php

namespace App\Actions\Fortify;

use App\Currency;
use App\Models\Organization;
use App\Models\User;
use App\Support\Jetstream;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]), function (User $user) {
                $this->createTeam($user);
            });
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        // Generate meaningful organization name from user input
        $firstName = explode(' ', trim($user->name), 2)[0];
        $organizationName = $firstName ? $firstName."'s Organization" : $user->name."'s Organization";

        $organization = Organization::forceCreate([
            'user_id' => $user->id,
            'name' => $organizationName,
            'personal_team' => true,
            'currency' => Currency::default(),
            'setup_completed_at' => null, // Explicitly set as incomplete for new registrations
        ]);

        // CRITICAL FIX: Set the user's current team to the newly created personal team
        // This ensures proper team assignment from registration
        $user->current_team_id = $organization->id;
        $user->save();

        // Associate the organization with the user through the pivot table
        $user->ownedTeams()->save($organization);
    }
}
