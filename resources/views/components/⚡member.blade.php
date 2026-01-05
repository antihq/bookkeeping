<?php

use App\Models\Team;
use Illuminate\Foundation\Auth\User;
use Laravel\Jetstream\Events\TeamMemberUpdated;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Role as JetstreamRole;
use Laravel\Jetstream\Rules\Role;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public User $member;

    public $role = '';

    public function mount()
    {
        $this->role = $this->member->teamRole($this->team)->key;
    }

    public function updatedRole()
    {
        $this->authorize('updateTeamMember', $this->team);

        $this->validate([
            'role' => ['required', 'string', new Role],
        ]);

        $this->team->users()->updateExistingPivot($this->member->id, [
            'role' => $this->role,
        ]);

        TeamMemberUpdated::dispatch($this->team, $this->member);
    }

    #[Computed]
    public function roles()
    {
        return collect(Jetstream::$roles)
            ->transform(function ($role) {
                return with($role->jsonSerialize(), function ($data) {
                    return (new JetstreamRole($data['key'], $data['name'], $data['permissions']))->description(
                        $data['description'],
                    );
                });
            })
            ->values()
            ->all();
    }
};
?>

<div class="flex items-center justify-between gap-4 py-4">
    <div class="flex min-w-0 items-center gap-2">
        @if ($member->profile_photo_path)
            <flux:avatar circle size="xs" src="{{ $member->profile_photo_url }}" />
        @else
            <flux:avatar circle avatar:name="{{ $member->name }}" size="xs" />
        @endif
        <flux:heading size="sm">{{ $member->name }}</flux:heading>
    </div>

    <div class="flex min-w-fit justify-end max-lg:hidden">
        <flux:text class="text-[13px]">
            {{ $member->email }}
        </flux:text>
    </div>

    <div class="flex min-w-fit justify-end">
        @if ($member->id === $team->owner->id)
            <flux:text class="text-[13px]">Owner</flux:text>
        @elseif (Gate::check('updateTeamMember', $team) && Jetstream::hasRoles())
            <flux:dropdown align="end">
                <flux:button
                    size="sm"
                    inset="top bottom"
                    icon:trailing="chevron-down"
                    variant="subtle"
                    class="text-[13px]"
                >
                    {{ Jetstream::findRole($role)->name }}
                </flux:button>

                <flux:menu>
                    <flux:menu.radio.group wire:model.live="role">
                        @foreach ($this->roles as $role)
                            <flux:menu.radio :value="$role->key">{{ $role->name }}</flux:menu.radio>
                        @endforeach
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        @elseif ( Jetstream::hasRoles())
            <flux:text class="text-[13px]">
                {{ Jetstream::findRole($role)->name }}
            </flux:text>
        @endif
    </div>

    <div class="flex min-w-fit justify-end">
        @if (Gate::check('removeTeamMember', $team))
            <flux:button wire:click="$parent.removeMember({{ $member->id }})" size="xs" inset="top bottom">
                Remove
            </flux:button>
        @endif
    </div>
</div>
