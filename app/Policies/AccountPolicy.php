<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Account $account): bool
    {
        return $user->belongsToTeam($account->team);
    }

    public function create(User $user): bool
    {
        $team = $user->currentTeam;

        if (! $team) {
            return false;
        }

        return $user->ownsTeam($team) || $user->hasTeamRole($team, 'admin');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->ownsTeam($account->team) || $user->hasTeamRole($account->team, 'admin');
    }

    public function delete(User $user, Account $account): bool
    {
        return $this->update($user, $account);
    }

    public function restore(User $user, Account $account): bool
    {
        return false;
    }

    public function forceDelete(User $user, Account $account): bool
    {
        return false;
    }
}
