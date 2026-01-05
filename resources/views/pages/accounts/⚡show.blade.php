<?php

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Account $account;

    public string $transaction_date = '';

    public string $transaction_title = '';

    public ?string $transaction_note = null;

    public float $transaction_amount = 0.0;

    public function mount(Account $account)
    {
        $this->authorize('view', $account);

        $this->transaction_date = now()->format('Y-m-d');
    }

    public function delete()
    {
        $this->authorize('delete', $this->account);

        $this->account->delete();

        Flux::toast('Account deleted successfully.', variant: 'success');

        return $this->redirectRoute('accounts.index');
    }

    public function addTransaction()
    {
        $this->authorize('create', Transaction::class);

        $this->validate([
            'transaction_date' => ['required', 'date'],
            'transaction_title' => ['required', 'string', 'max:255'],
            'transaction_amount' => ['required', 'numeric'],
        ]);

        $amount = (int) round($this->transaction_amount * 100);

        $this->account->addTransaction(
            date: $this->transaction_date,
            title: $this->transaction_title,
            amount: $amount,
            note: $this->transaction_note,
            createdBy: $this->user->id,
        );

        Flux::toast('Transaction added successfully.', variant: 'success');

        $this->reset(['transaction_title', 'transaction_note', 'transaction_amount']);
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }

    #[Computed]
    public function transactions()
    {
        return $this->account->transactions()->paginate(10);
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-8">
            <div>
                <flux:heading size="lg">{{ $account->name }}</flux:heading>
            </div>
            <div>
                <flux:text class="text-2xl font-semibold">
                    {{ $account->formatted_balance }}
                </flux:text>
            </div>
        </div>

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
                    <flux:modal.trigger name="delete">
                        <flux:menu.item variant="danger" icon="trash" icon:variant="micro">Delete</flux:menu.item>
                    </flux:modal.trigger>
                @endcan
            </flux:menu>
        </flux:dropdown>
    </div>

    <div class="space-y-14">
        <div class="space-y-6">
            <header class="flex items-center justify-between gap-4">
                <div class="space-y-1">
                    <flux:heading>Transactions</flux:heading>
                </div>
                @can('create', Transaction::class)
                    <flux:modal.trigger name="add-transaction">
                        <flux:button variant="primary" size="sm">Add transaction</flux:button>
                    </flux:modal.trigger>
                @endcan
            </header>

            @if ($this->transactions->count() > 0)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Date</flux:table.column>
                        <flux:table.column>Title</flux:table.column>
                        <flux:table.column>Note</flux:table.column>
                        <flux:table.column>Amount</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->transactions as $transaction)
                            <flux:table.row>
                                <flux:table.cell>{{ $transaction->date }}</flux:table.cell>
                                <flux:table.cell>{{ $transaction->title }}</flux:table.cell>
                                <flux:table.cell>{{ $transaction->note ?? '-' }}</flux:table.cell>
                                <flux:table.cell
                                    class="{{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}"
                                >
                                    {{ $transaction->display_amount }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                <div class="flex justify-center">
                    {{ $this->transactions->links() }}
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12">
                    <flux:icon icon="arrow-path" size="lg" class="text-gray-400 dark:text-gray-600" />
                    <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No transactions yet</flux:text>
                </div>
            @endif

            @can('create', Transaction::class)
                <flux:modal name="add-transaction" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
                    <form wire:submit="addTransaction" class="space-y-6">
                        <div>
                            <flux:heading size="lg">Add transaction</flux:heading>
                            <flux:text class="mt-2">Add a new transaction to this account.</flux:text>
                        </div>

                        <flux:input wire:model="transaction_date" label="Date" type="date" required />

                        <flux:input wire:model="transaction_title" label="Title" type="text" required />

                        <flux:input wire:model="transaction_note" label="Note" type="text" placeholder="Optional" />

                        <flux:input
                            wire:model="transaction_amount"
                            label="Amount"
                            type="number"
                            step="0.01"
                            placeholder="Use negative values for expenses"
                            required
                        />

                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost" size="sm">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" size="sm" type="submit">Add transaction</flux:button>
                        </div>
                    </form>
                </flux:modal>
            @endcan
        </div>

        @can('delete', $account)
            <flux:modal name="delete" class="min-w-[22rem]">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Delete account?</flux:heading>
                        <flux:text class="mt-2">
                            You're about to delete "{{ $account->name }}". This action cannot be reversed.
                        </flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost" size="sm">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button wire:click="delete" variant="danger" size="sm">Delete account</flux:button>
                    </div>
                </div>
            </flux:modal>
        @endcan
    </div>
</section>
