<?php

use App\Models\User;
use Livewire\Livewire;

it('can create accounts', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)->test('pages::accounts.create')
        ->set('type', 'savings')
        ->set('name', 'Test Savings Account')
        ->set('start_balance', 1234.56)
        ->call('create')
        ->assertHasNoErrors();

    $account = $user->currentTeam->accounts()->latest()->first();

    expect($account->name)->toBe('Test Savings Account');
    expect($account->type)->toBe('savings');
    expect($account->start_balance)->toBe(123456);
});

it('stores balance in cents', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)->test('pages::accounts.create')
        ->set('type', 'checking')
        ->set('name', 'Test Account')
        ->set('start_balance', 99.99)
        ->call('create');

    $account = $user->currentTeam->accounts()->latest()->first();

    expect($account->start_balance)->toBe(9999);
});

it('prevents non-owners from creating accounts', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();

    $owner->currentTeam->users()->attach($member, ['role' => 'editor']);

    $member->currentTeam = $owner->currentTeam;

    Livewire::actingAs($member)->test('pages::accounts.create')
        ->assertForbidden();
});

it('allows negative starting balances', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)->test('pages::accounts.create')
        ->set('type', 'credit card')
        ->set('name', 'Credit Card')
        ->set('start_balance', -500.00)
        ->call('create')
        ->assertHasNoErrors();

    $account = $user->currentTeam->accounts()->latest()->first();

    expect($account->start_balance)->toBe(-50000);
});
