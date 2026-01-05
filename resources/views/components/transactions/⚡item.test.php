<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('transaction can be edited with category', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create();
    $category = Category::factory()->forTeam($user->currentTeam)->create(['name' => 'Food']);
    $transaction = $account->addTransaction(now()->toDateString(), 'Grocery', 1000, null, $user->id);

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->call('openEditModal')
        ->set('category_id', $category->id)
        ->set('title', 'Updated Grocery')
        ->call('editTransaction')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect($transaction->category_id)->toBe($category->id);
    expect($transaction->title)->toBe('Updated Grocery');
});

test('transaction category can be changed', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create();
    $category1 = Category::factory()->forTeam($user->currentTeam)->create(['name' => 'Food']);
    $category2 = Category::factory()->forTeam($user->currentTeam)->create(['name' => 'Transport']);
    $transaction = $account->addTransaction(
        now()->toDateString(),
        'Grocery',
        1000,
        null,
        $user->id,
        $category1->id
    );

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->call('openEditModal')
        ->assertSet('category_id', $category1->id)
        ->set('category_id', $category2->id)
        ->call('editTransaction')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect($transaction->category_id)->toBe($category2->id);
});

test('category can be created when editing transaction', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create();
    $transaction = $account->addTransaction(now()->toDateString(), 'Grocery', 1000, null, $user->id);

    actingAs($user);

    Livewire::test('transactions.item', ['transaction' => $transaction])
        ->call('openEditModal')
        ->set('category_search', 'Entertainment')
        ->call('createCategory')
        ->assertSet('category_id', Category::where('name', 'Entertainment')->first()->id)
        ->assertSet('category_search', '');

    expect(Category::where('name', 'Entertainment')->exists())->toBeTrue();
});
