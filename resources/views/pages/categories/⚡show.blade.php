<?php

use App\Models\Category;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Category')] class extends Component
{
    public Category $category;

    public $page = 1;

    public string $selectedPeriod = 'this_month';

    public function mount()
    {
        $this->authorize('view', $this->category);
    }

    public function loadMore()
    {
        $this->page++;
    }

    #[Computed]
    public function team(): Team
    {
        return $this->category->team;
    }

    #[Computed]
    public function transactions()
    {
        return $this->category
            ->transactions()
            ->orderBy('date', 'desc')
            ->forPage($this->page, 25)
            ->get();
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }

    #[Computed]
    public function selectedMonthDate(): Carbon
    {
        return $this->selectedPeriod === 'this_month' ? now() : now()->subMonth();
    }

    #[Computed]
    public function previousMonthDate(): Carbon
    {
        return $this->selectedMonthDate->copy()->subMonth();
    }

    private function getMonthTotal(Carbon $date, string $comparison): float
    {
        return $this->category
            ->transactions()
            ->where('amount', $comparison, 0)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function selectedMonthExpenses(): float
    {
        return $this->getMonthTotal($this->selectedMonthDate, '<');
    }

    #[Computed]
    public function selectedMonthIncome(): float
    {
        return $this->getMonthTotal($this->selectedMonthDate, '>');
    }

    #[Computed]
    public function previousMonthExpenses(): float
    {
        return $this->getMonthTotal($this->previousMonthDate, '<');
    }

    #[Computed]
    public function previousMonthIncome(): float
    {
        return $this->getMonthTotal($this->previousMonthDate, '>');
    }

    #[Computed]
    public function expensesChange(): float
    {
        return $this->selectedMonthExpenses - $this->previousMonthExpenses;
    }

    #[Computed]
    public function incomeChange(): float
    {
        return $this->selectedMonthIncome - $this->previousMonthIncome;
    }

    private function calculateChangePercentage(float $current, float $previous): ?float
    {
        if ($previous === 0.0) {
            return null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    #[Computed]
    public function expensesChangePercentage(): ?float
    {
        return $this->calculateChangePercentage($this->selectedMonthExpenses, $this->previousMonthExpenses);
    }

    #[Computed]
    public function incomeChangePercentage(): ?float
    {
        return $this->calculateChangePercentage($this->selectedMonthIncome, $this->previousMonthIncome);
    }

    private function formatChange(float $change, string $currencySymbol): string
    {
        return ($change >= 0 ? '+' : '-') . $currencySymbol . number_format(abs($change), 2);
    }

    #[Computed]
    public function expensesChangeFormatted(): string
    {
        return $this->formatChange($this->expensesChange, '$');
    }

    #[Computed]
    public function incomeChangeFormatted(): string
    {
        return $this->formatChange($this->incomeChange, '$');
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
    public function hasExpenses(): bool
    {
        return $this->selectedMonthExpenses !== 0.0;
    }

    #[Computed]
    public function hasIncome(): bool
    {
        return $this->selectedMonthIncome !== 0.0;
    }
};
?>

<section class="mx-auto max-w-lg">
    <flux:heading size="xl">{{ $category->name }}</flux:heading>

    <div class="mt-8 flex items-end justify-between gap-4">
        <flux:heading level="2">Overview</flux:heading>
        <div>
            <flux:select wire:model.live="selectedPeriod">
                <flux:select.option value="this_month">This month</flux:select.option>
                <flux:select.option value="last_month">Last month</flux:select.option>
            </flux:select>
        </div>
    </div>
    <div class="mt-4 grid gap-8 @if ($this->hasExpenses && $this->hasIncome) sm:grid-cols-2 @endif">
        @if ($this->hasExpenses)
        <div>
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Expenses</div>
            <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                ${{ number_format(abs($this->selectedMonthExpenses), 2) }}
            </div>
            <div class="mt-3 text-sm/6 sm:text-xs/6">
                @if (! is_null($this->expensesChangePercentage))
                    <flux:badge color="{{ $this->expensesChangeColor }}" size="sm">
                        {{ number_format($this->expensesChangePercentage, 1) }}%
                    </flux:badge>
                    <flux:text size="sm" inline class="whitespace-nowrap">from previous month</flux:text>
                @endif
            </div>
        </div>
        @endif
        @if ($this->hasIncome)
        <div>
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Income</div>
            <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                ${{ number_format(abs($this->selectedMonthIncome), 2) }}
            </div>
            <div class="mt-3 text-sm/6 sm:text-xs/6">
                @if (! is_null($this->incomeChangePercentage))
                    <flux:badge color="{{ $this->incomeChangeColor }}" size="sm">
                        {{ number_format($this->incomeChangePercentage, 1) }}%
                    </flux:badge>
                    <flux:text size="sm" inline class="whitespace-nowrap">from previous month</flux:text>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="mt-14">
        <flux:heading>Transactions</flux:heading>
    </div>

    <div class="mt-4">
        <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
        @if ($this->transactions->isNotEmpty())
            <div class="divide-y divide-zinc-100 dark:divide-white/5 dark:text-white">
                @island(name: 'transactions', lazy: true)
                    @placeholder
                        @foreach (range(1, rand(3, 8)) as $i)
                            <flux:skeleton.group animate="shimmer" class="py-4">
                                <flux:skeleton class="h-15" />
                            </flux:skeleton.group>
                            @unless ($loop->last)
                                <flux:separator variant="subtle" />
                            @endunless
                        @endforeach
                    @endplaceholder

                    @foreach ($this->transactions as $transaction)
                        <livewire:transactions.item :$transaction wire:key="transaction-{{ $transaction->id }}" />
                    @endforeach
                @endisland
            </div>
            <div class="p-[.3125rem]">
                <flux:button wire:click="loadMore" wire:island.append="transactions" variant="subtle" class="w-full">
                    Load more
                </flux:button>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-12">
                <flux:icon icon="tag" size="lg" class="text-zinc-400 dark:text-zinc-600" />
                <flux:text class="mt-4 text-zinc-500 dark:text-zinc-400">No transactions yet</flux:text>
            </div>
        @endif
    </div>
</section>
