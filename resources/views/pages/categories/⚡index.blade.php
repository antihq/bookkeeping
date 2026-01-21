<?php

use App\Models\Category;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Categories')] class extends Component {
    public string $selectedPeriod = 'this_month';

    #[Computed]
    public function categories()
    {
        $date = $this->selectedMonthDate;

        return $this->team->categories()
            ->whereHas('transactions', function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month);
            })
            ->with(['transactions' => function ($query) use ($date) {
                $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month);
            }])
            ->get();
    }

    #[Computed]
    public function selectedMonthDate(): Carbon
    {
        return $this->selectedPeriod === 'this_month' ? now() : now()->subMonth();
    }

    private function getCategoryBalance(Category $category): float
    {
        return $category->transactions->sum('amount') / 100;
    }

    public function categoryBalanceFormatted(Category $category): string
    {
        $balance = $this->getCategoryBalance($category);

        return '$'.number_format($balance, 2);
    }

    public function categoryBalanceColor(Category $category): string
    {
        return $this->getCategoryBalance($category) >= 0 ? 'lime' : 'pink';
    }

    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam;
    }
};
?>

<section class="mx-auto max-w-lg">
    @if ($this->categories->count() === 0)
        <div class="flex flex-col items-center justify-center py-12">
            <flux:heading>No categories yet.</flux:heading>
            <flux:text>Categories will appear here once you add transactions.</flux:text>
        </div>
    @else
        <div class="flex items-end justify-between gap-4">
            <flux:heading size="xl">All categories</flux:heading>
            <div>
                <flux:select wire:model.live="selectedPeriod">
                    <flux:select.option value="this_month">This month</flux:select.option>
                    <flux:select.option value="last_month">Last month</flux:select.option>
                </flux:select>
            </div>
        </div>

        <div class="mt-8">
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div
                class="divide-y divide-zinc-100 overflow-hidden dark:divide-white/5 dark:text-white"
            >
                @foreach ($this->categories as $category)
                    <div
                        wire:key="category-{{ $category->id }}"
                        class="relative flex items-center justify-between gap-4 py-4"
                    >
                        <div>
                            <flux:heading class="leading-6!">
                                <a href="{{ route('categories.show', $category) }}" wire:navigate>
                                    {{ $category->name }}
                                </a>
                            </flux:heading>
                        </div>

                        <div class="flex shrink-0 items-center gap-x-4">
                            <div class="text-right tabular-nums">
                                <flux:badge color="{{ $this->categoryBalanceColor($category) }}" size="sm" class="whitespace-nowrap">
                                    {{ $this->categoryBalanceFormatted($category) }}
                                </flux:badge>
                            </div>
                            <flux:dropdown align="end">
                                <flux:button variant="subtle" square icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item
                                        href="{{ route('categories.show', $category) }}"
                                        wire:navigate
                                    >
                                        View
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
