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
        return $this->team->transactions()
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
        </div>
        <div class="mt-4 grid gap-8 sm:grid-cols-1">
            <div>
                <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
                <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Overal balance</div>
                <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                    @if ($this->totalBalance >= 0)
                        {{ $this->team->formatted_total_balance }}
                    @else
                        -{{ $this->team->formatted_total_balance }}
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-14 flex items-end justify-between">
            <flux:heading level="2">Recent transactions</flux:heading>
        </div>

        <div class=" mt-4">
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div
                class="divide-y divide-zinc-100 overflow-hidden dark:divide-white/5 dark:text-white"
            >
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
                            <div class="text-xs/6 text-zinc-500 mt-1">
                                {{ $transaction->display_date }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
