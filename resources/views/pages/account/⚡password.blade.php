<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Password')] class extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', PasswordRule::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast('Password has been updated.', variant: 'success');
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <flux:heading size="lg">Password</flux:heading>

    <div class="space-y-14">
        <div class="space-y-8">
            <header>
                <flux:heading>Update password</flux:heading>
                <flux:text class="mt-1">Ensure your account is using a long, random password to stay secure.</flux:text>
            </header>

            <form wire:submit="updatePassword" class="w-full max-w-lg space-y-8">
                <flux:input
                    wire:model="current_password"
                    :label="'Current password'"
                    type="password"
                    size="sm"
                    required
                    autocomplete="current-password"
                />
                <flux:input
                    wire:model="password"
                    :label="'New password'"
                    type="password"
                    size="sm"
                    required
                    autocomplete="new-password"
                />
                <flux:input
                    wire:model="password_confirmation"
                    :label="'Confirm Password'"
                    type="password"
                    size="sm"
                    required
                    autocomplete="new-password"
                />
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full" size="sm">Save</flux:button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
