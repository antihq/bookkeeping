<?php

use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('accounts can be created', function () {
    $user = User::factory()->withPersonalTeam()->create();

    actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('type', 'savings')
        ->set('name', 'Test Savings Account')
        ->set('currency', 'usd')
        ->set('start_balance', 1234.56)
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect(route('accounts.index'));

    $account = $user->currentTeam->accounts()->latest()->first();

    expect($account->name)->toBe('Test Savings Account');
    expect($account->type)->toBe('savings');
    expect($account->currency)->toBe('usd');
    expect($account->start_balance)->toBe(123456);
});

// test('account creation validates required fields', function () {
//     $user = User::factory()->withPersonalTeam()->create();
//
//     actingAs($user);
//
//     Livewire::test('pages::accounts.create')
//         ->set('type', 'invalid')
//         ->set('name', '')
//         ->set('currency', '')
//         ->set('start_balance', '')
//         ->call('create')
//         ->assertHasErrors(['type', 'name', 'currency', 'start_balance']);
// });

test('account type must be valid', function () {
    $user = User::factory()->withPersonalTeam()->create();

    actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('type', 'invalid')
        ->set('name', 'Test Account')
        ->set('currency', 'usd')
        ->set('start_balance', 100)
        ->call('create')
        ->assertHasErrors(['type']);
});

test('balance is stored in cents', function () {
    $user = User::factory()->withPersonalTeam()->create();

    actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('type', 'checking')
        ->set('name', 'Test Account')
        ->set('currency', 'usd')
        ->set('start_balance', 99.99)
        ->call('create');

    $account = $user->currentTeam->accounts()->latest()->first();

    expect($account->start_balance)->toBe(9999);
});

test('currency defaults to usd', function () {
    $user = User::factory()->withPersonalTeam()->create();

    actingAs($user);

    $component = Livewire::test('pages::accounts.create');

    expect($component->get('currency'))->toBe('usd');
});

test('non-owners cannot create accounts', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();

    $owner->currentTeam->users()->attach($member, ['role' => 'editor']);

    actingAs($member);

    $member->currentTeam = $owner->currentTeam;

    Livewire::test('pages::accounts.create')
        ->assertForbidden();
});

test('negative starting balances are allowed', function () {
    $user = User::factory()->withPersonalTeam()->create();

    actingAs($user);

    Livewire::test('pages::accounts.create')
        ->set('type', 'credit card')
        ->set('name', 'Credit Card')
        ->set('currency', 'usd')
        ->set('start_balance', -500.00)
        ->call('create')
        ->assertHasNoErrors();

    $account = $user->currentTeam->accounts()->latest()->first();

    expect($account->start_balance)->toBe(-50000);
});
