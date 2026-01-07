<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('can edit transaction with category', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();
    $category = Category::factory()->for($user->currentTeam, 'team')->create(['name' => 'Food']);
    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Grocery',
            'amount' => 1000,
            'note' => null,
        ],
        createdBy: $user,
    );

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->set('category', $category->id)
        ->set('payee', 'Updated Grocery')
        ->call('edit')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect($transaction->category_id)->toBe($category->id);
    expect($transaction->payee)->toBe('Updated Grocery');
});

it('can change transaction category', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();
    $category1 = Category::factory()->for($user->currentTeam, 'team')->create(['name' => 'Food']);
    $category2 = Category::factory()->for($user->currentTeam, 'team')->create(['name' => 'Transport']);
    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Grocery',
            'amount' => 1000,
            'note' => null,
        ],
        createdBy: $user,
        category: $category1,
    );

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->assertSet('category', $category1->id)
        ->set('category', $category2->id)
        ->call('edit')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect($transaction->category_id)->toBe($category2->id);
});

it('can create category when editing transaction', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();
    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Grocery',
            'amount' => 1000,
            'note' => null,
        ],
        createdBy: $user,
    );

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->set('category_search', 'Entertainment')
        ->call('createCategory')
        ->assertSet('category', Category::where('name', 'Entertainment')->first()->id)
        ->assertSet('category_search', '');

    expect(Category::where('name', 'Entertainment')->exists())->toBeTrue();
});

it('can edit expense transaction', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();
    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Coffee',
            'amount' => -550,
            'note' => null,
        ],
        createdBy: $user,
    );

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->assertSet('type', 'expense')
        ->set('payee', 'Updated Coffee')
        ->set('amount', 7.50)
        ->set('type', 'expense')
        ->call('edit')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect($transaction->payee)->toBe('Updated Coffee');
    expect($transaction->amount)->toBe(-750);
});

it('can edit income transaction', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();
    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Salary',
            'amount' => 500000,
            'note' => null,
        ],
        createdBy: $user,
    );

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->assertSet('type', 'income')
        ->set('payee', 'Updated Salary')
        ->set('amount', 5200)
        ->set('type', 'income')
        ->call('edit')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect($transaction->payee)->toBe('Updated Salary');
    expect($transaction->amount)->toBe(520000);
});

it('can change transaction type from expense to income', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();
    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Refund',
            'amount' => -2000,
            'note' => null,
        ],
        createdBy: $user,
    );

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->assertSet('type', 'expense')
        ->set('amount', 20)
        ->set('type', 'income')
        ->call('edit')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect($transaction->amount)->toBe(2000);
});

it('can change transaction type from income to expense', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();
    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Sale',
            'amount' => 10000,
            'note' => null,
        ],
        createdBy: $user,
    );

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->assertSet('type', 'income')
        ->set('amount', 100)
        ->set('type', 'expense')
        ->call('edit')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect($transaction->amount)->toBe(-10000);
});
