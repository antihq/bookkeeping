<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add account')] class extends Component {
    public string $type = 'checking';

    public string $name = '';

    public string $currency = 'usd';

    public string $start_balance = '';

    public function mount()
    {
        $this->authorize('create', Account::class);
    }

    public function updatedStartBalance($value)
    {
        $this->start_balance = str_replace(['$', ','], '', $value);
    }

    public function create()
    {
        $this->authorize('create', Account::class);

        $this->validate([
            'type' => ['required', 'in:checking,savings,credit card,cash,other'],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'start_balance' => ['required', 'numeric'],
        ]);

        Account::create([
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
            'type' => $this->type,
            'name' => $this->name,
            'currency' => $this->currency,
            'start_balance' => (int) round($this->start_balance * 100),
        ]);

        Flux::toast('Account created successfully.', variant: 'success');

        return $this->redirectRoute('accounts.index', navigate: true);
    }

    #[Computed]
    public function currencies()
    {
        return Currency::all()->sortBy('currency');
    }

    #[Computed]
    public function team()
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

<section class="mx-auto max-w-6xl space-y-8">
    <flux:heading size="xl">Add account</flux:heading>

    <div class="space-y-14">
        <div class="space-y-8">
            <header class="space-y-1">
                <flux:heading size="lg">Account details</flux:heading>
                <flux:text>Add a new account to track your finances.</flux:text>
            </header>

            <form wire:submit="create" class="w-full max-w-lg space-y-8">
                <flux:radio.group label="Account type" wire:model="type" class="mt-2">
                    <flux:radio label="Checking" value="checking" />
                    <flux:radio label="Savings" value="savings" />
                    <flux:radio label="Credit Card" value="credit card" />
                    <flux:radio label="Cash" value="cash" />
                    <flux:radio label="Other" value="other" />
                </flux:radio.group>

                <flux:input wire:model="name" label="Account name" type="text" required autofocus />

                <flux:select wire:model="currency" label="Currency" required>
                    @foreach ($this->currencies as $currency)
                        <option value="{{ $currency->iso }}">
                            {{ $currency->currency }} ({{ $currency->iso }})
                        </option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="start_balance"
                    label="Start balance"
                    type="text"
                    mask:dynamic="$money($input)"
                    placeholder="0.00"
                    required
                />

                <div class="flex justify-end gap-4">
                    <flux:button href="{{ route('accounts.index') }}" variant="ghost" wire:navigate>Cancel</flux:button>
                    <flux:button variant="primary" type="submit">Add account</flux:button>
                </div>
            </form>
        </div>
    </div>
</section>
