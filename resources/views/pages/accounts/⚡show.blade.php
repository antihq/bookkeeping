<?php

use App\Models\Account;
use App\Models\Category;
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

    public string $transaction_payee = '';

    public ?string $transaction_note = null;

    public float $transaction_amount = 0.0;

    public ?int $transaction_category_id = null;

    public string $category_search = '';

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

    public function deleteTransaction(int $transactionId)
    {
        $transaction = Transaction::findOrFail($transactionId);

        $this->authorize('delete', $transaction);

        $transaction->delete();

        Flux::toast('Transaction deleted successfully.', variant: 'success');
    }

    public function addTransaction()
    {
        $this->authorize('create', Transaction::class);

        $this->validate([
            'transaction_date' => ['required', 'date'],
            'transaction_payee' => ['required', 'string', 'max:255'],
            'transaction_amount' => ['required', 'numeric'],
            'transaction_category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $amount = (int) round($this->transaction_amount * 100);

        $this->account->addTransaction(
            date: $this->transaction_date,
            payee: $this->transaction_payee,
            amount: $amount,
            note: $this->transaction_note,
            createdBy: $this->user->id,
            categoryId: $this->transaction_category_id,
        );

        Flux::toast('Transaction added successfully.', variant: 'success');

        Flux::modals()->close();

        $this->reset(['transaction_payee', 'transaction_note', 'transaction_amount', 'transaction_category_id']);
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

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->where('team_id', $this->account->team_id)
            ->when(
                $this->category_search,
                fn ($query) => $query->where('name', 'like', '%' . $this->category_search . '%'),
            )
            ->limit(20)
            ->get();
    }

    public function createCategory()
    {
        $this->validate([
            'category_search' => ['required', 'unique:categories,name,NULL,id,team_id,' . $this->account->team_id],
        ]);

        $category = Category::create([
            'name' => $this->category_search,
            'team_id' => $this->account->team_id,
        ]);

        $this->transaction_category_id = $category->id;
        $this->category_search = '';
    }

    public function updatedCategorySearch()
    {
        $this->resetErrorBag('category_search');
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-8">
            <flux:heading size="lg">{{ $account->name }}</flux:heading>
        </div>

        <div class="flex items-center gap-6">
            <div class="inline-flex flex-wrap gap-1">
                <flux:text>Overall balance:</flux:text>
                <flux:heading>{{ $account->formatted_balance }}</flux:heading>
            </div>
            @can('create', Transaction::class)
                <flux:modal.trigger name="add-transaction">
                    <flux:button variant="primary" size="sm">Add transaction</flux:button>
                </flux:modal.trigger>
            @endcan

            <flux:dropdown align="end">
                <flux:button variant="subtle" size="sm" square icon="ellipsis-horizontal" inset="left" />
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
    </div>

    <div class="space-y-14">
        <div class="space-y-6">
            @if ($this->transactions->count() > 0)
                <div class="divide-y divide-zinc-100 text-zinc-950 dark:divide-white/5 dark:text-white">
                    @foreach ($this->transactions as $transaction)
                        <livewire:transactions.item :$transaction wire:key="transaction-{{ $transaction->id }}" />
                    @endforeach
                </div>
                <div class="flex justify-center">
                    {{ $this->transactions->links() }}
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12">
                    <flux:icon icon="credit-card" size="lg" class="text-gray-400 dark:text-gray-600" />
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

                        <flux:input wire:model="transaction_payee" label="Payee" type="text" required />

                        <flux:select
                            wire:model="transaction_category_id"
                            variant="combobox"
                            label="Category"
                            placeholder="Optional"
                            :filter="false"
                        >
                            <x-slot name="input">
                                <flux:select.input wire:model.live="category_search" />
                            </x-slot>
                            @foreach ($this->categories as $category)
                                <flux:select.option :value="$category->id" :wire:key="'cat-'.$category->id">
                                    {{ $category->name }}
                                </flux:select.option>
                            @endforeach

                            <flux:select.option.create wire:click="createCategory" min-length="1">
                                Create "
                                <span wire:text="category_search"></span>
                                "
                            </flux:select.option.create>
                        </flux:select>

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
