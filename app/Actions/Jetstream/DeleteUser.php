<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Clean up organizations and team associations
            $user->teams()->detach();

            // Note: We don't delete owned organizations since they may contain
            // important business data (invoices, customers, etc.)
            // In a real application, you'd want to handle organization ownership transfer

            // Clean up user data
            $user->deleteProfilePhoto();
            $user->tokens->each->delete();
            $user->delete();
        });
    }
}
