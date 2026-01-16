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
        <ul class="divide-y divide-zinc-100 text-zinc-950 dark:divide-white/5 dark:text-white">
            @foreach ($this->accounts as $account)
                <li
                    wire:key="account-{{ $account->id }}"
                    class="relative flex items-center justify-between gap-4 py-4"
                >
                    <a href="{{ route('accounts.show', $account) }}" class="absolute inset-0 z-10"></a>
                    <div
                        class="flex min-w-0 flex-col gap-x-2 gap-y-1 md:flex-row md:items-center"
                        href="{{ route('accounts.show', $account) }}"
                        wire:navigate
                    >
                        <flux:text>
                            <flux:link
                                href="{{ route('accounts.show', $account) }}"
                                class="relative z-10 truncate"
                                variant="ghost"
                                wire:navigate
                            >
                                {{ $account->name }}
                            </flux:link>
                        </flux:text>
                        <flux:text class="text-sm/6 sm:text-[13px]/6">
                            {{ $account->display_type }}
                        </flux:text>
                    </div>

                    <div class="flex items-center gap-4">
                        @if ($account->balance_in_dollars === 0)
                            <flux:text>{{ $account->formatted_balance }}</flux:text>
                        @elseif ($account->balance_in_dollars > 0)
                            <flux:text color="green">{{ $account->formatted_balance }}</flux:text>
                        @else
                            <flux:text color="red">{{ $account->formatted_balance }}</flux:text>
                        @endif

                        <div>
                            <flux:dropdown align="end">
                                <flux:button variant="subtle" square icon="ellipsis-horizontal" class="z-10" />
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
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
