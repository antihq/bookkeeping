<?php

use App\Models\Account;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Account')] class extends Component
{
    public Account $account;

    public string $date = '';

    public string $payee = '';

    public ?string $note = null;

    public string $amount = '';

    public string $type = 'expense';

    public ?int $category = null;

    public string $category_search = '';

    public $page = 1;

    public function mount()
    {
        $this->authorize('view', $this->account);

        $this->date = now()->format('Y-m-d');
    }

    public function addTransaction()
    {
        $this->authorize('create', Transaction::class);

        $this->validate([
            'date' => ['required', 'date'],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required'],
            'category' => ['nullable', 'exists:categories,id'],
        ]);

        $category = $this->category ? $this->team->categories()->findOrFail($this->category) : null;

        $this->account->addTransaction(
            input: [
                'date' => $this->date,
                'payee' => $this->payee,
                'amount' => $this->type === 'expense'
                        ? (int) -round((float) $this->amount * 100)
                        : (int) round((float) $this->amount * 100),
                'note' => $this->note,
            ],
            createdBy: $this->user,
            category: $category,
        );

        Flux::toast('Transaction added successfully.', variant: 'success');

        Flux::modals()->close();

        $this->reset(['payee', 'note', 'amount', 'category', 'page', 'type']);
    }

    public function createCategory()
    {
        $this->validate([
            'category_search' => ['required', 'unique:categories,name,NULL,id,team_id,' . $this->team->id],
        ]);

        $category = $this->team->categories()->create([
            'name' => $this->pull('category_search'),
        ]);

        $this->category = $category->id;
    }

    public function delete()
    {
        $this->authorize('delete', $this->account);

        $this->account->delete();

        Flux::toast('Account deleted successfully.', variant: 'success');

        return $this->redirectRoute('accounts.index');
    }

    public function deleteTransaction(int $id)
    {
        $transaction = $this->account->transactions()->findOrFail($id);

        $this->authorize('delete', $transaction);

        $transaction->delete();

        Flux::toast('Transaction deleted successfully.', variant: 'success');
    }

    public function loadMore()
    {
        $this->page++;
    }

    public function updatedAmount($value)
    {
        $this->amount = str_replace(['$', ','], '', $value);
    }

    #[Computed]
    public function categories()
    {
        return $this->team
            ->categories()
            ->when(
                $this->category_search,
                fn ($query) => $query->where('name', 'like', '%' . $this->category_search . '%'),
            )
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function team(): Team
    {
        return $this->account->team;
    }

    #[Computed]
    public function transactions()
    {
        return $this->account
            ->transactions()
            ->forPage($this->page, 10)
            ->get();
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <div class="space-y-2.5">
        <flux:heading size="xl">{{ $account->name }}</flux:heading>

        <div class="flex flex-wrap justify-between gap-x-6 gap-y-4">
            <div class="flex flex-wrap gap-x-10 gap-y-4 py-1.5">
                <span class="flex flex-wrap items-center gap-3">
                    <flux:text>Overall balance</flux:text>
                    <flux:text variant="strong">{{ $account->formatted_balance }}</flux:text>
                </span>
            </div>
            <div class="flex gap-2">
                @can('create', Transaction::class)
                    <flux:modal.trigger name="add-transaction">
                        <flux:button variant="primary">Add transaction</flux:button>
                    </flux:modal.trigger>
                @endcan

                <flux:dropdown align="end">
                    <flux:button variant="subtle" square icon="ellipsis-horizontal" />
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

                @can('delete', $account)
                    <flux:modal name="delete" class="w-full max-w-xs sm:max-w-md">
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
                                <flux:button wire:click="delete" variant="primary">Delete</flux:button>
                            </div>
                        </div>
                    </flux:modal>
                @endcan
            </div>
        </div>
    </div>

    <div class="space-y-14">
        <div class="space-y-6">
            @if ($this->transactions->count() > 0)
                <div>
                    <div class="divide-y divide-zinc-100 text-zinc-950 dark:divide-white/5 dark:text-white">
                        @island(name: 'transactions', always: true)
                            @foreach ($this->transactions as $transaction)
                                <livewire:transactions.item
                                    :$transaction
                                    wire:key="transaction-{{ $transaction->id }}"
                                    lazy
                                />
                            @endforeach
                        @endisland
                    </div>

                    <div wire:intersect.append="loadMore" wire:island="transactions"></div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12">
                    <flux:icon icon="credit-card" size="lg" class="text-gray-400 dark:text-gray-600" />
                    <flux:text class="mt-4 text-gray-500 dark:text-gray-400">No transactions yet</flux:text>
                </div>
            @endif

            @can('create', Transaction::class)
                <flux:modal name="add-transaction" class="w-full sm:max-w-lg">
                    <form wire:submit="addTransaction" class="space-y-6">
                        <div>
                            <flux:heading size="lg">Add transaction</flux:heading>
                            <flux:text class="mt-2">Add a new transaction to this account.</flux:text>
                        </div>

                        <flux:radio.group wire:model="type" label="Transaction type" variant="segmented" label:sr-only>
                            <flux:radio value="expense" icon="minus">Expense</flux:radio>
                            <flux:radio value="income" icon="plus">Income</flux:radio>
                        </flux:radio.group>

                        <flux:field>
                            <flux:input.group>
                                <flux:label sr-only>Amount</flux:label>
                                <flux:input.group.prefix>{{ $account->currencySymbol }}</flux:input.group.prefix>
                                <flux:input
                                    wire:model="amount"
                                    type="text"
                                    mask:dynamic="$money($input)"
                                    inputmode="decimal"
                                    placeholder="0.00"
                                    required
                                    autofocus
                                />
                            </flux:input.group>
                            <flux:error name="amount" />
                        </flux:field>

                        <flux:input
                            wire:model="payee"
                            label="Payee"
                            type="text"
                            placeholder="Payee"
                            label:sr-only
                            required
                        />

                        <flux:select
                            wire:model="category"
                            variant="combobox"
                            label="Category"
                            placeholder="Category"
                            label:sr-only
                        >
                            <x-slot name="input">
                                <flux:select.input wire:model="category_search" />
                            </x-slot>

                            @foreach ($this->categories as $category)
                                <flux:select.option :value="$category->id" :wire:key="'cat-'.$category->id">
                                    {{ $category->name }}
                                </flux:select.option>
                            @endforeach

                            <flux:select.option.create wire:click="createCategory" min-length="1">
                                Create "<span wire:text="category_search"></span>"
                            </flux:select.option.create>
                        </flux:select>

                        <flux:input wire:model="note" label="Note" type="text" placeholder="Note" label:sr-only />

                        <flux:date-picker wire:model="date" label="Date" required />

                        <div
                            class="flex gap-2"
                        >
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" type="submit">Add transaction</flux:button>
                        </div>
                    </form>
                </flux:modal>
            @endcan
        </div>
    </div>
</section>
