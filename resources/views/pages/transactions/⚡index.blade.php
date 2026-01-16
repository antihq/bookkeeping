<?php

use App\Models\Team;
use App\Models\Transaction;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('All accounts')] class extends Component {
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

<section class="mx-auto max-w-lg space-y-8">
    <div class="space-y-6 sm:space-y-4">
        <flux:heading size="lg">All accounts</flux:heading>

        @if ($this->transactions->isNotEmpty())
            <div
                class="relative h-full w-full rounded-xl bg-white shadow-[0px_0px_0px_1px_rgba(9,9,11,0.07),0px_2px_2px_0px_rgba(9,9,11,0.05)] dark:bg-zinc-900 dark:shadow-[0px_0px_0px_1px_rgba(255,255,255,0.1)] dark:before:pointer-events-none dark:before:absolute dark:before:-inset-px dark:before:rounded-xl dark:before:shadow-[0px_2px_8px_0px_rgba(0,0,0,0.20),0px_1px_0px_0px_rgba(255,255,255,0.06)_inset] forced-colors:outline"
            >
                <ul role="list" class="overflow-hidden p-[.3125rem]">
                    @island(name: 'transactions', lazy: true)
                        @placeholder
                            @foreach (range(1, rand(3, 8)) as $i)
                                <flux:skeleton.group animate="shimmer">
                                    <flux:skeleton class="h-15 rounded-lg" />
                                </flux:skeleton.group>
                                @unless ($loop->last)
                                    <li class="mx-3.5 my-1 h-px sm:mx-3">
                                        <flux:separator variant="subtle" />
                                    </li>
                                @endunless
                            @endforeach
                        @endplaceholder
                        @foreach ($this->transactions as $transaction)
                            <livewire:transactions.item
                                :$transaction
                                wire:key="transaction-{{ $transaction->id }}"
                            />
                            @unless ($loop->last)
                                <li class="mx-3.5 my-1 h-px sm:mx-3">
                                    <flux:separator variant="subtle" wire:key="separator-{{ $transaction->id }}" />
                                </li>
                            @endunless
                        @endforeach
                    @endisland
                </ul>

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
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-12">
                <flux:icon icon="document-text" size="lg" class="text-gray-400 dark:text-gray-600" />
                <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No transactions yet</flux:text>
            </div>
        @endif
    </div>
</section>
