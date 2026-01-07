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
            'amount' => $this->type === 'expense'
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

@placeholder
    <flux:skeleton.group animate="shimmer" class="flex items-center justify-between gap-4 py-5.5 lg:py-6.5">
        <div class="flex w-1/2 items-center gap-4">
            <flux:skeleton.line class="w-22 max-lg:hidden" />
            <div class="w-full lg:w-1/2">
                <flux:skeleton.line class="w-4/5" />
                <flux:skeleton.line class="mt-1 w-1/4 lg:hidden" />
            </div>
        </div>
        <div class="flex w-1/2 items-center justify-between gap-4">
            <div class="flex w-full max-lg:justify-end lg:w-1/4">
                <div class="w-4/5">
                    <flux:skeleton.line />
                    <flux:skeleton.line class="mt-1 w-1/4 lg:hidden" />
                </div>
            </div>
            <flux:skeleton.line class="w-1/4" />
        </div>
    </flux:skeleton.group>
@endplaceholder

<div {{ $attributes->class('flex items-center justify-between gap-4 py-4') }}>
    <div class="flex w-1/2 items-center gap-4">
        <flux:text class="shrink-0 text-[13px] tabular-nums max-lg:hidden">{{ $transaction->display_date }}</flux:text>
        <div>
            <flux:text class="truncate font-medium" variant="strong">{{ $transaction->payee }}</flux:text>
            <flux:text :variant="$transaction->category ? 'strong' : null" class="mt-1 text-[13px] lg:hidden">
                {{ $transaction->category?->name ?? 'Uncategorized' }}
            </flux:text>
        </div>
    </div>

    <div class="flex w-1/2 items-center justify-between gap-4">
        <div class="flex min-w-fit justify-end max-lg:hidden">
            <div>
                <flux:text :variant="$transaction->category ? 'strong' : null" class="text-[13px]">
                    {{ $transaction->category?->name ?? 'Uncategorized' }}
                </flux:text>
                @if ($transaction->note)
                    <flux:text class="mt-1 text-[13px] max-lg:hidden">
                        {{ $transaction->note }}
                    </flux:text>
                @endif
            </div>
        </div>

        <div class="flex min-w-fit flex-1 items-center justify-end gap-4">
            <div class="text-right tabular-nums">
                @if ($transaction->amount === 0)
                    <flux:text variant="strong" class="font-medium">{{ $transaction->display_amount }}</flux:text>
                @elseif ($transaction->amount > 0)
                    <flux:text variant="strong" color="green" class="font-medium">
                        {{ $transaction->display_amount }}
                    </flux:text>
                @else
                    <flux:text variant="strong" color="red" class="font-medium">
                        {{ $transaction->display_amount }}
                    </flux:text>
                @endif
                <flux:text class="mt-1 shrink-0 text-[13px] tabular-nums lg:hidden">
                    {{ $transaction->display_date }}
                </flux:text>
            </div>

            <div>
                <flux:dropdown align="end">
                    <flux:button variant="subtle" size="sm" square icon="ellipsis-horizontal" />
                    <flux:menu>
                        <flux:modal.trigger name="edit-transaction-{{ $transaction->id }}">
                            <flux:menu.item icon="pencil-square" icon:variant="micro">Edit</flux:menu.item>
                        </flux:modal.trigger>
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
                                You're about to delete "{{ $transaction->payee }}". This action cannot be reversed.
                            </flux:text>
                        </div>
                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost" size="sm">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button
                                wire:click="$parent.deleteTransaction({{ $transaction->id }})"
                                variant="danger"
                                size="sm"
                            >
                                Delete transaction
                            </flux:button>
                        </div>
                    </div>
                </flux:modal>

                <flux:modal
                    name="edit-transaction-{{ $transaction->id }}"
                    :show="$errors->isNotEmpty()"
                    class="w-full pb-0 sm:max-w-lg"
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
                            class="sticky bottom-0 flex gap-2 border-t border-zinc-100 bg-white py-6 dark:border-white/5 dark:bg-zinc-900"
                        >
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost" size="sm">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" size="sm" type="submit">Save changes</flux:button>
                        </div>
                    </form>
                </flux:modal>
            </div>
        </div>
    </div>
</div>
