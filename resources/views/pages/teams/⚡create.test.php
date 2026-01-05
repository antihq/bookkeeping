<?php

use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('teams can be created', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('pages::teams.create')
        ->set('name', 'Test Team')
        ->call('create');

    expect($user->fresh()->ownedTeams)->toHaveCount(2);
    expect($user->fresh()->ownedTeams()->latest('id')->first()->name)->toEqual('Test Team');
});
