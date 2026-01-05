<?php

use App\Models\Category;
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

    public ?int $category_id = null;

    public string $category_search = '';

    public function openEditModal()
    {
        $this->date = $this->transaction->date;
        $this->payee = $this->transaction->payee;
        $this->note = $this->transaction->note;
        $this->amount = (string) ($this->transaction->amount / 100);
        $this->category_id = $this->transaction->category_id;
    }

    public function editTransaction()
    {
        $this->authorize('update', $this->transaction);

        $this->validate([
            'date' => ['required', 'date'],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $this->transaction->update([
            'date' => $this->date,
            'payee' => $this->payee,
            'note' => $this->note,
            'amount' => (int) round((float) $this->amount * 100),
            'category_id' => $this->category_id,
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
        return Category::query()
            ->where('team_id', $this->transaction->team_id)
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
            'category_search' => ['required', 'unique:categories,name,NULL,id,team_id,' . $this->transaction->team_id],
        ]);

        $category = Category::create([
            'name' => $this->category_search,
            'team_id' => $this->transaction->team_id,
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
            <flux:text class="truncate" variant="strong">{{ $transaction->payee }}</flux:text>
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
                    <flux:badge color="zinc" size="sm">{{ $transaction->display_amount }}</flux:badge>
                @elseif ($transaction->amount > 0)
                    <flux:badge color="green" size="sm">{{ $transaction->display_amount }}</flux:badge>
                @else
                    <flux:badge color="red" size="sm">{{ $transaction->display_amount }}</flux:badge>
                @endif
                <flux:text class="mt-1 shrink-0 text-[13px] tabular-nums lg:hidden">
                    {{ $transaction->display_date }}
                </flux:text>
            </div>

            <div>
                <flux:dropdown align="end">
                    <flux:button variant="subtle" size="sm" square icon="ellipsis-horizontal" />
                    <flux:menu>
                        <flux:modal.trigger name="edit-transaction-{{ $transaction->id }}" wire:click="openEditModal">
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
                    focusable
                    class="w-full sm:max-w-lg"
                >
                    <form wire:submit="editTransaction" class="space-y-6">
                        <div>
                            <flux:heading size="lg">Edit transaction</flux:heading>
                            <flux:text class="mt-2">Make changes to this transaction.</flux:text>
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
                            <flux:button variant="primary" size="sm" type="submit">Save changes</flux:button>
                        </div>
                    </form>
                </flux:modal>
            </div>
        </div>
    </div>
</div>
