<?php

use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Breakdown')] class extends Component
{
    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam;
    }

    #[Computed]
    public function monthlyIncome(): float
    {
        return $this->team
            ->transactions()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->where('amount', '>', 0)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function monthlyExpenses(): float
    {
        return abs($this->team
            ->transactions()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->where('amount', '<', 0)
            ->sum('amount')) / 100;
    }

    #[Computed]
    public function currentMonth(): string
    {
        return now()->format('F');
    }

    #[Computed]
    public function incomeByCategory(): Collection
    {
        return $this->team
            ->transactions()
            ->selectRaw('category_id, COALESCE(categories.name, "Uncategorized") as category_name, SUM(amount) as total_amount')
            ->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->where('amount', '>', 0)
            ->groupBy('category_id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($item) => [
                'category_name' => $item->category_name,
                'total_amount' => $item->total_amount / 100,
            ]);
    }

    #[Computed]
    public function expensesByCategory(): Collection
    {
        return $this->team
            ->transactions()
            ->selectRaw('category_id, COALESCE(categories.name, "Uncategorized") as category_name, SUM(ABS(amount)) as total_amount')
            ->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->where('amount', '<', 0)
            ->groupBy('category_id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($item) => [
                'category_name' => $item->category_name,
                'total_amount' => abs($item->total_amount) / 100,
            ]);
    }
};
?>

<section class="mx-auto max-w-lg space-y-8">
    <div class="space-y-6 sm:space-y-4">
        <flux:heading size="lg">This month</flux:heading>

        <div class="grid gap-4">
            <div
                class="relative h-full w-full rounded-xl bg-white shadow-[0px_0px_0px_1px_rgba(9,9,11,0.07),0px_2px_2px_0px_rgba(9,9,11,0.05)] dark:bg-zinc-900 dark:shadow-[0px_0px_0px_1px_rgba(255,255,255,0.1)] dark:before:pointer-events-none dark:before:absolute dark:before:-inset-px dark:before:rounded-xl dark:before:shadow-[0px_2px_8px_0px_rgba(0,0,0,0.20),0px_1px_0px_0px_rgba(255,255,255,0.06)_inset] forced-colors:outline p-6"
            >
                <flux:text>Income</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums" color="green">
                    ${{ number_format($this->monthlyIncome, 2) }}
                </flux:heading>
            </div>

            <div
                class="relative h-full w-full rounded-xl bg-white shadow-[0px_0px_0px_1px_rgba(9,9,11,0.07),0px_2px_2px_0px_rgba(9,9,11,0.05)] dark:bg-zinc-900 dark:shadow-[0px_0px_0px_1px_rgba(255,255,255,0.1)] dark:before:pointer-events-none dark:before:absolute dark:before:-inset-px dark:before:rounded-xl dark:before:shadow-[0px_2px_8px_0px_rgba(0,0,0,0.20),0px_1px_0px_0px_rgba(255,255,255,0.06)_inset] forced-colors:outline p-6"
            >
                <flux:text>Expenses</flux:text>
                <flux:heading size="xl" class="mt-2 tabular-nums" color="red">
                    ${{ number_format($this->monthlyExpenses, 2) }}
                </flux:heading>
            </div>
        </div>
    </div>

    <div class="space-y-6 sm:space-y-4">
        <flux:heading size="lg">Income by Category</flux:heading>

        @if ($this->incomeByCategory->isNotEmpty())
            <div
                class="relative h-full w-full rounded-xl bg-white shadow-[0px_0px_0px_1px_rgba(9,9,11,0.07),0px_2px_2px_0px_rgba(9,9,11,0.05)] dark:bg-zinc-900 dark:shadow-[0px_0px_0px_1px_rgba(255,255,255,0.1)] dark:before:pointer-events-none dark:before:absolute dark:before:-inset-px dark:before:rounded-xl dark:before:shadow-[0px_2px_8px_0px_rgba(0,0,0,0.20),0px_1px_0px_0px_rgba(255,255,255,0.06)_inset] forced-colors:outline"
            >
                <div class="overflow-hidden p-[.3125rem]">
                    @foreach ($this->incomeByCategory as $item)
                        <div class="flex flex-wrap items-start justify-between gap-3 px-3.5 py-2.5 sm:px-3 sm:py-1.5">
                            <flux:text variant="strong" class="font-medium">{{ $item['category_name'] }}</flux:text>
                            <flux:text variant="strong" class="whitespace-nowrap text-green-600 dark:text-green-400">
                                ${{ number_format($item['total_amount'], 2) }}
                            </flux:text>
                        </div>
                        @unless ($loop->last)
                            <li class="mx-3.5 my-1 h-px sm:mx-3">
                                <flux:separator variant="subtle" />
                            </li>
                        @endunless
                    @endforeach
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-12">
                <flux:icon icon="trending-up" size="lg" class="text-gray-400 dark:text-gray-600" />
                <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No income this month</flux:text>
            </div>
        @endif
    </div>

    <div class="space-y-6 sm:space-y-4">
        <flux:heading size="lg">Expenses by Category</flux:heading>

        @if ($this->expensesByCategory->isNotEmpty())
            <div
                class="relative h-full w-full rounded-xl bg-white shadow-[0px_0px_0px_1px_rgba(9,9,11,0.07),0px_2px_2px_0px_rgba(9,9,11,0.05)] dark:bg-zinc-900 dark:shadow-[0px_0px_0px_1px_rgba(255,255,255,0.1)] dark:before:pointer-events-none dark:before:absolute dark:before:-inset-px dark:before:rounded-xl dark:before:shadow-[0px_2px_8px_0px_rgba(0,0,0,0.20),0px_1px_0px_0px_rgba(255,255,255,0.06)_inset] forced-colors:outline"
            >
                <div class="overflow-hidden p-[.3125rem]">
                    @foreach ($this->expensesByCategory as $item)
                        <div class="flex flex-wrap items-start justify-between gap-3 px-3.5 py-2.5 sm:px-3 sm:py-1.5">
                            <flux:text variant="strong" class="font-medium">{{ $item['category_name'] }}</flux:text>
                            <flux:text variant="strong" class="whitespace-nowrap text-red-600 dark:text-red-400">
                                ${{ number_format($item['total_amount'], 2) }}
                            </flux:text>
                        </div>
                        @unless ($loop->last)
                            <div class="mx-3.5 my-1 h-px sm:mx-3">
                                <flux:separator variant="subtle" />
                            </div>
                        @endunless
                    @endforeach
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-12">
                <flux:icon icon="trending-down" size="lg" class="text-gray-400 dark:text-gray-600" />
                <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No expenses this month</flux:text>
            </div>
        @endif
    </div>
</section>
