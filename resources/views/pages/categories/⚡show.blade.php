<?php

use App\Models\Category;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Category')] class extends Component
{
    public Category $category;

    public $page = 1;

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
};
?>

<section class="mx-auto max-w-lg">
    <flux:heading size="xl">{{ $category->name }}</flux:heading>

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
