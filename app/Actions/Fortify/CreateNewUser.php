<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        Validator::make($input, [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
        ])->validate();

        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => null,
                'email' => $input['email'],
                'password' => null,
            ]), function (User $user) {
                $this->createTeam($user);
                $user->notify(new WelcomeNotification);
            });
        });
    }

    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => 'Personal',
            'personal_team' => true,
        ]));
    }
}
