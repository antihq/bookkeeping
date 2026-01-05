<?php

use App\Models\Account;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('accounts can be viewed', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->assertSee('Test Account')
        ->assertSee('$1,000.00')
        ->assertSee('Checking');
});

test('balance is formatted correctly', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'savings',
        'currency' => 'eur',
        'start_balance' => 123456,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->assertSee('€1,234.56');
});

test('account type is displayed title-cased', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Credit Card',
        'type' => 'credit card',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->assertSee('Credit Card');
});

test('non-team-members cannot view accounts', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $otherUser = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($otherUser->currentTeam)->create([
        'name' => 'Other Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $otherUser->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->assertForbidden();
});

test('accounts can be deleted', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->call('delete')
        ->assertRedirect(route('accounts.index'));

    expect($user->currentTeam->accounts()->count())->toBe(0);
});

test('non-owners cannot delete accounts', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();

    $owner->currentTeam->users()->attach($member, ['role' => 'editor']);

    $account = Account::factory()->for($owner->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $owner->id,
    ]);

    actingAs($member);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->assertDontSee('Delete account');
});

test('transactions are displayed on account page', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    $account->addTransaction(now()->toDateString(), 'Deposit', 5000, null, $user->id);
    $account->addTransaction(now()->toDateString(), 'Withdrawal', -2500, null, $user->id);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->assertSee('Deposit')
        ->assertSee('Withdrawal');
});

test('transactions are sorted newest first', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    $account->addTransaction('2025-01-01', 'First Transaction', 1000, null, $user->id);
    $account->addTransaction('2025-01-15', 'Second Transaction', 2000, null, $user->id);
    $account->addTransaction('2025-01-10', 'Third Transaction', 1500, null, $user->id);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->assertSeeInOrder(['Second Transaction', 'Third Transaction', 'First Transaction']);
});

test('transaction can be added through livewire component', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->set('transaction_date', now()->toDateString())
        ->set('transaction_title', 'New Deposit')
        ->set('transaction_amount', 100.50)
        ->set('transaction_note', null)
        ->call('addTransaction')
        ->assertHasNoErrors();

    $account->refresh();

    expect($account->transactions)->toHaveCount(1);
    expect($account->transactions->first()->title)->toBe('New Deposit');
    expect($account->transactions->first()->amount)->toBe(10050);
    expect($account->balanceInDollars)->toBe(1100.50);
});

test('transaction validation errors are shown', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $account = Account::factory()->for($user->currentTeam)->create([
        'name' => 'Test Account',
        'type' => 'checking',
        'currency' => 'usd',
        'start_balance' => 100000,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    Livewire::test('pages::accounts.show', ['account' => $account])
        ->set('transaction_date', 'invalid-date')
        ->set('transaction_title', '')
        ->call('addTransaction')
        ->assertHasErrors(['transaction_date', 'transaction_title']);
});
