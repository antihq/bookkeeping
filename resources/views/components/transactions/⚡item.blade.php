<?php

use App\Models\Team;
use App\Models\Transaction;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
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
        $this->date = $this->transaction->date;
        $this->payee = $this->transaction->payee;
        $this->note = $this->transaction->note;
        $this->amount = (string) (abs($this->transaction->amount) / 100);
        $this->type = $this->transaction->amount < 0 ? 'expense' : 'income';
        $this->category = $this->transaction->category_id;
    }

    public function createCategory()
    {
        $this->validate([
            'category_search' => ['required', 'unique:categories,name,NULL,id,team_id,' . $this->transaction->team_id],
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
        ]);

        $category = $this->category ? $this->team->categories()->findOrFail($this->category) : null;

        $this->transaction->update([
            'date' => $this->date,
            'payee' => $this->payee,
            'note' => $this->note,
            'amount' =>
                $this->type === 'expense'
                    ? (int) -round((float) $this->amount * 100)
                    : (int) round((float) $this->amount * 100),
            'category_id' => $category?->id,
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
                fn ($query) => $query->where('name', 'like', '%' . $this->category_search . '%'),
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

<li {{ $attributes }}>
    <div
        class="relative flex justify-between gap-x-6 rounded-lg px-3.5 py-2.5 hover:bg-zinc-950/2.5 sm:px-3 sm:py-1.5 dark:hover:bg-white/2.5"
    >
        <div>
            <flux:heading class="truncate">
                <flux:modal.trigger name="edit-transaction-{{ $transaction->id }}">
                    <span class="absolute inset-x-0 -top-px bottom-0"></span>
                    {{ $transaction->payee }}
                </flux:modal.trigger>
            </flux:heading>
            <flux:text :variant="$transaction->category ? 'strong' : null" class="mt-1 text-sm/5 sm:text-[13px]/5">
                {{ $transaction->category?->name ?? 'Uncategorized' }}
            </flux:text>
        </div>

        <div class="flex shrink-0 items-center gap-x-4">
            <div class="text-right tabular-nums">
                @if ($transaction->amount === 0)
                    <flux:text variant="strong" class="font-medium whitespace-nowrap">
                        {{ $transaction->display_amount }}
                    </flux:text>
                @elseif ($transaction->amount > 0)
                    <flux:text variant="strong" color="green" class="font-medium whitespace-nowrap">
                        {{ $transaction->display_amount }}
                    </flux:text>
                @else
                    <flux:text variant="strong" color="red" class="font-medium whitespace-nowrap">
                        {{ $transaction->display_amount }}
                    </flux:text>
                @endif
                <flux:text class="mt-1 text-sm/5 sm:text-[13px]/5 tabular-nums">
                    {{ $transaction->display_date }}
                </flux:text>
            </div>

            <flux:dropdown align="end">
                <flux:button variant="subtle" square icon="ellipsis-horizontal" />
                <flux:menu>
                    <flux:modal.trigger name="edit-transaction-{{ $transaction->id }}">
                        <flux:menu.item icon="pencil-square" icon:variant="micro">Edit</flux:menu.item>
                    </flux:modal.trigger>
                    <flux:modal.trigger name="delete-transaction-{{ $transaction->id }}">
                        <flux:menu.item variant="danger" icon="trash" icon:variant="micro">Delete</flux:menu.item>
                    </flux:modal.trigger>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    <flux:modal
        name="edit-transaction-{{ $transaction->id }}"
        class="w-full sm:max-w-lg"
    >
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
                        {{ $transaction->account->currencySymbol }}
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
    </flux:modal>

    <flux:modal name="delete-transaction-{{ $transaction->id }}" class="w-full max-w-xs sm:max-w-md">
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
                <flux:button
                    wire:click="$parent.deleteTransaction({{ $transaction->id }})"
                    variant="primary"
                >
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
