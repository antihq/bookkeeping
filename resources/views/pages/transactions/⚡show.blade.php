<?php

use App\Models\Team;
use App\Models\Transaction;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Transaction')] class extends Component
{
    public Transaction $transaction;

    public string $date = '';

    public string $payee = '';

    public ?string $note = null;

    public string $amount = '';

    public string $type = 'expense';

    public ?int $category = null;

    public string $category_search = '';

    public function mount()
    {
        $this->authorize('view', $this->transaction);

        $this->date = $this->transaction->date;
        $this->payee = $this->transaction->payee;
        $this->note = $this->transaction->note;
        $this->amount = (string) (abs($this->transaction->amount) / 100);
        $this->type = $this->transaction->amount < 0 ? 'expense' : 'income';
        $this->category = $this->transaction->category_id;
    }

    public function edit()
    {
        $this->authorize('update', $this->transaction);

        $this->validate([
            'date' => ['required', 'date'],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required'],
            'category' => ['nullable', 'exists:categories,id'],
        ]);

        $category = $this->category ? $this->team->categories()->findOrFail($this->category) : null;

        $this->transaction->update([
            'date' => $this->date,
            'payee' => $this->payee,
            'note' => $this->note,
            'amount' => $this->type === 'expense'
                    ? (int) -round((float) $this->amount * 100)
                    : (int) round((float) $this->amount * 100),
            'category_id' => $category?->id,
        ]);

        Flux::toast('Transaction updated successfully.', variant: 'success');

        Flux::modals()->close();
    }

    public function delete()
    {
        $this->authorize('delete', $this->transaction);

        $this->transaction->delete();

        Flux::toast('Transaction deleted successfully.', variant: 'success');

        return $this->redirectRoute('transactions.index');
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
    public function team(): Team
    {
        return $this->transaction->account->team;
    }
};
?>

<section class="mx-auto max-w-xl space-y-8">
    <div class="mt-4 lg:mt-8">
        <div class="flex items-center gap-4">
            <flux:heading size="xl">{{ $transaction->payee }}</flux:heading>
            @if ($transaction->amount >= 0)
                <flux:badge color="lime" size="sm">Income</flux:badge>
            @else
                <flux:badge color="pink" size="sm">Expense</flux:badge>
            @endif
        </div>
        <div class="isolate mt-2.5 flex flex-wrap justify-between gap-x-6 gap-y-4">
            <div class="flex flex-wrap gap-x-10 gap-y-4 py-1.5">
                <span class="flex items-center gap-3 text-base/6 text-zinc-950 sm:text-sm/6 dark:text-white">
                    <flux:icon name="banknotes" variant="micro" class="size-4 shrink-0 fill-zinc-400 dark:fill-zinc-500" />
                    <span>{{ $transaction->display_amount }}</span>
                </span>
                <span class="flex items-center gap-3 text-base/6 text-zinc-950 sm:text-sm/6 dark:text-white">
                    <flux:icon name="credit-card" variant="micro" class="size-4 shrink-0 fill-zinc-400 dark:fill-zinc-500" />
                    <a href="{{ route('accounts.show', $transaction->account) }}" wire:navigate>
                        {{ $transaction->account->name }}
                    </a>
                </span>
                <span class="flex items-center gap-3 text-base/6 text-zinc-950 sm:text-sm/6 dark:text-white">
                    <flux:icon name="calendar" variant="micro" class="size-4 shrink-0 fill-zinc-400 dark:fill-zinc-500" />
                    <span>{{ $transaction->display_date }}</span>
                </span>
            </div>
            <div class="flex gap-4">
                <flux:dropdown align="end">
                    <flux:button variant="subtle" square icon="ellipsis-horizontal" class="-my-0.5" />
                    <flux:menu>
                        @can('update', $transaction)
                            <flux:modal.trigger name="edit-transaction">
                                <flux:menu.item icon="pencil-square" icon:variant="micro">Edit</flux:menu.item>
                            </flux:modal.trigger>
                        @endcan

                        @can('delete', $transaction)
                            <flux:modal.trigger name="delete-transaction">
                                <flux:menu.item variant="danger" icon="trash" icon:variant="micro">Delete</flux:menu.item>
                            </flux:modal.trigger>
                        @endcan
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </div>

    <div class="mt-12">
        <flux:heading level="2">Summary</flux:heading>
        <flux:separator variant="subtle" class="mt-4" />
        <x-description.list>
            <x-description.term>Account</x-description.term>
            <x-description.details>
                <a href="{{ route('accounts.show', $transaction->account) }}" wire:navigate>
                    {{ $transaction->account->name }}
                </a>
            </x-description.details>

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

    @can('update', $transaction)
        <flux:modal name="edit-transaction" class="w-full sm:max-w-lg">
            @island
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
                            <flux:input.group.prefix>{{ $transaction->account->currencySymbol }}</flux:input.group.prefix>
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
                            Create "
                            <span wire:text="category_search"></span>
                            "
                        </flux:select.option.create>
                    </flux:select>

                    <flux:input wire:model="note" label="Note" type="text" placeholder="Note" label:sr-only />

                    <flux:date-picker wire:model="date" label="Date" required />

                    <div
                        class="flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto"
                    >
                        <flux:modal.close>
                            <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button variant="primary" type="submit">Save changes</flux:button>
                    </div>
                </form>
            @endisland
        </flux:modal>
    @endcan

    @can('delete', $transaction)
        <flux:modal name="delete-transaction" class="w-full max-w-xs sm:max-w-md">
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
                    <flux:button wire:click="delete" variant="primary">Delete</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan
</section>
