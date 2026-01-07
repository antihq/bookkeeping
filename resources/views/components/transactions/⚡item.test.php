<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('transaction can be edited with category', function () {
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

test('transaction category can be changed', function () {
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

test('category can be created when editing transaction', function () {
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
