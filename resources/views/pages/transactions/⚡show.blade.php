<?php

use App\Models\Team;
use App\Models\Transaction;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Transaction')] class extends Component {
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
            'amount' =>
                $this->type === 'expense'
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
            'category_search' => ['required', 'unique:categories,name,NULL,id,team_id,' . $this->transaction->team_id],
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

<section class="mx-auto max-w-lg space-y-8">
    <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <flux:heading size="xl">Transaction</flux:heading>

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

        <div
            class="relative h-full w-full rounded-xl bg-white shadow-[0px_0px_0px_1px_rgba(9,9,11,0.07),0px_2px_2px_0px_rgba(9,9,11,0.05)] dark:bg-zinc-900 dark:shadow-[0px_0px_0px_1px_rgba(255,255,255,0.1)] dark:before:pointer-events-none dark:before:absolute dark:before:-inset-px dark:before:rounded-xl dark:before:shadow-[0px_2px_8px_0px_rgba(0,0,0,0.20),0px_1px_0px_0px_rgba(255,255,255,0.06)_inset] forced-colors:outline"
        >
            <div class="overflow-hidden p-[.3125rem]">
                <div class="flex items-center justify-between gap-3 px-3.5 py-4 sm:px-3 sm:py-3">
                    <div class="flex-1 overflow-hidden">
                        <flux:heading class="truncate">{{ $transaction->payee }}</flux:heading>
                        <flux:text class="mt-1 text-sm/5 sm:text-[13px]/5">
                            {{ $transaction->account->name }}
                        </flux:text>
                    </div>
                    <div class="text-right tabular-nums">
                        @if ($transaction->amount === 0)
                            <flux:text variant="strong" class="font-medium whitespace-nowrap text-xl">
                                {{ $transaction->display_amount }}
                            </flux:text>
                        @elseif ($transaction->amount > 0)
                            <flux:text variant="strong" color="green" class="font-medium whitespace-nowrap text-xl">
                                {{ $transaction->display_amount }}
                            </flux:text>
                        @else
                            <flux:text variant="strong" color="red" class="font-medium whitespace-nowrap text-xl">
                                {{ $transaction->display_amount }}
                            </flux:text>
                        @endif
                    </div>
                </div>

                <div class="mx-3.5 my-1 h-px sm:mx-3">
                    <flux:separator variant="subtle" />
                </div>

                <div class="space-y-3 px-3.5 py-4 sm:px-3 sm:py-3">
                    <div class="flex items-start justify-between gap-4">
                        <flux:text class="text-sm/5 sm:text-[13px]/5">Date</flux:text>
                        <flux:text variant="strong" class="text-sm/5 sm:text-[13px]/5">{{ $transaction->display_date }}</flux:text>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <flux:text class="text-sm/5 sm:text-[13px]/5">Category</flux:text>
                        <flux:text :variant="$transaction->category ? 'strong' : null" class="text-sm/5 sm:text-[13px]/5">
                            {{ $transaction->category?->name ?? 'Uncategorized' }}
                        </flux:text>
                    </div>

                    @if ($transaction->note)
                        <div class="mx-3.5 my-1 h-px sm:mx-3">
                            <flux:separator variant="subtle" />
                        </div>

                        <div class="space-y-1">
                            <flux:text class="text-sm/5 sm:text-[13px]/5">Note</flux:text>
                            <flux:text class="text-sm/5 sm:text-[13px]/5">{{ $transaction->note }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>
        </div>
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
