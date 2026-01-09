<?php

use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public $name = '';

    public function mount()
    {
        $this->authorize('view', $this->team);

        $this->name = $this->team->name;
    }

    public function update()
    {
        $this->authorize('update', $this->team);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->team
            ->fill([
                'name' => $this->name,
            ])
            ->save();

        Flux::toast('Team name has been saved.', variant: 'success');
    }

    public function delete()
    {
        $this->authorize('delete', $this->team);

        if ($this->team->personal_team) {
            $this->addError('team', 'You may not delete your personal team.');

            return;
        }

        $this->team->purge();

        return $this->redirectRoute('dashboard');
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <flux:heading size="xl">Team settings</flux:heading>

    <div class="space-y-14">
        <div class="space-y-6">
            <header class="space-y-1">
                <flux:heading>Team name</flux:heading>
                <flux:text>The name of your team appears on your profile.</flux:text>
            </header>

            <form wire:submit="update" class="w-full max-w-lg space-y-8">
                <flux:input
                    wire:model="name"
                    label="Team name"
                    type="text"
                    :readonly="! Gate::check('update', $team)"
                    :variant="! Gate::check('update', $team) ? 'filled' : null"
                    required
                    autofocus
                />

                @if (Gate::check('update', $team))
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-end">
                            <flux:button variant="primary" type="submit" class="w-full">Save changes</flux:button>
                        </div>
                    </div>
                @endif
            </form>
        </div>

        @if (Gate::check('delete', $team) && ! $team->personal_team)
            <div class="space-y-6">
                <header class="space-y-1">
                    <flux:heading>Delete team</flux:heading>
                </header>

                <flux:modal.trigger name="delete">
                    <flux:button variant="danger">Delete team</flux:button>
                </flux:modal.trigger>

                <flux:modal name="delete" class="w-full max-w-xs sm:max-w-md">
                    <div class="space-y-6 sm:space-y-4">
                        <div>
                            <flux:heading>Delete team?</flux:heading>
                            <flux:text class="mt-2">
                                You're about to delete this team. This action cannot be reversed.
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
            </div>
        @endif
    </div>
</section>
