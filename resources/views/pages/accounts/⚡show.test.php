<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

it('allows deleting accounts', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam, 'team')->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('pages::accounts.show', ['account' => $account])
        ->call('delete')
        ->assertRedirect(route('accounts.index'));

    expect($user->currentTeam->accounts()->count())->toBe(0);
});

it('prevents non-owners from deleting accounts', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();

    $owner->currentTeam->users()->attach($member, ['role' => 'editor']);

    $account = Account::factory()->for($owner->currentTeam, 'team')->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $owner->id,
    ]);

    Livewire::actingAs($member)->test('pages::accounts.show', ['account' => $account])
        ->assertDontSee('Delete account');
});

it('can add transactions', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('pages::accounts.show', ['account' => $account])
        ->set('date', now()->toDateString())
        ->set('payee', 'New Deposit')
        ->set('amount', 100.50)
        ->set('note', null)
        ->call('addTransaction')
        ->assertHasNoErrors();

    $account->refresh();

    expect($account->transactions)->toHaveCount(1);
    expect($account->transactions->first()->payee)->toBe('New Deposit');
    expect($account->transactions->first()->amount)->toBe(10050);
    expect($account->balanceInDollars)->toBe(1100.50);
});

it('can create new categories', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create();

    Livewire::actingAs($user)->test('pages::accounts.show', ['account' => $account])
        ->set('category_search', 'Entertainment')
        ->call('createCategory')
        ->assertSet('category', ($category = Category::first())->id)
        ->assertSet('category_search', '');

    expect($category->name)->toBe('Entertainment');
});

it('prevents creating duplicate categories in the same team', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create();
    Category::factory()->for($user->currentTeam, 'team')->create(['name' => 'Food']);

    Livewire::actingAs($user)->test('pages::accounts.show', ['account' => $account])
        ->set('category_search', 'Food')
        ->call('createCategory')
        ->assertHasErrors(['category_search' => 'unique']);
});
