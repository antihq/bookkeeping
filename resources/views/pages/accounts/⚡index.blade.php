<?php

use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Accounts')] class extends Component {
    #[Computed]
    public function accounts()
    {
        return $this->team->accounts()->get();
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

    public function delete(int $accountId): void
    {
        $account = $this->team->accounts()->findOrFail($accountId);

        $this->authorize('delete', $account);

        $account->delete();

        Flux::toast('Account deleted successfully.', variant: 'success');
    }
};
?>

<section class="mx-auto max-w-lg">
    @if ($this->accounts->count() === 0)
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="credit-card" size="lg" class="text-gray-400 dark:text-gray-600" />
            <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No accounts yet</flux:text>
            @can('create', Account::class)
                <flux:button href="{{ route('accounts.create') }}" variant="primary" class="mt-4" size="sm">
                    Add your first account
                </flux:button>
            @endcan
        </div>
    @else
        <div class="flex flex-wrap justify-between gap-x-6 gap-y-4">
            <flux:heading size="xl">Accounts</flux:heading>
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
            <flux:heading level="2">All accounts</flux:heading>
            @can('create', App\Models\Account::class)
                <flux:button href="{{ route('accounts.create') }}" variant="primary" class="-my-0.5" wire:navigate>
                    Add account
                </flux:button>
            @endcan
        </div>

        <div class=" mt-4">
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div
                class="divide-y divide-zinc-100 overflow-hidden dark:divide-white/5 dark:text-white"
            >
                @foreach ($this->accounts as $account)
                    <div
                        wire:key="account-{{ $account->id }}"
                        class="relative flex items-center justify-between gap-4 py-4"
                    >
                        <div>
                            <div class="text-sm/6 font-semibold">
                                <a href="{{ route('accounts.show', $account) }}" wire:navigate>
                                    {{ $account->name }}
                                </a>
                            </div>
                            <div class="text-xs/6 text-zinc-500">
                                {{ $account->display_type }}
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-x-4">
                            <div class="text-right tabular-nums">
                                @if ($account->balance_in_dollars === 0)
                                    <flux:text variant="strong" class="font-medium whitespace-nowrap">
                                        {{ $account->formatted_balance }}
                                    </flux:text>
                                @elseif ($account->balance_in_dollars > 0)
                                    <flux:badge color="lime" size="sm" class="whitespace-nowrap">
                                        {{ $account->formatted_balance }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="pink" size="sm" class="whitespace-nowrap">
                                        {{ $account->formatted_balance }}
                                    </flux:badge>
                                @endif
                            </div>
                            <flux:dropdown align="end">
                                <flux:button variant="subtle" square icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item
                                        href="{{ route('accounts.show', $account) }}"
                                        wire:navigate
                                    >
                                        View
                                    </flux:menu.item>

                                    @can('update', $account)
                                        <flux:menu.item
                                            href="{{ route('accounts.edit', $account) }}"
                                            wire:navigate
                                        >
                                            Edit
                                        </flux:menu.item>
                                    @endcan

                                    @can('delete', $account)
                                        <flux:modal.trigger name="delete-{{ $account->id }}">
                                            <flux:menu.item variant="danger">
                                                Delete
                                            </flux:menu.item>
                                        </flux:modal.trigger>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
