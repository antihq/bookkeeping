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
            $this->deletesTeams->delete($team);
        });
    }
};
?>

<section class="space-y-6">
    <div class="relative mb-5">
        <flux:heading>Delete account</flux:heading>
        <flux:subheading>Delete your account and all of its resources</flux:subheading>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <flux:button
            variant="danger"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            size="sm"
        >
            Delete account
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form wire:submit="deleteUser" class="space-y-6">
            <div>
                <flux:heading size="lg">Are you sure you want to delete your account?</flux:heading>

                <flux:subheading>
                    Once your account is deleted, all of its resources and data will be permanently deleted. Please
                    enter your password to confirm you would like to permanently delete your account.
                </flux:subheading>
            </div>

            <flux:input wire:model="password" label="Password" type="password" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button size="sm">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit" size="sm">Delete account</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
