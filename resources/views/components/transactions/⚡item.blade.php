<?php

use App\Models\Team;
use App\Models\Transaction;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Transaction $transaction;

    public string $date = '';

    public string $payee = '';

    public ?string $note = null;

    public string $amount = '';

    public int $account = 0;

    public string $type = 'expense';

    public ?int $category = null;

    public string $category_search = '';

    public function mount()
    {
        $this->date = $this->transaction->date;
        $this->payee = $this->transaction->payee;
        $this->note = $this->transaction->note;
        $this->amount = (string) (abs($this->transaction->amount) / 100);
        $this->type = $this->transaction->amount < 0 ? 'expense' : 'income';
        $this->category = $this->transaction->category_id;
        $this->account = $this->transaction->account_id ?? 0;
    }

    public function createCategory()
    {
        $this->validate([
            'category_search' => ['required', 'unique:categories,name,NULL,id,team_id,'.$this->transaction->team_id],
        ]);

        $category = $this->team->categories()->create([
            'name' => $this->pull('category_search'),
        ]);

        $this->category = $category->id;
    }

    public function edit()
    {
        $this->authorize('update', $this->transaction);

        $this->validate([
            'date' => ['required', 'date'],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required'],
            'category' => ['nullable', 'exists:categories,id'],
            'account' => ['required', 'exists:accounts,id'],
        ]);

        $account = $this->accounts->findOrFail($this->account);
        $category = $this->category ? $this->team->categories()->findOrFail($this->category) : null;

        $this->transaction->update([
            'date' => $this->date,
            'payee' => $this->payee,
            'note' => $this->note,
            'amount' => $this->type === 'expense'
                    ? (int) -round((float) $this->amount * 100)
                    : (int) round((float) $this->amount * 100),
            'category_id' => $category?->id,
            'account_id' => $account?->id,
        ]);

        Flux::toast('Transaction updated successfully.', variant: 'success');

        Flux::modals()->close();
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
                fn ($query) => $query->where('name', 'like', '%'.$this->category_search.'%'),
            )
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function accounts()
    {
        return $this->team->accounts;
    }

    #[Computed]
    public function team(): Team
    {
        return $this->transaction->team;
    }
};
?>

