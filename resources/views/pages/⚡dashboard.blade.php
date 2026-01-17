<?php

use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Transactions')] class extends Component {
    #[Computed]
    public function transactions()
    {
        return $this->team
            ->transactions()
            ->with('category')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam;
    }

    #[Computed]
    public function totalBalance(): float
    {
        return $this->team->total_balance_in_dollars;
    }

    #[Computed]
    public function monthlyExpenses(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '<', 0)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function monthlyIncome(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '>', 0)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function lastMonthExpenses(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '<', 0)
            ->whereYear('date', now()->subMonth()->year)
            ->whereMonth('date', now()->subMonth()->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function lastMonthIncome(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '>', 0)
            ->whereYear('date', now()->subMonth()->year)
            ->whereMonth('date', now()->subMonth()->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function lastMonthBalance(): float
    {
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        return ($this->team->accounts()->sum('start_balance') +
                $this->team->transactions()
                    ->where('date', '<=', $lastMonthEnd)
                    ->sum('amount')) / 100;
    }

    #[Computed]
    public function expensesChange(): float
    {
        return $this->monthlyExpenses - $this->lastMonthExpenses;
    }

    #[Computed]
    public function incomeChange(): float
    {
        return $this->monthlyIncome - $this->lastMonthIncome;
    }

    #[Computed]
    public function balanceChange(): float
    {
        return $this->totalBalance - $this->lastMonthBalance;
    }

    #[Computed]
    public function expensesChangePercentage(): ?float
    {
        if ($this->lastMonthExpenses === 0.0) {
            return null;
        }

        return (($this->monthlyExpenses - $this->lastMonthExpenses) / abs($this->lastMonthExpenses)) * 100;
    }

    #[Computed]
    public function incomeChangePercentage(): ?float
    {
        if ($this->lastMonthIncome === 0.0) {
            return null;
        }

        return (($this->monthlyIncome - $this->lastMonthIncome) / abs($this->lastMonthIncome)) * 100;
    }

    #[Computed]
    public function balanceChangePercentage(): ?float
    {
        if ($this->lastMonthBalance === 0.0) {
            return null;
        }

        return (($this->totalBalance - $this->lastMonthBalance) / abs($this->lastMonthBalance)) * 100;
    }

    #[Computed]
    public function expensesChangeFormatted(): string
    {
        $change = $this->expensesChange;

        return ($change >= 0 ? '+' : '-').'$'.number_format(abs($change), 2);
    }

    #[Computed]
    public function incomeChangeFormatted(): string
    {
        $change = $this->incomeChange;

        return ($change >= 0 ? '+' : '-').'$'.number_format(abs($change), 2);
    }

    #[Computed]
    public function balanceChangeFormatted(): string
    {
        $change = $this->balanceChange;

        return ($change >= 0 ? '+' : '-').'$'.number_format(abs($change), 2);
    }

    #[Computed]
    public function expensesChangeColor(): string
    {
        return $this->expensesChange <= 0 ? 'lime' : 'pink';
    }

    #[Computed]
    public function incomeChangeColor(): string
    {
        return $this->incomeChange >= 0 ? 'lime' : 'pink';
    }

    #[Computed]
    public function balanceChangeColor(): string
    {
        return $this->balanceChange >= 0 ? 'lime' : 'pink';
    }
};
?>

<section class="mx-auto max-w-lg">
    @if ($this->transactions->count() === 0)
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="banknotes" size="lg" class="text-gray-400 dark:text-gray-600" />
            <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No transactions yet</flux:text>
        </div>
    @else
        <div class="flex flex-wrap justify-between gap-x-6 gap-y-4">
            <flux:heading size="xl">{{ $this->team->name }}</flux:heading>
        </div>

        <div class="mt-8 flex items-end justify-between">
            <flux:heading level="2">Overview</flux:heading>
            <flux:text>This month</flux:text>
        </div>
        <div class="mt-4 grid gap-8 sm:grid-cols-3">
            <div>
                <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
                <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Overall balance</div>
                <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                    @if ($this->totalBalance >= 0)
                        {{ $this->team->formatted_total_balance }}
                    @else
                        -{{ $this->team->formatted_total_balance }}
                    @endif
                </div>
                <div class="mt-3 text-sm/6 sm:text-xs/6">
                    @if (!is_null($this->balanceChangePercentage))
                        <flux:badge color="{{ $this->balanceChangeColor }}" size="sm">
                            {{ number_format($this->balanceChangePercentage, 1) }}%
                        </flux:badge>
                        <flux:text size="sm" inline>from last month</flux:text>
                    @endif
                </div>
            </div>
            <div>
                <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
                <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Expenses</div>
                <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                    ${{ number_format(abs($this->monthlyExpenses), 2) }}
                </div>
                <div class="mt-3 text-sm/6 sm:text-xs/6">
                    @if (!is_null($this->expensesChangePercentage))
                        <flux:badge color="{{ $this->expensesChangeColor }}" size="sm">
                            {{ number_format($this->expensesChangePercentage, 1) }}%
                        </flux:badge>
                        <flux:text size="sm" inline>from last month</flux:text>
                    @endif
                </div>
            </div>
            <div>
                <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
                <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Income</div>
                <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                    ${{ number_format($this->monthlyIncome, 2) }}
                </div>
                <div class="mt-3 text-sm/6 sm:text-xs/6">
                    @if (!is_null($this->incomeChangePercentage))
                        <flux:badge color="{{ $this->incomeChangeColor }}" size="sm">
                            {{ number_format($this->incomeChangePercentage, 1) }}%
                        </flux:badge>
                        <flux:text size="sm" inline>from last month</flux:text>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-14 flex items-end justify-between">
            <flux:heading level="2">Recent transactions</flux:heading>
        </div>

        <div class="mt-4">
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div class="divide-y divide-zinc-100 overflow-hidden dark:divide-white/5 dark:text-white">
                @foreach ($this->transactions as $transaction)
                    <div
                        wire:key="transaction-{{ $transaction->id }}"
                        class="flex items-center justify-between gap-4 py-4"
                    >
                        <div>
                            <div class="text-sm/6 font-semibold">
                                {{ $transaction->payee }}
                            </div>
                            @if ($transaction->category)
                                <div class="text-xs/6 text-zinc-500">
                                    {{ $transaction->category->name }}
                                </div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="tabular-nums">
                                @if ($transaction->amount === 0)
                                    <flux:text variant="strong">
                                        {{ $transaction->formatted_amount }}
                                    </flux:text>
                                @elseif ($transaction->amount > 0)
                                    <flux:badge color="lime" size="sm">
                                        {{ $transaction->display_amount }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="pink" size="sm">
                                        {{ $transaction->display_amount }}
                                    </flux:badge>
                                @endif
                            </div>
                            <div class="mt-1 text-xs/6 text-zinc-500">
                                {{ $transaction->display_date }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
