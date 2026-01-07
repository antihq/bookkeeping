<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Account')] class extends Component {
    public Account $account;

    public string $date = '';

    public string $payee = '';

    public ?string $note = null;

    public string $amount = '';

    public ?int $category_id = null;

    public string $category_search = '';

    public $page = 1;

    public function mount(Account $account)
    {
        $this->authorize('view', $account);

        $this->date = now()->format('Y-m-d');
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

    public function addTransaction()
    {
        $this->authorize('create', Transaction::class);

        $this->validate([
            'date' => ['required', 'date'],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $category = $this->category_id ? $this->account->team->categories()->findOrFail($this->category_id) : null;

        $this->account->addTransaction(
            input: ($input = [
                'date' => $this->date,
                'payee' => $this->payee,
                'amount' => (int) round((float) $this->amount * 100),
                'note' => $this->note,
            ]),
            createdBy: $this->user,
            category: $category,
        );

        Flux::toast('Transaction added successfully.', variant: 'success');

        Flux::modals()->close();

        $this->reset(['payee', 'note', 'amount', 'category_id']);
    }

    public function updatedAmount($value)
    {
        $this->amount = str_replace(['$', ','], '', $value);
    }

    public function loadMore()
    {
        $this->page++;
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
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

        $this->category_id = $category->id;
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
                <div>
                    <div class="divide-y divide-zinc-100 text-zinc-950 dark:divide-white/5 dark:text-white">
                        @island(name: 'transactions')
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

                        <flux:date-picker wire:model="date" label="Date" required />

                        <flux:input wire:model="payee" label="Payee" type="text" required />

                        <flux:select
                            wire:model="category_id"
                            variant="combobox"
                            label="Category"
                            placeholder="Optional"
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
                                Create "
                                <span wire:text="category_search"></span>
                                "
                            </flux:select.option.create>
                        </flux:select>

                        <flux:input wire:model="note" label="Note" type="text" placeholder="Optional" />

                        <flux:input
                            wire:model="amount"
                            label="Amount"
                            type="text"
                            mask:dynamic="$money($input)"
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
    </div>
</section>
