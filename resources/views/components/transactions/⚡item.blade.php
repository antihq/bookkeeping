<?php

use App\Models\Transaction;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public Transaction $transaction;

    public function delete()
    {
        $this->authorize('delete', $this->transaction);

        $this->transaction->delete();

        Flux::toast('Transaction deleted successfully.', variant: 'success');

        $this->dispatch('transaction-deleted');
    }
};
?>

<div {{ $attributes->class('flex items-center justify-between gap-4 py-4') }}>
    <div class="flex min-w-0 flex-1 items-center gap-4">
        <flux:text class="w-24 shrink-0">{{ $transaction->date }}</flux:text>
        <flux:text class="min-w-0 flex-1 truncate">{{ $transaction->title }}</flux:text>
        <flux:text class="hidden w-48 shrink-0 truncate text-gray-500 sm:block dark:text-gray-400">
            {{ $transaction->note ?? '-' }}
        </flux:text>
    </div>

    <div class="flex gap-4">
        <flux:text class="{{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
            {{ $transaction->display_amount }}
        </flux:text>

        <div>
            <flux:dropdown align="end">
                <flux:button variant="subtle" size="sm" square icon="ellipsis-horizontal" inset="top bottom" />
                <flux:menu>
                    <flux:menu.item icon="pencil-square" icon:variant="micro">Edit</flux:menu.item>
                    <flux:modal.trigger name="delete-transaction-{{ $transaction->id }}">
                        <flux:menu.item variant="danger" icon="trash" icon:variant="micro">Delete</flux:menu.item>
                    </flux:modal.trigger>
                </flux:menu>
            </flux:dropdown>
            <flux:modal name="delete-transaction-{{ $transaction->id }}" class="min-w-[22rem]">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Delete transaction?</flux:heading>
                        <flux:text class="mt-2">
                            You're about to delete "{{ $transaction->title }}". This action cannot be reversed.
                        </flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost" size="sm">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button wire:click="delete" variant="danger" size="sm">Delete transaction</flux:button>
                    </div>
                </div>
            </flux:modal>
        </div>
    </div>
</div>
