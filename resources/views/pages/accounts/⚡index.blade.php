<?php

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function mount()
    {
        $this->authorize('viewAny', Account::class);
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }

    #[Computed]
    public function team()
    {
        return $this->user->currentTeam;
    }

    #[Computed]
    public function accounts()
    {
        return $this->team->accounts()->get();
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">Accounts</flux:heading>

        @can('create', App\Models\Account::class)
            <flux:button href="{{ route('accounts.create') }}" variant="primary" size="sm" wire:navigate>
                Create account
            </flux:button>
        @endcan
    </div>

    @if ($this->accounts->count() === 0)
        <div class="flex flex-col items-center justify-center py-12">
            <flux:icon icon="credit-card" size="lg" class="text-gray-400 dark:text-gray-600" />
            <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No accounts yet</flux:text>
            @can('create', App\Models\Account::class)
                <flux:button href="{{ route('accounts.create') }}" variant="primary" class="mt-4" size="sm">
                    Create your first account
                </flux:button>
            @endcan
        </div>
    @else
        <div class="divide-y divide-zinc-100 text-zinc-950 dark:divide-white/5 dark:text-white">
            @foreach ($this->accounts as $account)
                <div wire:key="account-{{ $account->id }}" class="flex items-center justify-between gap-4 py-4">
                    <div class="flex min-w-0 items-center gap-2">
                        <flux:text>
                            <flux:link
                                href="{{ route('accounts.show', $account) }}"
                                class="truncate"
                                :accent="false"
                                wire:navigate
                            >
                                {{ $account->name }}
                            </flux:link>
                        </flux:text>
                        <flux:badge size="sm" variant="subtle">
                            {{ $account->display_type }}
                        </flux:badge>
                        <flux:badge size="sm">
                            {{ $account->currency }}
                        </flux:badge>
                    </div>

                    <div class="flex items-center gap-4">
                        <flux:text>{{ $account->formatted_balance }}</flux:text>

                        <div>
                            <flux:dropdown align="end">
                                <flux:button variant="subtle" size="sm" square icon="ellipsis-horizontal" />
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
                                <flux:modal name="delete-{{ $account->id }}" class="min-w-[22rem]">
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Delete account?</flux:heading>
                                            <flux:text class="mt-2">
                                                You're about to delete "{{ $account->name }}". This action cannot be
                                                reversed.
                                            </flux:text>
                                        </div>
                                        <div class="flex gap-2">
                                            <flux:spacer />
                                            <flux:modal.close>
                                                <flux:button variant="ghost" size="sm">Cancel</flux:button>
                                            </flux:modal.close>
                                            <flux:button
                                                wire:click="delete({{ $account->id }})"
                                                variant="danger"
                                                size="sm"
                                            >
                                                Delete account
                                            </flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
