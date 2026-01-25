<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function latestTransactionForUser(User $user): ?Transaction
    {
        return $this->transactions()
            ->where('created_by', $user->id)
            ->latest('created_at')
            ->first();
    }

    protected function totalBalanceInDollars(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->accounts()->sum('start_balance') + $this->transactions()->sum('amount')) / 100,
        );
    }

    protected function formattedTotalBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => '$'.number_format($this->total_balance_in_dollars, 2),
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

        return $this->accounts()->sum('start_balance') +
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }
}
