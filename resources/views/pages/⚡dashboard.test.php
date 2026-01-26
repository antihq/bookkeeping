<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('can add expense transaction', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->set('date', now()->toDateString())
        ->set('payee', 'Coffee Shop')
        ->set('amount', 5.50)
        ->set('type', 'expense')
        ->call('addTransaction')
        ->assertHasNoErrors();

    $transaction = $user->currentTeam->transactions()->first();

    expect($transaction->payee)->toBe('Coffee Shop');
    expect($transaction->amount)->toBe(-550);
    expect($transaction->date)->toBe(now()->toDateString());
    expect($transaction->created_by)->toBe($user->id);
});

it('can add income transaction', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->set('date', now()->toDateString())
        ->set('payee', 'Salary')
        ->set('amount', 5000)
        ->set('type', 'income')
        ->call('addTransaction')
        ->assertHasNoErrors();

    $transaction = $user->currentTeam->transactions()->first();

    expect($transaction->payee)->toBe('Salary');
    expect($transaction->amount)->toBe(500000);
});

it('can add transaction with all fields populated', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $category = Category::factory()->for($user->currentTeam, 'team')->create(['name' => 'Entertainment']);
    $account = Account::factory()->for($user->currentTeam, 'team')->create(['name' => 'Credit Card']);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->set('date', now()->toDateString())
        ->set('payee', 'Netflix')
        ->set('amount', 15.99)
        ->set('note', 'Monthly subscription')
        ->set('type', 'expense')
        ->set('category', $category->id)
        ->set('account', $account->id)
        ->call('addTransaction')
        ->assertHasNoErrors();

    $transaction = $user->currentTeam->transactions()->first();

    expect($transaction->payee)->toBe('Netflix');
    expect($transaction->amount)->toBe(-1599);
    expect($transaction->note)->toBe('Monthly subscription');
    expect($transaction->category_id)->toBe($category->id);
    expect($transaction->account_id)->toBe($account->id);
});

it('amount formatting removes dollar sign', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->set('date', now()->toDateString())
        ->set('payee', 'Store')
        ->set('amount', '$10.50')
        ->set('type', 'expense')
        ->call('addTransaction')
        ->assertHasNoErrors();

    $transaction = $user->currentTeam->transactions()->first();

    expect($transaction->amount)->toBe(-1050);
});

it('amount formatting removes commas', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->set('date', now()->toDateString())
        ->set('payee', 'Purchase')
        ->set('amount', '1,000.00')
        ->set('type', 'income')
        ->call('addTransaction')
        ->assertHasNoErrors();

    $transaction = $user->currentTeam->transactions()->first();

    expect($transaction->amount)->toBe(100000);
});

it('expense amounts are stored as negative in cents', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->set('date', now()->toDateString())
        ->set('payee', 'Expense')
        ->set('amount', 25.99)
        ->set('type', 'expense')
        ->call('addTransaction')
        ->assertHasNoErrors();

    $transaction = $user->currentTeam->transactions()->first();

    expect($transaction->amount)->toBe(-2599);
});

it('can add transaction with decimal amounts', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->set('date', now()->toDateString())
        ->set('payee', 'Item')
        ->set('amount', 0.99)
        ->set('type', 'expense')
        ->call('addTransaction')
        ->assertHasNoErrors();

    $transaction = $user->currentTeam->transactions()->first();

    expect($transaction->amount)->toBe(-99);
});

it('non-owner non-admin users cannot add transactions', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();

    $owner->currentTeam->users()->attach($member, ['role' => 'editor']);

    $member->currentTeam = $owner->currentTeam;

    Livewire::actingAs($member)
        ->test('pages::dashboard')
        ->set('date', now()->toDateString())
        ->set('payee', 'Store')
        ->set('amount', 10)
        ->call('addTransaction')
        ->assertForbidden();
});

it('redirects guests to the login page', function () {
    get('/dashboard')->assertRedirect('/login');
});
