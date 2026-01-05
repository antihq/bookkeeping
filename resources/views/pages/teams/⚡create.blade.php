<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\Events\AddingTeam;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public $name = '';

    public function create()
    {
        $this->authorize('create', Team::class);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        AddingTeam::dispatch($this->user);

        $this->user->switchTeam($this->user->ownedTeams()->create([
            'name' => $this->name,
            'personal_team' => false,
        ]));

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
    <flux:heading size="lg">Create team</flux:heading>

    <div class="space-y-14">
        <div class="space-y-8">
            <header>
                <flux:heading>Team details</flux:heading>
                <flux:text class="mt-1">Create a new team to collaborate with others on projects.</flux:text>
            </header>

            <form wire:submit="create" class="w-full max-w-lg space-y-8">
                <flux:input wire:model="name" label="Team name" type="text" size="sm" required autofocus />

                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full" size="sm">Create</flux:button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
