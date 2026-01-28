<?php

use App\Models\Account;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Account')] class extends Component
{
    public Account $account;

    public $page = 1;

    public bool $hasTransactions = false;

    public string $selectedPeriod = 'this_month';

    public string $amount = '';

    public string $category = '';

    public string $category_search = '';

    public string $date = '';

    public string $note = '';

    public string $payee = '';

    public string $payee_search = '';

    public string $type = 'expense';

    #[Computed]
    public function user(): User
    {
        return Auth::user();
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
            ->forPage($this->page, 25)
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return $this->team
            ->categories()
            ->when(
                $this->category_search,
                fn ($query) => $query->where('name', 'like', '%'.$this->category_search.'%'),
            )
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function payees()
    {
        return $this->team
            ->transactions()
            ->select('payee')
            ->when(
                $this->payee_search,
                fn ($query) => $query->where('payee', 'like', '%'.$this->payee_search.'%'),
            )
            ->distinct()
            ->get();
    }

    #[Computed]
    public function hasMorePages(): bool
    {
        return $this->account->transactions()->count() > ($this->page * 25);
    }

    #[Computed]
    public function accountBalance(): float
    {
        return $this->account->balance_in_dollars;
    }

    #[Computed]
    public function selectedMonthDate(): Carbon
    {
        return $this->selectedPeriod === 'this_month' ? now() : now()->subMonth();
    }

    public function mount()
    {
        $this->authorize('view', $this->account);

        $this->date = now()->format('Y-m-d');
        $this->hasTransactions = $this->account->transactions()->exists();
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

        $this->hasTransactions = true;

        Flux::toast('Transaction added successfully.', variant: 'success');

        Flux::modals()->close();

        $this->reset(['payee', 'payee_search', 'note', 'amount', 'category', 'page', 'type']);

        $this->renderIsland('transactions');
    }

    public function createCategory()
    {
        $this->validate([
            'category_search' => ['required', 'unique:categories,name,NULL,id,team_id,'.$this->team->id],
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

    #[Renderless]
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

        $this->renderIsland('pagination');
    }

    public function updatedAmount($value)
    {
        $this->amount = str_replace(['$', ','], '', $value);
    }
};
?>

<section class="mx-auto max-w-lg">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <flux:heading size="xl">{{ $account->name }}</flux:heading>
        <flux:dropdown align="end">
            <flux:button variant="subtle" square icon="ellipsis-horizontal" class="-my-0.5" />
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

    <div class="mt-8 flex items-end justify-between gap-4">
        <flux:heading level="2">Overview</flux:heading>
        <div>
            <flux:select wire:model.live="selectedPeriod">
                <flux:select.option value="this_month">This month</flux:select.option>
                <flux:select.option value="last_month">Last month</flux:select.option>
            </flux:select>
        </div>
    </div>
    <div class="mt-4 grid gap-8 sm:grid-cols-3">
        <div>
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Balance</div>
            <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                @if (($account->monthEndBalance($this->selectedMonthDate) / 100) >= 0)
                    {{ $account->currencySymbol }}{{ number_format(abs($account->monthEndBalance($this->selectedMonthDate) / 100), 2) }}
                @else
                    -{{ $account->currencySymbol }}{{ number_format(abs($account->monthEndBalance($this->selectedMonthDate) / 100), 2) }}
                @endif
            </div>
            <div class="mt-3 text-sm/6 sm:text-xs/6">
                @if (! is_null($account->balanceChangePercentage($this->selectedMonthDate)))
                    <flux:badge color="{{ $account->balanceChangeColor($this->selectedMonthDate) }}" size="sm">
                        {{ number_format($account->balanceChangePercentage($this->selectedMonthDate), 1) }}%
                    </flux:badge>
                    <flux:text size="sm" inline class="whitespace-nowrap">from previous month</flux:text>
                @endif
            </div>
        </div>
        <div>
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Expenses</div>
            <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                {{ $account->currencySymbol }}{{ number_format(abs($account->monthExpenses($this->selectedMonthDate) / 100), 2) }}
            </div>
            <div class="mt-3 text-sm/6 sm:text-xs/6">
                @if (! is_null($account->expensesChangePercentage($this->selectedMonthDate)))
                    <flux:badge color="{{ $account->expensesChangeColor($this->selectedMonthDate) }}" size="sm">
                        {{ number_format($account->expensesChangePercentage($this->selectedMonthDate), 1) }}%
                    </flux:badge>
                    <flux:text size="sm" inline class="whitespace-nowrap">from previous month</flux:text>
                @endif
            </div>
        </div>
        <div>
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Income</div>
            <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                {{ $account->currencySymbol }}{{ number_format(abs($account->monthIncome($this->selectedMonthDate) / 100), 2) }}
            </div>
            <div class="mt-3 text-sm/6 sm:text-xs/6">
                @if (! is_null($account->incomeChangePercentage($this->selectedMonthDate)))
                    <flux:badge color="{{ $account->incomeChangeColor($this->selectedMonthDate) }}" size="sm">
                        {{ number_format($account->incomeChangePercentage($this->selectedMonthDate), 1) }}%
                    </flux:badge>
                    <flux:text size="sm" inline class="whitespace-nowrap">from previous month</flux:text>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-14 flex items-end justify-between gap-4">
        <flux:heading>Transactions</flux:heading>
        @can('create', Transaction::class)
            <flux:modal.trigger name="add-transaction">
                <flux:button variant="primary">Add transaction</flux:button>
            </flux:modal.trigger>
        @endcan
    </div>

    <div id="transactions" class="mt-8" wire:show="hasTransactions" wire:cloak>
        <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
        <div class="divide-y divide-zinc-100 dark:divide-white/5 dark:text-white">
            @island(name: 'transactions', lazy: true)
                @placeholder
                    @foreach (range(1, rand(3, 8)) as $i)
                        <flux:skeleton.group animate="shimmer" class="py-4">
                            <flux:skeleton class="h-15" />
                        </flux:skeleton.group>
                    @endforeach
                @endplaceholder
                @foreach ($this->transactions as $transaction)
                    <livewire:transactions.item :$transaction wire:key="transaction-{{ $transaction->id }}" />
                @endforeach
            @endisland
        </div>

        @island(name: 'pagination')
            @if ($this->hasMorePages)
                <div id="pagination" class="p-[.3125rem]">
                    <flux:button
                        wire:click="loadMore"
                        wire:island.append="transactions"
                        variant="subtle"
                        class="w-full"
                    >
                        Load more
                    </flux:button>
                </div>
            @endif
        @endisland
    </div>

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
                <flux:autocomplete wire:model="payee" label="Payee" placeholder="Payee" label:sr-only >
                    @foreach ($this->payees as $payee)
                        <flux:autocomplete.item wire:key="payee-{{ $loop->index }}">
                            {{ $payee->payee }}
                        </flux:autocomplete.item>
                    @endforeach
                </flux:autocomplete>
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
                        Create "
                        <span wire:text="category_search"></span>
                        "
                    </flux:select.option.create>
                </flux:select>
                <flux:input wire:model="note" label="Note" type="text" placeholder="Note" label:sr-only />
                <flux:date-picker wire:model="date" label="Date" required />
                <div class="flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto">
                    <flux:modal.close>
                        <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">Add transaction</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan

    @can('delete', $account)
        <flux:modal name="delete" class="w-full max-w-xs sm:max-w-md">
            <div class="space-y-6 sm:space-y-4">
                <div>
                    <flux:heading>Delete account?</flux:heading>
                    <flux:text class="mt-2">
                        You're about to delete "{{ $account->name }}". All associated transactions will also be
                        deleted. This action cannot be reversed.
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
</section>
