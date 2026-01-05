<?php

use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('displays overall team balance with single currency', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account1 = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
        'start_balance' => 10000,
    ]);
    $account2 = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
        'start_balance' => 5000,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('USD: $150.00');
});

it('displays overall team balance with multiple currencies', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account1 = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
        'start_balance' => 10000,
    ]);
    $account2 = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'eur',
        'start_balance' => 5000,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('USD: $100.00')
        ->assertSee('EUR: €50.00');
});

it('calculates team balance correctly with transactions', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
        'start_balance' => 10000,
    ]);

    $account->addTransaction(now()->toDateString(), 'Deposit', 5000, null, $user->id);

    actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('USD: $150.00');
});

it('calculates team balance correctly across multiple accounts with transactions', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account1 = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
        'start_balance' => 10000,
    ]);
    $account2 = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
        'start_balance' => 5000,
    ]);

    $account1->addTransaction(now()->toDateString(), 'Deposit', 3000, null, $user->id);
    $account2->addTransaction(now()->toDateString(), 'Deposit', 2000, null, $user->id);

    actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('USD: $200.00');
});

it('calculates team balance correctly with negative transactions', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
        'start_balance' => 10000,
    ]);

    $account->addTransaction(now()->toDateString(), 'Expense', -5000, null, $user->id);

    actingAs($user);

    Livewire::test('pages::accounts.index')
        ->assertSee('USD: $50.00');
});
