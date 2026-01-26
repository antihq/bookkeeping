<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->account = Account::factory()->for($this->team)->create([
        'start_balance' => 100000,
    ]);
});

it('calculates monthly expenses correctly', function () {
    $category = Category::factory()->for($this->team)->create();

    $this->account->transactions()->createMany([
        [
            'amount' => -5000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Store 1',
            'category_id' => $category->id,
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -3000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Store 2',
            'category_id' => $category->id,
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 10000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Employer',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->monthExpenses(now()))->toBe(-8000);
});

it('calculates monthly income correctly', function () {
    $this->account->transactions()->createMany([
        [
            'amount' => -5000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Store 1',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 10000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Employer',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 5000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Freelance',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->monthIncome(now()))->toBe(15000);
});

it('calculates month end balance correctly', function () {
    $this->account->transactions()->createMany([
        [
            'amount' => -5000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Store 1',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 10000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Employer',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    $expectedBalance = 100000 + 10000 - 5000;
    expect($this->account->monthEndBalance(now()))->toBe($expectedBalance);
});

it('calculates expenses change between months', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => -10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -15000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->expensesChange($thisMonth))->toBe(-5000);
});

it('calculates income change between months', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 12000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->incomeChange($thisMonth))->toBe(2000);
});

it('calculates balance change between months', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    $previousBalance = 100000 + 5000;
    $currentBalance = 100000 + 5000 + 3000;
    $expectedChange = $currentBalance - $previousBalance;

    expect($this->account->balanceChange($thisMonth))->toBe($expectedChange);
});

it('calculates expenses change percentage correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => -10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -15000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->expensesChangePercentage($thisMonth))->toBe(-50.0);
});

it('calculates income change percentage correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 12000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->incomeChangePercentage($thisMonth))->toBe(20.0);
});

it('calculates balance change percentage correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    $previousBalance = 100000 + 5000;
    $currentBalance = 100000 + 5000 + 3000;
    $expectedPercentage = (($currentBalance - $previousBalance) / abs($previousBalance)) * 100;

    expect($this->account->balanceChangePercentage($thisMonth))->toBe($expectedPercentage);
});

it('returns null for expenses change percentage when previous month is zero', function () {
    $thisMonth = now();

    $this->account->transactions()->create([
        'amount' => -5000,
        'date' => $thisMonth->format('Y-m-d'),
        'payee' => 'Store This Month',
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
    ]);

    expect($this->account->expensesChangePercentage($thisMonth))->toBeNull();
});

it('returns null for income change percentage when previous month is zero', function () {
    $thisMonth = now();

    $this->account->transactions()->create([
        'amount' => 5000,
        'date' => $thisMonth->format('Y-m-d'),
        'payee' => 'Employer This Month',
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
    ]);

    expect($this->account->incomeChangePercentage($thisMonth))->toBeNull();
});

it('returns null for balance change percentage when previous month is zero', function () {
    $this->account->update(['start_balance' => 0]);

    $thisMonth = now();

    $this->account->transactions()->create([
        'amount' => 5000,
        'date' => $thisMonth->format('Y-m-d'),
        'payee' => 'Employer This Month',
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
    ]);

    expect($this->account->balanceChangePercentage($thisMonth))->toBeNull();
});

it('formats expenses change correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => -10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -15000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->expensesChangeFormatted($thisMonth))->toBe('-$50.00');
});

it('formats income change correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 12000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->incomeChangeFormatted($thisMonth))->toBe('+$20.00');
});

it('formats balance change correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    $expectedBalance = 100000 + 5000 + 3000;
    $previousBalance = 100000 + 5000;
    $change = ($expectedBalance - $previousBalance) / 100;

    $expectedString = ($change >= 0 ? '+' : '-').'$'.number_format(abs($change), 2);

    expect($this->account->balanceChangeFormatted($thisMonth))->toBe($expectedString);
});

it('returns correct color for expenses change', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => -10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -15000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->expensesChangeColor($thisMonth))->toBe('lime');
});

it('returns correct color for income change', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 12000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->incomeChangeColor($thisMonth))->toBe('lime');
});

it('returns correct color for balance change', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->balanceChangeColor($thisMonth))->toBe('lime');
});

it('returns pink color when expenses decrease', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => -15000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -10000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->expensesChangeColor($thisMonth))->toBe('pink');
});

it('returns pink color when income decreases', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 12000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 10000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->incomeChangeColor($thisMonth))->toBe('pink');
});

it('returns pink color when balance decreases', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->account->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->account->balanceChangeColor($thisMonth))->toBe('pink');
});
