<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $type = 'checking';

    public string $name = '';

    public string $currency = 'usd';

    public float $start_balance = 0.0;

    public function mount()
    {
        $this->authorize('create', Account::class);
    }

    public function create()
    {
        $this->authorize('create', Account::class);

        $this->validate([
            'type' => ['required', 'in:checking,savings,credit card,cash,other'],
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'start_balance' => ['required', 'numeric', 'min:0'],
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
    public function user(): User
    {
        return Auth::user();
    }

    #[Computed]
    public function team()
    {
        return $this->user->currentTeam;
    }

    #[Computed]
    public function currencies()
    {
        return Currency::all()->sortBy('currency');
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="lg">Create account</flux:heading>
        <flux:button href="{{ route('accounts.index') }}" size="sm" inset="top bottom" wire:navigate>
            Cancel
        </flux:button>
    </div>

    <div class="space-y-14">
        <div class="space-y-8">
            <header class="space-y-1">
                <flux:heading>Account details</flux:heading>
                <flux:text>Create a new account to track your finances.</flux:text>
            </header>

            <form wire:submit="create" class="w-full max-w-lg space-y-8">
                <flux:radio.group label="Account type" wire:model="type" class="mt-2">
                    <flux:radio label="Checking" value="checking" />
                    <flux:radio label="Savings" value="savings" />
                    <flux:radio label="Credit Card" value="credit card" />
                    <flux:radio label="Cash" value="cash" />
                    <flux:radio label="Other" value="other" />
                </flux:radio.group>

                <flux:input wire:model="name" label="Account name" type="text" size="sm" required autofocus />

                <flux:select wire:model="currency" label="Currency" size="sm" required>
                    @foreach ($this->currencies as $currency)
                        <option value="{{ $currency->iso }}">
                            {{ $currency->currency }} ({{ $currency->iso }})
                        </option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="start_balance"
                    label="Start balance"
                    type="number"
                    step="0.01"
                    min="0"
                    size="sm"
                    required
                />

                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full" size="sm">
                            Create account
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
