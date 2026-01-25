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

    $this->team->transactions()->createMany([
        [
            'amount' => -5000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Store 1',
            'category_id' => $category->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -3000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Store 2',
            'category_id' => $category->id,
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 10000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Employer',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->monthExpenses(now()))->toBe(-8000);
});

it('calculates monthly income correctly', function () {
    $this->team->transactions()->createMany([
        [
            'amount' => -5000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Store 1',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 10000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Employer',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 5000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Freelance',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->monthIncome(now()))->toBe(15000);
});

it('calculates month end balance correctly', function () {
    $this->team->transactions()->createMany([
        [
            'amount' => -5000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Store 1',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 10000,
            'date' => now()->format('Y-m-d'),
            'payee' => 'Employer',
            'created_by' => $this->user->id,
        ],
    ]);

    $expectedBalance = 100000 + 10000 - 5000;
    expect($this->team->monthEndBalance(now()))->toBe($expectedBalance);
});

it('calculates expenses change between months', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => -10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -15000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->expensesChange($thisMonth))->toBe(-5000);
});

it('calculates income change between months', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 12000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->incomeChange($thisMonth))->toBe(2000);
});

it('calculates balance change between months', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    $previousBalance = 100000 + 5000;
    $currentBalance = 100000 + 5000 + 3000;
    $expectedChange = $currentBalance - $previousBalance;

    expect($this->team->balanceChange($thisMonth))->toBe($expectedChange);
});

it('calculates expenses change percentage correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => -10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -15000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->expensesChangePercentage($thisMonth))->toBe(-50.0);
});

it('calculates income change percentage correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 12000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->incomeChangePercentage($thisMonth))->toBe(20.0);
});

it('calculates balance change percentage correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    $previousBalance = 100000 + 5000;
    $currentBalance = 100000 + 5000 + 3000;
    $expectedPercentage = (($currentBalance - $previousBalance) / abs($previousBalance)) * 100;

    expect($this->team->balanceChangePercentage($thisMonth))->toBe($expectedPercentage);
});

it('returns null for expenses change percentage when previous month is zero', function () {
    $thisMonth = now();

    $this->team->transactions()->create([
        'amount' => -5000,
        'date' => $thisMonth->format('Y-m-d'),
        'payee' => 'Store This Month',
        'created_by' => $this->user->id,
    ]);

    expect($this->team->expensesChangePercentage($thisMonth))->toBeNull();
});

it('returns null for income change percentage when previous month is zero', function () {
    $thisMonth = now();

    $this->team->transactions()->create([
        'amount' => 5000,
        'date' => $thisMonth->format('Y-m-d'),
        'payee' => 'Employer This Month',
        'created_by' => $this->user->id,
    ]);

    expect($this->team->incomeChangePercentage($thisMonth))->toBeNull();
});

it('returns null for balance change percentage when previous month is zero', function () {
    $this->team->accounts()->delete();

    Account::factory()->for($this->team)->create(['start_balance' => 0]);

    $thisMonth = now();

    $this->team->transactions()->create([
        'amount' => 5000,
        'date' => $thisMonth->format('Y-m-d'),
        'payee' => 'Employer This Month',
        'created_by' => $this->user->id,
    ]);

    expect($this->team->balanceChangePercentage($thisMonth))->toBeNull();
});

it('formats expenses change correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => -10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -15000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->expensesChangeFormatted($thisMonth))->toBe('-$50.00');
});

it('formats income change correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 12000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->incomeChangeFormatted($thisMonth))->toBe('+$20.00');
});

it('formats balance change correctly', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    $expectedBalance = 100000 + 5000 + 3000;
    $previousBalance = 100000 + 5000;
    $change = ($expectedBalance - $previousBalance) / 100;

    $expectedString = ($change >= 0 ? '+' : '-').'$'.number_format(abs($change), 2);

    expect($this->team->balanceChangeFormatted($thisMonth))->toBe($expectedString);
});

it('returns correct color for expenses change', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => -10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -15000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->expensesChangeColor($thisMonth))->toBe('lime');
});

it('returns correct color for income change', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 10000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 12000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->incomeChangeColor($thisMonth))->toBe('lime');
});

it('returns correct color for balance change', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->balanceChangeColor($thisMonth))->toBe('lime');
});

it('returns pink color when expenses decrease', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => -15000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Store Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -10000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->expensesChangeColor($thisMonth))->toBe('pink');
});

it('returns pink color when income decreases', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 12000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => 10000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Employer This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->incomeChangeColor($thisMonth))->toBe('pink');
});

it('returns pink color when balance decreases', function () {
    $lastMonth = now()->subMonth();
    $thisMonth = now();

    $this->team->transactions()->createMany([
        [
            'amount' => 5000,
            'date' => $lastMonth->format('Y-m-d'),
            'payee' => 'Employer Last Month',
            'created_by' => $this->user->id,
        ],
        [
            'amount' => -3000,
            'date' => $thisMonth->format('Y-m-d'),
            'payee' => 'Store This Month',
            'created_by' => $this->user->id,
        ],
    ]);

    expect($this->team->balanceChangeColor($thisMonth))->toBe('pink');
});
