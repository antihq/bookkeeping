<?php

use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

it('can edit accounts', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam, 'team')->create([
        'name' => 'Old Name',
        'type' => 'checking',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('pages::accounts.edit', ['account' => $account])
        ->set('type', 'savings')
        ->set('name', 'New Name')
        ->set('start_balance', 2000.50)
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('accounts.show', $account));

    $account->refresh();

    expect($account->name)->toBe('New Name');
    expect($account->type)->toBe('savings');
    expect($account->start_balance)->toBe(200050);
});

it('prevents invalid account types', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam, 'team')->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('pages::accounts.edit', ['account' => $account])
        ->set('type', 'invalid')
        ->set('name', 'Test Account')
        ->set('start_balance', 100)
        ->call('update')
        ->assertHasErrors(['type']);
});

it('converts balance from dollars to cents', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam, 'team')->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'start_balance' => 9999,
        'created_by' => $user->id,
    ]);

    $component = Livewire::actingAs($user)->test('pages::accounts.edit', ['account' => $account]);

    expect($component->get('start_balance'))->toBe('99.99');

    $component->set('start_balance', '100.50')
        ->call('update');

    $account->refresh();

    expect($account->start_balance)->toBe(10050);
});

it('prevents non-owners from editing accounts', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();

    $owner->currentTeam->users()->attach($member, ['role' => 'editor']);

    $account = Account::factory()->for($owner->currentTeam, 'team')->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'start_balance' => 100000,
        'created_by' => $owner->id,
    ]);

    Livewire::actingAs($member)->test('pages::accounts.edit', ['account' => $account])
        ->assertForbidden();
});

it('allows negative starting balances', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam, 'team')->create([
        'name' => 'Credit Card',
        'type' => 'credit card',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('pages::accounts.edit', ['account' => $account])
        ->set('start_balance', -1000.50)
        ->call('update')
        ->assertHasNoErrors();

    $account->refresh();

    expect($account->start_balance)->toBe(-100050);
});
