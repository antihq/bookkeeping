<?php

use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Transactions')] class extends Component
{
    public string $date = '';

    public string $payee = '';

    public string $payee_search = '';

    public ?string $note = null;

    public string $amount = '';

    public string $type = 'expense';

    public ?int $category = null;

    public string $category_search = '';

    public ?int $account = null;

    public string $selectedPeriod = 'this_month';

    #[Computed]
    public function transactions()
    {
        return $this->team
            ->transactions()
            ->with('category')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();
    }

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
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

    public function updatedAmount($value)
    {
        $this->amount = str_replace(['$', ','], '', $value);
    }

    #[Computed]
    public function team(): Team
    {
        return Auth::user()->currentTeam;
    }

    #[Computed]
    public function totalBalance(): float
    {
        return $this->team->total_balance_in_dollars;
    }

    #[Computed]
    public function monthlyExpenses(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '<', 0)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function monthlyIncome(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '>', 0)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function lastMonthExpenses(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '<', 0)
            ->whereYear('date', now()->subMonth()->year)
            ->whereMonth('date', now()->subMonth()->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function lastMonthIncome(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '>', 0)
            ->whereYear('date', now()->subMonth()->year)
            ->whereMonth('date', now()->subMonth()->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function lastMonthBalance(): float
    {
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        return ($this->team->accounts()->sum('start_balance') +
                $this->team->transactions()
                    ->where('date', '<=', $lastMonthEnd)
                    ->sum('amount')) / 100;
    }

    #[Computed]
    public function selectedMonthDate()
    {
        return $this->selectedPeriod === 'this_month' ? now() : now()->subMonth();
    }

    #[Computed]
    public function previousMonthDate()
    {
        return $this->selectedMonthDate->copy()->subMonth();
    }

    #[Computed]
    public function selectedMonthExpenses(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '<', 0)
            ->whereYear('date', $this->selectedMonthDate->year)
            ->whereMonth('date', $this->selectedMonthDate->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function selectedMonthIncome(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '>', 0)
            ->whereYear('date', $this->selectedMonthDate->year)
            ->whereMonth('date', $this->selectedMonthDate->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function selectedMonthEndBalance(): float
    {
        $monthEnd = $this->selectedMonthDate->copy()->endOfMonth();

        return ($this->team->accounts()->sum('start_balance') +
                $this->team->transactions()
                    ->where('date', '<=', $monthEnd)
                    ->sum('amount')) / 100;
    }

    #[Computed]
    public function previousMonthExpenses(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '<', 0)
            ->whereYear('date', $this->previousMonthDate->year)
            ->whereMonth('date', $this->previousMonthDate->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function previousMonthIncome(): float
    {
        return $this->team
            ->transactions()
            ->where('amount', '>', 0)
            ->whereYear('date', $this->previousMonthDate->year)
            ->whereMonth('date', $this->previousMonthDate->month)
            ->sum('amount') / 100;
    }

    #[Computed]
    public function previousMonthEndBalance(): float
    {
        $monthEnd = $this->previousMonthDate->copy()->endOfMonth();

        return ($this->team->accounts()->sum('start_balance') +
                $this->team->transactions()
                    ->where('date', '<=', $monthEnd)
                    ->sum('amount')) / 100;
    }

    #[Computed]
    public function expensesChange(): float
    {
        return $this->selectedMonthExpenses - $this->previousMonthExpenses;
    }

    #[Computed]
    public function incomeChange(): float
    {
        return $this->selectedMonthIncome - $this->previousMonthIncome;
    }

    #[Computed]
    public function balanceChange(): float
    {
        return $this->selectedMonthEndBalance - $this->previousMonthEndBalance;
    }

    #[Computed]
    public function expensesChangePercentage(): ?float
    {
        if ($this->previousMonthExpenses === 0.0) {
            return null;
        }

        return (($this->selectedMonthExpenses - $this->previousMonthExpenses) / abs($this->previousMonthExpenses)) * 100;
    }

    #[Computed]
    public function incomeChangePercentage(): ?float
    {
        if ($this->previousMonthIncome === 0.0) {
            return null;
        }

        return (($this->selectedMonthIncome - $this->previousMonthIncome) / abs($this->previousMonthIncome)) * 100;
    }

    #[Computed]
    public function balanceChangePercentage(): ?float
    {
        if ($this->previousMonthEndBalance === 0.0) {
            return null;
        }

        return (($this->selectedMonthEndBalance - $this->previousMonthEndBalance) / abs($this->previousMonthEndBalance)) * 100;
    }

    #[Computed]
    public function expensesChangeFormatted(): string
    {
        $change = $this->expensesChange;

        return ($change >= 0 ? '+' : '-').'$'.number_format(abs($change), 2);
    }

    #[Computed]
    public function incomeChangeFormatted(): string
    {
        $change = $this->incomeChange;

        return ($change >= 0 ? '+' : '-').'$'.number_format(abs($change), 2);
    }

    #[Computed]
    public function balanceChangeFormatted(): string
    {
        $change = $this->balanceChange;

        return ($change >= 0 ? '+' : '-').'$'.number_format(abs($change), 2);
    }

    #[Computed]
    public function expensesChangeColor(): string
    {
        return $this->expensesChange <= 0 ? 'lime' : 'pink';
    }

    #[Computed]
    public function incomeChangeColor(): string
    {
        return $this->incomeChange >= 0 ? 'lime' : 'pink';
    }

    #[Computed]
    public function balanceChangeColor(): string
    {
        return $this->balanceChange >= 0 ? 'lime' : 'pink';
    }

    public function deleteTransaction(int $id)
    {
        $transaction = $this->team->transactions()->findOrFail($id);

        $this->authorize('delete', $transaction);

        $transaction->delete();

        Flux::toast('Transaction deleted successfully.', variant: 'success');
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
    @if ($this->transactions->count() === 0)
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
    @else
        <div class="flex flex-wrap justify-between gap-x-6 gap-y-4">
            <flux:heading size="xl">{{ $this->team->name }}</flux:heading>
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
                <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Overall balance</div>
                <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                    @if ($this->selectedMonthEndBalance >= 0)
                        ${{ number_format(abs($this->selectedMonthEndBalance), 2) }}
                    @else
                        -${{ number_format(abs($this->selectedMonthEndBalance), 2) }}
                    @endif
                </div>
                <div class="mt-3 text-sm/6 sm:text-xs/6">
                    @if (!is_null($this->balanceChangePercentage))
                        <flux:badge color="{{ $this->balanceChangeColor }}" size="sm">
                            {{ number_format($this->balanceChangePercentage, 1) }}%
                        </flux:badge>
                        <flux:text size="sm" inline class="whitespace-nowrap">from previous month</flux:text>
                    @endif
                </div>
            </div>
            <div>
                <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
                <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Expenses</div>
                <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                    ${{ number_format(abs($this->selectedMonthExpenses), 2) }}
                </div>
                <div class="mt-3 text-sm/6 sm:text-xs/6">
                    @if (!is_null($this->expensesChangePercentage))
                        <flux:badge color="{{ $this->expensesChangeColor }}" size="sm">
                            {{ number_format($this->expensesChangePercentage, 1) }}%
                        </flux:badge>
                        <flux:text size="sm" inline class="whitespace-nowrap">from previous month</flux:text>
                    @endif
                </div>
            </div>
            <div>
                <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
                <div class="mt-6 text-lg/6 font-medium sm:text-sm/6">Income</div>
                <div class="mt-3 text-3xl/8 font-semibold sm:text-2xl/8">
                    ${{ number_format(abs($this->selectedMonthIncome), 2) }}
                </div>
                <div class="mt-3 text-sm/6 sm:text-xs/6">
                    @if (!is_null($this->incomeChangePercentage))
                        <flux:badge color="{{ $this->incomeChangeColor }}" size="sm">
                            {{ number_format($this->incomeChangePercentage, 1) }}%
                        </flux:badge>
                        <flux:text size="sm" inline class="whitespace-nowrap">from previous month</flux:text>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-14 flex items-end justify-between gap-4">
            <flux:heading level="2">Recent transactions</flux:heading>
            @can('create', Transaction::class)
                <flux:modal.trigger name="add-transaction">
                    <flux:button variant="primary">Add transaction</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>

        <div class="mt-4">
            <hr role="presentation" class="w-full border-t border-zinc-950/10 dark:border-white/10" />
            <div class="divide-y divide-zinc-100 dark:divide-white/5 dark:text-white">
                @island(name: 'transactions', lazy: true)
                    @placeholder
                        @foreach (range(1, rand(3, 8)) as $i)
                            <flux:skeleton.group animate="shimmer" class="py-4">
                                <flux:skeleton class="h-15" />
                            </flux:skeleton.group>
                            @unless ($loop->last)
                                <flux:separator variant="subtle" />
                            @endunless
                        @endforeach
                    @endplaceholder

                    @foreach ($this->transactions as $transaction)
                        <livewire:transactions.item :$transaction wire:key="transaction-{{ $transaction->id }}" />
                    @endforeach
                @endisland
            </div>
        </div>
    @endif

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
                <flux:select wire:model="account" label="Account" placeholder="Optional: Select an account">
                    @foreach ($this->accounts as $acc)
                        <flux:select.option :value="$acc->id" :wire:key="'acc-'.$acc->id">
                            {{ $acc->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
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
