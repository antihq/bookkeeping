<?php

use App\Models\Account;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit account')] class extends Component
{
    public Account $account;

    public string $type = 'checking';

    public string $name = '';

    public string $start_balance = '';

    public function mount()
    {
        $this->authorize('update', $this->account);

        $this->account = $this->account;
        $this->type = $this->account->type;
        $this->name = $this->account->name;
        $this->start_balance = (string) ($this->account->start_balance / 100);
    }

    public function updatedStartBalance($value)
    {
        $this->start_balance = str_replace(['$', ','], '', $value);
    }

    public function update()
    {
        $this->authorize('update', $this->account);

        $this->validate([
            'type' => ['required', 'in:checking,savings,credit card,cash,other'],
            'name' => ['required', 'string', 'max:255'],
            'start_balance' => ['required', 'numeric'],
        ]);

        $this->account->update([
            'type' => $this->type,
            'name' => $this->name,
            'start_balance' => (int) round($this->start_balance * 100),
        ]);

        Flux::toast('Account updated successfully.', variant: 'success');

        return $this->redirectRoute('accounts.show', $this->account);
    }
};
?>

<section class="mx-auto max-w-lg space-y-8">
    <flux:heading size="xl">Edit account</flux:heading>

    <div class="space-y-14">
        <div class="space-y-8">
            <header class="space-y-1">
                <flux:heading size="lg">Account details</flux:heading>
                <flux:text>Update the account information.</flux:text>
            </header>

            <form wire:submit="update" class="w-full max-w-lg space-y-8">
                <flux:radio.group label="Account type" wire:model="type" class="mt-2">
                    <flux:radio label="Checking" value="checking" />
                    <flux:radio label="Savings" value="savings" />
                    <flux:radio label="Credit Card" value="credit card" />
                    <flux:radio label="Cash" value="cash" />
                    <flux:radio label="Other" value="other" />
                </flux:radio.group>

                <flux:input wire:model="name" label="Account name" type="text" required autofocus />

                <flux:input
                    wire:model="start_balance"
                    label="Start balance"
                    type="text"
                    mask:dynamic="$money($input)"
                    placeholder="0.00"
                    required
                />

                <div class="flex justify-end gap-4">
                    <flux:button href="{{ route('accounts.show', $account) }}" variant="ghost" wire:navigate>Cancel</flux:button>
                    <flux:button variant="primary" type="submit">Save changes</flux:button>
                </div>
            </form>
        </div>
    </div>
</section>
