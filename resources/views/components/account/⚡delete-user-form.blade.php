<?php

use App\Livewire\Actions\Logout;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        DB::transaction(function () use ($logout) {
            $this->deleteTeams();
            $this->user->deleteProfilePhoto();
            $this->user->tokens->each->delete();
            tap($this->user, $logout(...))->delete();
        });

        $this->redirect('/', navigate: true);
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    protected function deleteTeams(): void
    {
        $this->user->teams()->detach();

        $this->user->ownedTeams->each(function (Team $team) {
            $team->delete;
        });
    }
};
?>

<section class="space-y-6">
    <div class="space-y-1">
        <flux:heading>Delete account</flux:heading>
        <flux:text>Delete your account and all of its resources</flux:text>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button
            variant="danger"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >
            Delete account
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="w-full max-w-xs sm:max-w-md">
        <form wire:submit="deleteUser" class="space-y-6 sm:space-y-4">
            <div class="space-y-2">
                <flux:heading size="lg">Are you sure you want to delete your account?</flux:heading>

                <flux:text>
                    Once your account is deleted, all of its resources and data will be permanently deleted. Please
                    enter your password to confirm you would like to permanently delete your account.
                </flux:text>
            </div>

            <flux:input wire:model="password" label="Password" type="password" placeholder="•••••••" label:sr-only />

            <div class="flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto">
                <flux:modal.close>
                    <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">Delete account</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
