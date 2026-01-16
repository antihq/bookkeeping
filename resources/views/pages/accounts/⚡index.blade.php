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

<section class="mx-auto max-w-lg space-y-8">
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
            @can('create', App\Models\Account::class)
                <flux:button href="{{ route('accounts.create') }}" variant="primary" wire:navigate>
                    Add account
                </flux:button>
            @endcan
        </div>

        <div
            class="relative h-full w-full rounded-xl bg-white shadow-[0px_0px_0px_1px_rgba(9,9,11,0.07),0px_2px_2px_0px_rgba(9,9,11,0.05)] dark:bg-zinc-900 dark:shadow-[0px_0px_0px_1px_rgba(255,255,255,0.1)] dark:before:pointer-events-none dark:before:absolute dark:before:-inset-px dark:before:rounded-xl dark:before:shadow-[0px_2px_8px_0px_rgba(0,0,0,0.20),0px_1px_0px_0px_rgba(255,255,255,0.06)_inset] forced-colors:outline"
        >
            <ul role="list" class="overflow-hidden p-[.3125rem]">
                <li>
                    <div class="relative flex justify-between gap-x-6 rounded-lg px-3.5 py-2.5 hover:bg-zinc-950/2.5 sm:px-3 sm:py-1.5 dark:hover:bg-white/2.5">
                        <flux:heading>
                            <a href="{{ route('transactions.index') }}" wire:navigate>
                                <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                All accounts
                            </a>
                        </flux:heading>

                            <div class="flex shrink-0 items-center gap-x-4">
                                <div class="text-right tabular-nums">
                                    @if ($this->totalBalance === 0)
                                        <flux:text variant="strong" class="font-medium whitespace-nowrap">
                                            {{ $this->team->formatted_total_balance }}
                                        </flux:text>
                                    @elseif ($this->totalBalance > 0)
                                        <flux:text variant="strong" color="green" class="font-medium whitespace-nowrap">
                                            {{ $this->team->formatted_total_balance }}
                                        </flux:text>
                                    @else
                                        <flux:text variant="strong" color="red" class="font-medium whitespace-nowrap">
                                            {{ $this->team->formatted_total_balance }}
                                        </flux:text>
                                    @endif
                                </div>

                            <flux:dropdown align="end">
                                <flux:button variant="subtle" square icon="ellipsis-horizontal" class="-mr-2" />
                                <flux:menu>
                                    <flux:menu.item
                                        href="{{ route('transactions.index') }}"
                                        icon="eye"
                                        icon:variant="micro"
                                        wire:navigate
                                    >
                                        View
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <div
            class="relative h-full w-full rounded-xl bg-white shadow-[0px_0px_0px_1px_rgba(9,9,11,0.07),0px_2px_2px_0px_rgba(9,9,11,0.05)] dark:bg-zinc-900 dark:shadow-[0px_0px_0px_1px_rgba(255,255,255,0.1)] dark:before:pointer-events-none dark:before:absolute dark:before:-inset-px dark:before:rounded-xl dark:before:shadow-[0px_2px_8px_0px_rgba(0,0,0,0.20),0px_1px_0px_0px_rgba(255,255,255,0.06)_inset] forced-colors:outline"
        >
            <ul role="list" class="overflow-hidden p-[.3125rem]">
                @foreach ($this->accounts as $account)
                    <li wire:key="account-{{ $account->id }}">
                        <div class="relative flex justify-between gap-x-6 rounded-lg px-3.5 py-2.5 hover:bg-zinc-950/2.5 sm:px-3 sm:py-1.5 dark:hover:bg-white/2.5">
                            <div>
                                <flux:heading class="truncate">
                                    <a
                                        href="{{ route('accounts.show', $account) }}"
                                        wire:navigate
                                    >
                                        <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                        {{ $account->name }}
                                    </a>
                                </flux:heading>
                                <flux:text class="mt-1 text-sm/5 sm:text-[13px]/5">
                                    {{ $account->display_type }}
                                </flux:text>
                            </div>

                            <div class="flex shrink-0 items-center gap-x-4">
                                <div class="text-right tabular-nums">
                                    @if ($account->balance_in_dollars === 0)
                                        <flux:text variant="strong" class="font-medium whitespace-nowrap">
                                            {{ $account->formatted_balance }}
                                        </flux:text>
                                    @elseif ($account->balance_in_dollars > 0)
                                        <flux:text variant="strong" color="green" class="font-medium whitespace-nowrap">
                                            {{ $account->formatted_balance }}
                                        </flux:text>
                                    @else
                                        <flux:text variant="strong" color="red" class="font-medium whitespace-nowrap">
                                            {{ $account->formatted_balance }}
                                        </flux:text>
                                    @endif
                                </div>

                                <flux:dropdown align="end">
                                    <flux:button variant="subtle" square icon="ellipsis-horizontal" class="-mr-2" />
                                    <flux:menu>
                                        @can('update', $account)
                                            <flux:menu.item
                                                href="{{ route('accounts.edit', $account) }}"
                                                icon="pencil-square"
                                                icon:variant="micro"
                                                wire:navigate
                                            >
                                                Edit
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete', $account)
                                            <flux:modal.trigger name="delete-{{ $account->id }}">
                                                <flux:menu.item variant="danger" icon="trash" icon:variant="micro">
                                                    Delete
                                                </flux:menu.item>
                                            </flux:modal.trigger>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </div>

                        @unless ($loop->last)
                            <li class="mx-3.5 my-1 h-px sm:mx-3">
                                <flux:separator variant="subtle" wire:key="separator-{{ $account->id }}" />
                            </li>
                        @endunless

                        @can('delete', $account)
                            <flux:modal name="delete-{{ $account->id }}" class="w-full max-w-xs sm:max-w-md">
                                <div class="space-y-6 sm:space-y-4">
                                    <div>
                                        <flux:heading>Delete account?</flux:heading>
                                        <flux:text class="mt-2">
                                            You're about to delete "{{ $account->name }}". All associated
                                            transactions will also be deleted. This action cannot be reversed.
                                        </flux:text>
                                    </div>
                                    <div class="flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto">
                                        <flux:modal.close>
                                            <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                                        </flux:modal.close>
                                        <flux:button
                                            wire:click="delete({{ $account->id }})"
                                            variant="primary"
                                        >
                                            Delete
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        @endcan
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