<div>
    <div {{ $attributes }} class="flex items-center justify-between gap-4 py-4">
        <div class="overflow-hidden">
            <flux:heading class="leading-6!">
                <flux:modal.trigger name="view-transaction-{{ $transaction->id }}">
                    <button type="button" class="max-w-full truncate text-left">{{ $transaction->payee }}</button>
                </flux:modal.trigger>
            </flux:heading>
            <flux:text class="leading-6!" size="sm">
                {{ $transaction->category?->name ?? 'Uncategorized' }}
            </flux:text>
        </div>

        <div class="flex shrink-0 items-center gap-4">
            <div class="text-right tabular-nums">
                <div class="tabular-nums">
                    @if ($transaction->amount === 0)
                        <flux:text variant="strong">
                            {{ $transaction->display_amount }}
                        </flux:text>
                    @elseif ($transaction->amount > 0)
                        <flux:badge color="lime" size="sm">
                            {{ $transaction->display_amount }}
                        </flux:badge>
                    @else
                        <flux:badge color="pink" size="sm">
                            {{ $transaction->display_amount }}
                        </flux:badge>
                    @endif
                </div>
                <flux:text class="mt-1 leading-6!" size="sm">
                    {{ $transaction->display_date }}
                </flux:text>
            </div>

            <flux:dropdown align="end">
                <flux:button variant="subtle" square icon="ellipsis-horizontal" class="-mr-2" />
                <flux:menu>
                    <flux:modal.trigger name="view-transaction-{{ $transaction->id }}">
                        <flux:menu.item>View</flux:menu.item>
                    </flux:modal.trigger>
                    <flux:modal.trigger name="edit-transaction-{{ $transaction->id }}">
                        <flux:menu.item>Edit</flux:menu.item>
                    </flux:modal.trigger>
                    <flux:modal.trigger name="delete-transaction-{{ $transaction->id }}">
                        <flux:menu.item variant="danger">Delete</flux:menu.item>
                    </flux:modal.trigger>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
    <flux:modal name="edit-transaction-{{ $transaction->id }}" class="w-full sm:max-w-lg" @close="$refresh">
        @island(lazy: true)
            @placeholder
                <div class="space-y-6">
                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer" class="flex gap-4">
                        <flux:skeleton.line class="flex-1" />
                        <flux:skeleton.line class="flex-1" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>
                </div>
            @endplaceholder

            <form wire:submit="edit" class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit transaction</flux:heading>
                    <flux:text class="mt-2">Make changes to this transaction.</flux:text>
                </div>

                <flux:radio.group wire:model="type" label="Transaction type" variant="segmented" label:sr-only>
                    <flux:radio value="expense" icon="minus">Expense</flux:radio>
                    <flux:radio value="income" icon="plus">Income</flux:radio>
                </flux:radio.group>

                <flux:field>
                    <flux:input.group>
                        <flux:label sr-only>Amount</flux:label>
                        <flux:input.group.prefix>
                            {{ $transaction->account?->currencySymbol ?? '$' }}
                        </flux:input.group.prefix>
                        <flux:input
                            wire:model="amount"
                            type="text"
                            mask:dynamic="$money($input)"
                            inputmode="decimal"
                            placeholder="0.00"
                            required
                        />
                    </flux:input.group>
                    <flux:error name="amount" />
                </flux:field>

                <flux:input wire:model="payee" label="Payee" type="text" placeholder="Payee" label:sr-only required />

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

                <flux:select wire:model="account" label="Account" required label:sr-only>
                    @foreach ($this->accounts as $acc)
                        <flux:select.option :value="$acc->id" :wire:key="'acc-'.$acc->id">
                            {{ $acc->name }} ({{ $acc->display_type }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="note" label="Note" type="text" placeholder="Note" label:sr-only />

                <flux:date-picker wire:model="date" label="Date" required />

                <div class="flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto">
                    <flux:modal.close>
                        <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">Save changes</flux:button>
                </div>
            </form>
        @endisland
    </flux:modal>

    <flux:modal name="view-transaction-{{ $transaction->id }}" class="w-full sm:max-w-lg">
        @island(lazy: true)
            @placeholder
                <div class="space-y-6">
                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer" class="flex gap-4">
                        <flux:skeleton.line class="flex-1" />
                        <flux:skeleton.line class="flex-1" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>

                    <flux:skeleton.group animate="shimmer">
                        <flux:skeleton.line size="lg" />
                    </flux:skeleton.group>
                </div>
            @endplaceholder

            <div class="space-y-8">
                <div>
                    <div class="flex items-center gap-4">
                        <flux:heading size="lg">{{ $transaction->payee }}</flux:heading>
                        @if ($transaction->amount >= 0)
                            <flux:badge color="lime" size="sm">Income</flux:badge>
                        @else
                            <flux:badge color="pink" size="sm">Expense</flux:badge>
                        @endif
                    </div>
                    <div class="mt-2.5 flex flex-wrap gap-x-10 gap-y-4 py-1.5">
                        <span class="flex items-center gap-3 text-base/6 text-zinc-950 sm:text-sm/6 dark:text-white">
                            <flux:icon name="banknotes" variant="micro" class="size-4 shrink-0 fill-zinc-400 dark:fill-zinc-500" />
                            <span>{{ $transaction->display_amount }}</span>
                        </span>
                        @if ($transaction->account)
                            <span class="flex items-center gap-3 text-base/6 text-zinc-950 sm:text-sm/6 dark:text-white">
                                <flux:icon name="credit-card" variant="micro" class="size-4 shrink-0 fill-zinc-400 dark:fill-zinc-500" />
                                <a href="{{ route('accounts.show', $transaction->account) }}" wire:navigate>
                                    {{ $transaction->account->name }}
                                </a>
                            </span>
                        @endif
                        <span class="flex items-center gap-3 text-base/6 text-zinc-950 sm:text-sm/6 dark:text-white">
                            <flux:icon name="calendar" variant="micro" class="size-4 shrink-0 fill-zinc-400 dark:fill-zinc-500" />
                            <span>{{ $transaction->display_date }}</span>
                        </span>
                    </div>
                </div>

                <div>
                    <flux:heading level="2">Summary</flux:heading>
                    <flux:separator variant="subtle" class="mt-4" />
                    <x-description.list>
                        @if ($transaction->account)
                            <x-description.term>Account</x-description.term>
                            <x-description.details>
                                <a href="{{ route('accounts.show', $transaction->account) }}" wire:navigate>
                                    {{ $transaction->account->name }}
                                </a>
                            </x-description.details>
                        @endif

                        <x-description.term>Date</x-description.term>
                        <x-description.details>{{ $transaction->display_date }}</x-description.details>

                        <x-description.term>Category</x-description.term>
                        <x-description.details>{{ $transaction->category?->name ?? 'Uncategorized' }}</x-description.details>

                        @if ($transaction->note)
                            <x-description.term>Note</x-description.term>
                            <x-description.details>{{ $transaction->note }}</x-description.details>
                        @endif

                        <x-description.term>Amount</x-description.term>
                        <x-description.details>{{ $transaction->display_amount }}</x-description.details>
                    </x-description.list>
                </div>
            </div>
        @endisland
    </flux:modal>

    <flux:modal name="delete-transaction-{{ $transaction->id }}" class="w-full sm:max-w-md">
        <div class="space-y-6 sm:space-y-4">
            <div>
                <flux:heading>Delete transaction?</flux:heading>
                <flux:text class="mt-2">
                    You're about to delete "{{ $transaction->payee }}". This action cannot be reversed.
                </flux:text>
            </div>
            <div class="flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto">
                <flux:modal.close>
                    <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                </flux:modal.close>
                <flux:button wire:click="$parent.deleteTransaction({{ $transaction->id }})" variant="primary">
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
