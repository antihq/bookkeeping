<?php

use App\Models\Account;
use App\Models\User;

it('creates a transaction and updates account balance', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'start_balance' => 10000,
    ]);

    $transaction = $account->addTransaction(now()->toDateString(), 'Deposit', 5000, null, $user->id);

    $account->refresh();

    expect($transaction->title)->toBe('Deposit');
    expect($transaction->amount)->toBe(5000);
    expect($account->balanceInDollars)->toBe(150.00);
});

it('correctly calculates balance with positive and negative transactions', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'start_balance' => 10000,
    ]);

    $account->addTransaction(now()->toDateString(), 'Deposit', 5000, null, $user->id);
    $account->addTransaction(now()->toDateString(), 'Withdrawal', -2500, null, $user->id);

    expect($account->balanceInDollars)->toBe(125.00);
});

it('formats transaction amounts correctly with currency symbol', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
    ]);

    $transaction = $account->addTransaction(now()->toDateString(), 'Test', 1500, null, $user->id);

    expect($transaction->formatted_amount)->toBe('$15.00');
    expect($transaction->display_amount)->toBe('+$15.00');
});

it('formats negative transaction amounts correctly', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
    ]);

    $transaction = $account->addTransaction(now()->toDateString(), 'Expense', -2500, null, $user->id);

    expect($transaction->formatted_amount)->toBe('$25.00');
    expect($transaction->display_amount)->toBe('-$25.00');
});
