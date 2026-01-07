<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('creates a transaction and updates account balance', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'start_balance' => 10000,
    ]);

    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Deposit',
            'amount' => 5000,
            'note' => null,
        ],
        createdBy: $user,
    );

    $account->refresh();

    expect($transaction->payee)->toBe('Deposit');
    expect($transaction->amount)->toBe(5000);
    expect($account->balanceInDollars)->toBe(150.00);
});

it('correctly calculates balance with positive and negative transactions', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'start_balance' => 10000,
    ]);

    $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Deposit',
            'amount' => 5000,
            'note' => null,
        ],
        createdBy: $user,
    );
    $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Withdrawal',
            'amount' => -2500,
            'note' => null,
        ],
        createdBy: $user,
    );

    expect($account->balanceInDollars)->toBe(125.00);
});

it('formats transaction amounts correctly with currency symbol', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
    ]);

    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Test',
            'amount' => 1500,
            'note' => null,
        ],
        createdBy: $user,
    );

    expect($transaction->formatted_amount)->toBe('$15.00');
    expect($transaction->display_amount)->toBe('+$15.00');
});

it('formats negative transaction amounts correctly', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create([
        'currency' => 'usd',
    ]);

    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Expense',
            'amount' => -2500,
            'note' => null,
        ],
        createdBy: $user,
    );

    expect($transaction->formatted_amount)->toBe('$25.00');
    expect($transaction->display_amount)->toBe('-$25.00');
});

it('creates a transaction with a category', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create();
    $category = Category::factory()->forTeam($user->currentTeam)->create(['name' => 'Food']);

    $transaction = $account->addTransaction(
        input: [
            'date' => now()->toDateString(),
            'payee' => 'Grocery shopping',
            'amount' => 1500,
            'note' => null,
        ],
        createdBy: $user,
        category: $category,
    );

    expect($transaction->category_id)->toBe($category->id);
    expect($transaction->category->name)->toBe('Food');
});

it('can create a new category via livewire component', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create();

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->set('category_search', 'Entertainment')
        ->call('createCategory')
        ->assertSet('category_id', Category::where('name', 'Entertainment')->first()->id)
        ->assertSet('category_search', '');

    expect(Category::where('name', 'Entertainment')->exists())->toBeTrue();
});

it('cannot create duplicate categories in same team', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create();
    Category::factory()->forTeam($user->currentTeam)->create(['name' => 'Food']);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->set('category_search', 'Food')
        ->call('createCategory')
        ->assertHasErrors(['category_search' => 'unique']);
});

it('categories are filtered by search term', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->forTeam($user->currentTeam)->create();

    Category::factory()->forTeam($user->currentTeam)->create(['name' => 'Food']);
    Category::factory()->forTeam($user->currentTeam)->create(['name' => 'Transportation']);
    Category::factory()->forTeam($user->currentTeam)->create(['name' => 'Entertainment']);

    actingAs($user);

    $component = Livewire::test('pages::accounts.show', ['account' => $account])
        ->set('category_search', 'foo');

    expect($component->get('categories'))->toHaveCount(1);
    expect($component->get('categories')->first()->name)->toBe('Food');
});
