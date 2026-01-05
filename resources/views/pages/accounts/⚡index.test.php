<?php

use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('accounts can be listed', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Account::factory()->for($user->currentTeam)->create([
        'name' => 'Main Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('Main Account')
        ->assertSee('$1,000.00');
});

test('non-team-members cannot see accounts', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $otherUser = User::factory()->withPersonalTeam()->create();

    Account::factory()->for($otherUser->currentTeam)->create([
        'name' => 'Other Account',
        'type' => 'savings',
        'currency' => 'usd',
        'start_balance' => 50000,
        'created_by' => $otherUser->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertDontSee('Other Account');
});

test('account type is displayed title-cased', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Account::factory()->for($user->currentTeam)->create([
        'name' => 'Credit Card Account',
        'type' => 'credit card',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('Credit Card');
});
