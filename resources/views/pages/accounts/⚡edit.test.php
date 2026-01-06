<?php

use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('accounts can be edited', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Old Name',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account])
        ->set('type', 'savings')
        ->set('name', 'New Name')
        ->set('currency', 'eur')
        ->set('start_balance', 2000.50)
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('accounts.show', $account));

    $account->refresh();

    expect($account->name)->toBe('New Name');
    expect($account->type)->toBe('savings');
    expect($account->currency)->toBe('eur');
    expect($account->start_balance)->toBe(200050);
});

test('account type must be valid', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account])
        ->set('type', 'invalid')
        ->set('name', 'Test Account')
        ->set('currency', 'usd')
        ->set('start_balance', 100)
        ->call('update')
        ->assertHasErrors(['type']);
});

test('balance is converted from dollars to cents', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 9999,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    $component = Livewire::test('pages::accounts.edit', ['account' => $account]);

    expect($component->get('start_balance'))->toBe(99.99);

    $component->set('start_balance', 100.50)
        ->call('update');

    $account->refresh();

    expect($account->start_balance)->toBe(10050);
});

test('non-owners cannot edit accounts', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();

    $owner->currentTeam->users()->attach($member, ['role' => 'editor']);

    $account = Account::factory()->for($owner->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $owner->id,
    ]);

    actingAs($member);

    Livewire::test('pages::accounts.edit', ['account' => $account])
        ->assertForbidden();
});

test('negative starting balances are allowed', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Credit Card',
        'type' => 'credit card',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.edit', ['account' => $account])
        ->set('start_balance', -1000.50)
        ->call('update')
        ->assertHasNoErrors();

    $account->refresh();

    expect($account->start_balance)->toBe(-100050);
});
