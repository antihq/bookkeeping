<?php

use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('All transactions')] class extends Component {
    public $page = 1;

    public string $date = '';

    public string $payee = '';

    public string $payee_search = '';

    public ?string $note = null;

    public string $amount = '';

    public string $type = 'expense';

    public ?int $category = null;

    public string $category_search = '';

    public ?int $account = null;

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
    }

    public function loadMore()
    {
        $this->page++;
    }

    public function addTransaction()
    {
        $this->authorize('create', Transaction::class);

        $this->validate([
            'date' => ['required', 'date'],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required'],
            'category' => ['nullable', 'exists:categories,id'],
            'account' => ['nullable', 'exists:accounts,id'],
        ]);

        $category = $this->category ? $this->team->categories()->findOrFail($this->category) : null;
        $account = $this->account ? $this->team->accounts()->findOrFail($this->account) : null;

        $this->team->transactions()->create([
            'date' => $this->date,
            'payee' => $this->payee,
            'amount' => $this->type === 'expense'
                    ? (int) -round((float) $this->amount * 100)
                    : (int) round((float) $this->amount * 100),
            'note' => $this->note,
            'created_by' => $this->user->id,
            'category_id' => $category?->id,
            'account_id' => $account?->id,
        ]);

        Flux::toast('Transaction added successfully.', variant: 'success');

        Flux::modals()->close();

        $this->reset(['payee', 'payee_search', 'note', 'amount', 'category', 'account', 'type']);

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

    public function deleteTransaction(int $id)
    {
        $transaction = $this->team->transactions()->findOrFail($id);

        $this->authorize('delete', $transaction);

        $transaction->delete();

        Flux::toast('Transaction deleted successfully.', variant: 'success');
    }

    public function updatedAmount($value)
    {
        $this->amount = str_replace(['$', ','], '', $value);
    }

    #[Computed]
    public function transactions()
    {
        return $this->team
            ->transactions()
            ->with(['account', 'category', 'creator'])
            ->orderBy('date', 'desc')
            ->forPage($this->page, 25)
            ->get();
    }

    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam;
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
    public function accounts()
    {
        return $this->team->accounts()->get();
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
    public function user(): User
    {
        return Auth::user();
    }
};
?>

<section class="mx-auto max-w-lg">
    @if ($this->transactions->isNotEmpty())
        <div class="flex items-center justify-between flex-wrap gap-x-6 gap-y-4">
            <flux:heading size="xl">All transactions</flux:heading>
            @can('create', Transaction::class)
                <flux:modal.trigger name="add-transaction">
                    <flux:button variant="primary">Add transaction</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12">
            <flux:heading>No transactions yet.</flux:heading>
            <flux:text>Log your transactions with a single tap.</flux:text>
            @can('create', Transaction::class)
                <div class="mt-6">
                    <flux:modal.trigger name="add-transaction">
                        <flux:button variant="primary">Add transaction</flux:button>
                    </flux:modal.trigger>
                </div>
            @endcan
        </div>
    @endif

    @island(name: 'transactions', lazy: true)
        @placeholder
            <div class="mt-8">
                <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
                <div class="divide-y divide-zinc-100 dark:divide-white/5 dark:text-white">
                    @foreach (range(1, rand(3, 8)) as $i)
                        <flux:skeleton.group animate="shimmer" class="py-4">
                            <flux:skeleton class="h-15" />
                        </flux:skeleton.group>
                    @endforeach
                </div>
            </div>
        @endplaceholder
        @if ($this->transactions->isNotEmpty())
            <div class="mt-8">
                <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
                <div class="divide-y divide-zinc-100 dark:divide-white/5 dark:text-white">

                    @foreach ($this->transactions as $transaction)
                        <livewire:transactions.item :$transaction wire:key="transaction-{{ $transaction->id }}" />
                    @endforeach
                </div>
                <div class="p-[.3125rem]">
                    <flux:button
                        wire:click="loadMore"
                        wire:island.append="transactions"
                        variant="subtle"
                        class="w-full"
                    >
                        Load more
                    </flux:button>
                </div>
            </div>
        @endif
    @endisland

    @can('create', Transaction::class)
        <flux:modal name="add-transaction" class="w-full sm:max-w-lg">
            <form wire:submit="addTransaction" class="space-y-6">
                <flux:heading size="lg">Add transaction</flux:heading>
                <flux:radio.group wire:model="type" label="Transaction type" variant="segmented" label:sr-only>
                    <flux:radio value="expense" icon="minus">Expense</flux:radio>
                    <flux:radio value="income" icon="plus">Income</flux:radio>
                </flux:radio.group>
                <flux:field>
                    <flux:input.group>
                        <flux:label sr-only>Amount</flux:label>
                        <flux:input.group.prefix>$</flux:input.group.prefix>
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
                @if ($this->accounts->isNotEmpty())
                    <flux:select wire:model="account" label="Account" placeholder="Optional: Select an account">
                        @foreach ($this->accounts as $acc)
                            <flux:select.option :value="$acc->id" :wire:key="'acc-'.$acc->id">
                                {{ $acc->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
                <div class="flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto">
                    <flux:modal.close>
                        <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">Add transaction</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</section>
