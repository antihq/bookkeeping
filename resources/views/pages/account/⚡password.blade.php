<?php

use App\Models\User;
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

    public function updatePassword(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $rules = [
            'password' => ['required', 'string', PasswordRule::defaults()],
        ];

        if ($user->hasPassword()) {
            $rules['current_password'] = ['required', 'string', 'current_password'];
        }

        try {
            $validated = $this->validate($rules);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password');

            throw $e;
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password');

        Flux::toast('Password has been updated.', variant: 'success');
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <flux:heading size="xl">Password</flux:heading>

    <div class="space-y-14">
        <div class="space-y-6">
            <header class="space-y-1">
                <flux:heading size="lg">{{ auth()->user()->hasPassword() ? 'Update password' : 'Set password' }}</flux:heading>
                <flux:text>{{ auth()->user()->hasPassword() ? 'Ensure your account is using a long, random password to stay secure.' : 'Set a password for your account to enhance security.' }}</flux:text>
            </header>

            <form wire:submit="updatePassword" class="w-full max-w-lg space-y-8">
                @if(auth()->user()->hasPassword())
                    <flux:input
                        wire:model="current_password"
                        :label="'Current password'"
                        type="password"
                        required
                        autocomplete="current-password"
                    />
                @endif
                <flux:input
                    wire:model="password"
                    :label="(auth()->user()->hasPassword() ? 'New password' : 'Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                />
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full">Save changes</flux:button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
