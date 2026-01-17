<?php

use App\Models\Team;
use App\Models\Transaction;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('All transactions')] class extends Component {
    public $page = 1;

    public function loadMore()
    {
        $this->page++;
    }

    public function deleteTransaction(int $id)
    {
        $transaction = $this->team->transactions()->findOrFail($id);

        $this->authorize('delete', $transaction);

        $transaction->delete();

        Flux::toast('Transaction deleted successfully.', variant: 'success');
    }

    #[Computed]
    public function transactions()
    {
        return $this->team
            ->transactions()
            ->with(['account', 'category', 'creator'])
            ->orderBy('date', 'desc')
            ->forPage($this->page, 25)
            ->get();
    }

    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam;
    }
};
?>

<section class="mx-auto max-w-lg">
    <div class="flex items-center justify-between flex-wrap gap-x-6 gap-y-4">
        <flux:heading size="xl">All transactions</flux:heading>
        <flux:button href="{{ route('transactions.create') }}" icon="plus" variant="primary" wire:navigate>Add transaction</flux:button>
    </div>

    <div class="mt-8">
        @if ($this->transactions->isNotEmpty())
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div class="divide-y divide-zinc-100 dark:divide-white/5 dark:text-white">
                @island(name: 'transactions', lazy: true)
                    @placeholder
                        @foreach (range(1, rand(3, 8)) as $i)
                            <flux:skeleton.group animate="shimmer" class="py-4">
                                <flux:skeleton class="h-15" />
                            </flux:skeleton.group>
                        @endforeach
                    @endplaceholder
                    @foreach ($this->transactions as $transaction)
                        <livewire:transactions.item
                            :$transaction
                            wire:key="transaction-{{ $transaction->id }}"
                        />
                    @endforeach
                @endisland
            </div>

            <div class="p-[.3125rem]">
                <flux:button
                    wire:click="loadMore"
                    wire:island.append="transactions"
                    variant="subtle"
                    class="w-full"
                >
                    Load more
                </flux:button>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-12">
                <flux:icon icon="document-text" size="lg" class="text-gray-400 dark:text-gray-600" />
                <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No transactions yet</flux:text>
            </div>
        @endif
    </div>
</section>
