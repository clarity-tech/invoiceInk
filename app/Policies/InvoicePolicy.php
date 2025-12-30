<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->allTeams()->contains('id', $invoice->organization_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->currentTeam !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->allTeams()->contains('id', $invoice->organization_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->allTeams()->contains('id', $invoice->organization_id);
    }

    /**
     * Determine whether the user can send the document via email.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        return $user->allTeams()->contains('id', $invoice->organization_id);
    }

    /**
     * Determine whether the user can download the PDF.
     */
    public function downloadPdf(User $user, Invoice $invoice): bool
    {
        return $user->allTeams()->contains('id', $invoice->organization_id);
    }
}
