<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->belongsToTeam($transaction->team);
    }

    public function create(User $user): bool
    {
        $team = $user->currentTeam;

        if (! $team) {
            return false;
        }

        return $user->ownsTeam($team) || $user->hasTeamRole($team, 'admin');
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->ownsTeam($transaction->team) || $user->hasTeamRole($transaction->team, 'admin');
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }

    public function restore(User $user, Transaction $transaction): bool
    {
        return false;
    }

    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return false;
    }
}
