<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

it('can create expense transaction', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create(['name' => 'Checking Account']);

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '50.00')
        ->set('payee', 'Coffee Shop')
        ->set('date', now()->format('Y-m-d'))
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect(route('transactions.index'));

    $transaction = $user->currentTeam->transactions()->latest()->first();

    expect($transaction->payee)->toBe('Coffee Shop');
    expect($transaction->amount)->toBe(-5000);
    expect($transaction->date)->toBe(now()->format('Y-m-d'));
    expect($transaction->account_id)->toBe($account->id);
    expect($transaction->created_by)->toBe($user->id);
});

it('can create income transaction', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create(['name' => 'Checking Account']);

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'income')
        ->set('amount', '2500.00')
        ->set('payee', 'Salary')
        ->set('date', now()->format('Y-m-d'))
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect(route('transactions.index'));

    $transaction = $user->currentTeam->transactions()->latest()->first();

    expect($transaction->payee)->toBe('Salary');
    expect($transaction->amount)->toBe(250000);
    expect($transaction->date)->toBe(now()->format('Y-m-d'));
    expect($transaction->account_id)->toBe($account->id);
});

it('stores expense amount in cents', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '10.50')
        ->set('payee', 'Test')
        ->set('date', now()->format('Y-m-d'))
        ->call('create');

    $transaction = $user->currentTeam->transactions()->latest()->first();

    expect($transaction->amount)->toBe(-1050);
});

it('stores income amount in cents', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'income')
        ->set('amount', '75.25')
        ->set('payee', 'Test')
        ->set('date', now()->format('Y-m-d'))
        ->call('create');

    $transaction = $user->currentTeam->transactions()->latest()->first();

    expect($transaction->amount)->toBe(7525);
});

it('removes formatting characters from amount', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '$1,234.56')
        ->set('payee', 'Test')
        ->set('date', now()->format('Y-m-d'))
        ->call('create');

    $transaction = $user->currentTeam->transactions()->latest()->first();

    expect($transaction->amount)->toBe(-123456);
});

it('can create transaction with category', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();
    $category = Category::factory()->for($user->currentTeam, 'team')->create(['name' => 'Food']);

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '25.00')
        ->set('payee', 'Grocery')
        ->set('date', now()->format('Y-m-d'))
        ->set('category', $category->id)
        ->call('create')
        ->assertHasNoErrors();

    $transaction = $user->currentTeam->transactions()->latest()->first();

    expect($transaction->category_id)->toBe($category->id);
});

it('can create category inline', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '10.00')
        ->set('payee', 'Test')
        ->set('date', now()->format('Y-m-d'))
        ->set('category_search', 'Entertainment')
        ->call('createCategory')
        ->assertSet('category', Category::where('name', 'Entertainment')->first()->id)
        ->assertSet('category_search', '')
        ->call('create')
        ->assertHasNoErrors();

    expect(Category::where('name', 'Entertainment')->exists())->toBeTrue();
    $transaction = $user->currentTeam->transactions()->latest()->first();
    expect($transaction->category_id)->not->toBeNull();
});

it('can create transaction with note', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '15.00')
        ->set('payee', 'Test')
        ->set('date', now()->format('Y-m-d'))
        ->set('note', 'This is a note')
        ->call('create')
        ->assertHasNoErrors();

    $transaction = $user->currentTeam->transactions()->latest()->first();

    expect($transaction->note)->toBe('This is a note');
});

it('validates account is required', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('type', 'expense')
        ->set('amount', '10.00')
        ->set('payee', 'Test')
        ->set('date', now()->format('Y-m-d'))
        ->call('create')
        ->assertHasErrors(['account']);
});

it('validates date is required', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '10.00')
        ->set('payee', 'Test')
        ->set('date', '')
        ->call('create')
        ->assertHasErrors(['date']);
});

it('validates payee is required', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '10.00')
        ->set('payee', '')
        ->set('date', now()->format('Y-m-d'))
        ->call('create')
        ->assertHasErrors(['payee']);
});

it('validates amount is required', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '')
        ->set('payee', 'Test')
        ->set('date', now()->format('Y-m-d'))
        ->call('create')
        ->assertHasErrors(['amount']);
});

it('validates account exists', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', 999999)
        ->set('type', 'expense')
        ->set('amount', '10.00')
        ->set('payee', 'Test')
        ->set('date', now()->format('Y-m-d'))
        ->call('create')
        ->assertHasErrors(['account']);
});

it('validates category exists', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('account', $account->id)
        ->set('type', 'expense')
        ->set('amount', '10.00')
        ->set('payee', 'Test')
        ->set('date', now()->format('Y-m-d'))
        ->set('category', 999999)
        ->call('create')
        ->assertHasErrors(['category']);
});

it('prevents non-owners from creating transactions', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($owner->currentTeam, 'team')->create();

    $owner->currentTeam->users()->attach($member, ['role' => 'editor']);

    $member->currentTeam = $owner->currentTeam;

    Livewire::actingAs($member)->test('pages::transactions.create')
        ->assertForbidden();
});

it('sets default date on mount', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->assertSet('date', now()->format('Y-m-d'))
        ->assertSet('account', $account->id);
});

it('sets first account on mount', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account1 = Account::factory()->for($user->currentTeam, 'team')->create(['name' => 'Account 1']);
    $account2 = Account::factory()->for($user->currentTeam, 'team')->create(['name' => 'Account 2']);

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->assertSet('account', $account1->id);
});

it('validates category name is unique per team', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam, 'team')->create();
    Category::factory()->for($user->currentTeam, 'team')->create(['name' => 'Food']);

    Livewire::actingAs($user)->test('pages::transactions.create')
        ->set('category_search', 'Food')
        ->call('createCategory')
        ->assertHasErrors(['category_search']);
});
