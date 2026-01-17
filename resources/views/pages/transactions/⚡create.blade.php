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

new #[Title('Add transaction')] class extends Component {
    public int $account = 0;

    public string $type = 'expense';

    public string $amount = '';

    public string $payee = '';

    public ?string $note = null;

    public ?int $category = null;

    public string $category_search = '';

    public string $date = '';

    public function mount()
    {
        $this->authorize('create', Transaction::class);

        $this->date = now()->format('Y-m-d');

        $this->account = $this->accounts()->first()?->id ?? 0;
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

    public function create()
    {
        $this->authorize('create', Transaction::class);

        $this->validate([
            'account' => ['required', 'exists:accounts,id'],
            'date' => ['required', 'date'],
            'payee' => ['required', 'string', 'max:255'],
            'amount' => ['required'],
            'category' => ['nullable', 'exists:categories,id'],
        ]);

        $account = $this->accounts->findOrFail($this->account);
        $category = $this->category ? $this->team->categories()->findOrFail($this->category) : null;

        $account->addTransaction(
            [
                'date' => $this->date,
                'payee' => $this->payee,
                'amount' =>
                    $this->type === 'expense'
                        ? (int) -round((float) $this->amount * 100)
                        : (int) round((float) $this->amount * 100),
                'note' => $this->note,
            ],
            $this->user,
            $category,
        );

        Flux::toast('Transaction created successfully.', variant: 'success');

        return $this->redirectRoute('transactions.index', navigate: true);
    }

    public function updatedAmount($value)
    {
        $this->amount = str_replace(['$', ','], '', $value);
    }

    #[Computed]
    public function accounts()
    {
        return $this->team->accounts;
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
        return $this->user->currentTeam;
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }
};
?>

<section class="mx-auto max-w-lg space-y-8">
    <flux:heading size="xl">Add transaction</flux:heading>

    <form wire:submit="create" class="w-full max-w-lg space-y-8">
        <flux:radio.group wire:model="type" label="Transaction type" variant="segmented" label:sr-only>
            <flux:radio value="expense" icon="minus">Expense</flux:radio>
            <flux:radio value="income" icon="plus">Income</flux:radio>
        </flux:radio.group>

        <flux:field>
            <flux:input.group>
                <flux:label sr-only>Amount</flux:label>
                <flux:input.group.prefix>
                    $
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
            @foreach ($this->categories as $cat)
                <flux:select.option :value="$cat->id" :wire:key="'cat-'.$cat->id">
                    {{ $cat->name }}
                </flux:select.option>
            @endforeach

            <flux:select.option.create wire:click="createCategory" min-length="1">
                Create "
                <span wire:text="category_search"></span>
                "
            </flux:select.option.create>
        </flux:select>

        <flux:input wire:model="note" label="Note" type="text" placeholder="Note (optional)" label:sr-only />

        <flux:date-picker wire:model="date" label="Date" required label:sr-only />

        <flux:select wire:model="account" label="Account" required label:sr-only>
            @foreach ($this->accounts as $acc)
                <flux:select.option :value="$acc->id" :wire:key="'acc-'.$acc->id">
                    {{ $acc->name }} ({{ $acc->display_type }})
                </flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-4">
            <flux:button href="{{ route('transactions.index') }}" variant="ghost" wire:navigate>Cancel</flux:button>
            <flux:button variant="primary" type="submit">Add transaction</flux:button>
        </div>
    </form>
</section>
