<?php

use App\Models\Team;
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
</section>