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
     * Create an unconfigured organization for the user. Forced through the setup
     * wizard on first authenticated request because setup_completed_at is null.
     */
    protected function createTeam(User $user): void
    {
        $firstName = explode(' ', trim($user->name), 2)[0];
        $organizationName = $firstName ? $firstName."'s Organization" : $user->name."'s Organization";

        $organization = Organization::forceCreate([
            'user_id' => $user->id,
            'name' => $organizationName,
            'currency' => Currency::default(),
            'setup_completed_at' => null,
        ]);

        $user->current_team_id = $organization->id;
        $user->save();

        $user->ownedTeams()->save($organization);
    }
}
