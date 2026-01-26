<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'created_by',
        'type',
        'name',
        'start_balance',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('date', 'desc');
    }

    public function addTransaction(array $input, User $createdBy, ?Category $category = null): Transaction
    {
        return $this->transactions()->create([
            'date' => $input['date'],
            'payee' => $input['payee'],
            'amount' => $input['amount'],
            'note' => $input['note'],
            'team_id' => $this->team_id,
            'created_by' => $createdBy->id,
            'category_id' => $category?->id,
        ]);
    }

    public function delete(): bool
    {
        $this->transactions()->delete();

        return parent::delete();
    }

    protected function formattedBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currencySymbol.number_format(($this->start_balance + $this->transactions()->sum('amount')) / 100, 2),
        );
    }

    protected function currentMonthIncome(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->transactions()
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->where('amount', '>', 0)
                ->sum('amount') / 100,
        );
    }

    protected function currentMonthExpenses(): Attribute
    {
        return Attribute::make(
            get: fn () => abs($this->transactions()
                ->whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->where('amount', '<', 0)
                ->sum('amount')) / 100,
        );
    }

    protected function currentMonthIncomeFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currencySymbol.number_format($this->current_month_income, 2),
        );
    }

    protected function currentMonthExpensesFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currencySymbol.number_format($this->current_month_expenses, 2),
        );
    }

    protected function lastMonthIncome(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->transactions()
                ->whereMonth('date', now()->subMonth()->month)
                ->whereYear('date', now()->subMonth()->year)
                ->where('amount', '>', 0)
                ->sum('amount') / 100,
        );
    }

    protected function lastMonthExpenses(): Attribute
    {
        return Attribute::make(
            get: fn () => abs($this->transactions()
                ->whereMonth('date', now()->subMonth()->month)
                ->whereYear('date', now()->subMonth()->year)
                ->where('amount', '<', 0)
                ->sum('amount')) / 100,
        );
    }

    protected function lastMonthIncomeFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currencySymbol.number_format($this->last_month_income, 2),
        );
    }

    protected function lastMonthExpensesFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currencySymbol.number_format($this->last_month_expenses, 2),
        );
    }

    protected function currencySymbol(): Attribute
    {
        return Attribute::make(
            get: fn () => '$',
        );
    }

    protected function displayType(): Attribute
    {
        return Attribute::make(
            get: fn () => str($this->type)->title()->toString(),
        );
    }

    protected function balanceInDollars(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->start_balance + $this->transactions()->sum('amount')) / 100,
        );
    }

    protected function startBalanceInDollars(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->start_balance / 100,
            set: fn (float $value) => (int) round($value * 100),
        );
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtolower($value),
        );
    }

    public function monthExpenses(Carbon $date): int
    {
        return $this->transactions()
            ->where('amount', '<', 0)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->sum('amount');
    }

    public function monthIncome(Carbon $date): int
    {
        return $this->transactions()
            ->where('amount', '>', 0)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->sum('amount');
    }

    public function monthEndBalance(Carbon $date): int
    {
        $monthEnd = $date->copy()->endOfMonth();

        return $this->start_balance +
            $this->transactions()
                ->where('date', '<=', $monthEnd)
                ->sum('amount');
    }

    public function expensesChange(Carbon $currentDate): int
    {
        $previousDate = $currentDate->copy()->subMonth();

        return $this->monthExpenses($currentDate) - $this->monthExpenses($previousDate);
    }

    public function incomeChange(Carbon $currentDate): int
    {
        $previousDate = $currentDate->copy()->subMonth();

        return $this->monthIncome($currentDate) - $this->monthIncome($previousDate);
    }

    public function balanceChange(Carbon $currentDate): int
    {
        $previousDate = $currentDate->copy()->subMonth();

        return $this->monthEndBalance($currentDate) - $this->monthEndBalance($previousDate);
    }

    public function expensesChangePercentage(Carbon $currentDate): ?float
    {
        $previousDate = $currentDate->copy()->subMonth();
        $previousExpenses = $this->monthExpenses($previousDate);

        if ($previousExpenses === 0) {
            return null;
        }

        return (($this->monthExpenses($currentDate) - $previousExpenses) / abs($previousExpenses)) * 100;
    }

    public function incomeChangePercentage(Carbon $currentDate): ?float
    {
        $previousDate = $currentDate->copy()->subMonth();
        $previousIncome = $this->monthIncome($previousDate);

        if ($previousIncome === 0) {
            return null;
        }

        return (($this->monthIncome($currentDate) - $previousIncome) / abs($previousIncome)) * 100;
    }

    public function balanceChangePercentage(Carbon $currentDate): ?float
    {
        $previousDate = $currentDate->copy()->subMonth();
        $previousBalance = $this->monthEndBalance($previousDate);

        if ($previousBalance === 0) {
            return null;
        }

        return (($this->monthEndBalance($currentDate) - $previousBalance) / abs($previousBalance)) * 100;
    }

    public function expensesChangeFormatted(Carbon $currentDate): string
    {
        $change = $this->expensesChange($currentDate) / 100;

        return ($change >= 0 ? '+' : '-').$this->currencySymbol.number_format(abs($change), 2);
    }

    public function incomeChangeFormatted(Carbon $currentDate): string
    {
        $change = $this->incomeChange($currentDate) / 100;

        return ($change >= 0 ? '+' : '-').$this->currencySymbol.number_format(abs($change), 2);
    }

    public function balanceChangeFormatted(Carbon $currentDate): string
    {
        $change = $this->balanceChange($currentDate) / 100;

        return ($change >= 0 ? '+' : '-').$this->currencySymbol.number_format(abs($change), 2);
    }

    public function expensesChangeColor(Carbon $currentDate): string
    {
        return $this->expensesChange($currentDate) <= 0 ? 'lime' : 'pink';
    }

    public function incomeChangeColor(Carbon $currentDate): string
    {
        return $this->incomeChange($currentDate) >= 0 ? 'lime' : 'pink';
    }

    public function balanceChangeColor(Carbon $currentDate): string
    {
        return $this->balanceChange($currentDate) >= 0 ? 'lime' : 'pink';
    }
}
