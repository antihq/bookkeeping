<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public ?int $team = null;

    public function mount(): void
    {
        $this->team = Auth::user()?->current_team_id;
    }

    public function updatedTeam()
    {
        $team = Team::findOrFail($this->team);

        if (! Auth::user()->switchTeam($team)) {
            abort(403);
        }

        return $this->redirectRoute('dashboard', navigate: true);
    }
};
?>

<flux:header {{ $attributes }}>
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" size="sm" />

    <flux:navbar class="-mb-px max-lg:hidden">
        <flux:dropdown position="bottom">
            <flux:navbar.item :accent="false" icon:trailing="chevron-down" icon:variant="micro">
                Accounts
            </flux:navbar.item>

            <flux:menu>
                @foreach (auth()->user()->currentTeam->accounts as $account)
                    <flux:menu.item
                        :href="route('accounts.show', $account)"
                        :icon="request()->account?->is($account) ? 'check' : null"
                        wire:navigate
                    >
                        <span class="inline-flex w-full justify-between gap-2">
                            {{ $account->name }}
                            <flux:text class="font-normal" inline>{{ $account->formatted_balance }}</flux:text>
                        </span>
                    </flux:menu.item>
                @endforeach

                @if (auth()->user()->currentTeam->accounts->isNotEmpty())
                    <flux:menu.separator />
                @endif

                <flux:menu.item :href="route('accounts.create')" icon="plus" wire:navigate>
                    Create new account
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:navbar>

    <flux:spacer />

    <!-- Desktop User Menu -->
    <flux:dropdown position="top" align="end">
        <flux:profile
            class="cursor-pointer"
            :avatar="auth()->user()->profile_photo_url"
            :initials="auth()->user()->initials()"
            avatar:size="xs"
            :chevron="false"
        />

        <flux:menu>
            <flux:menu.group heading="Account">
                <flux:menu.item :href="route('profile.edit')" icon="user" icon:variant="micro" wire:navigate>
                    Profile
                </flux:menu.item>
                <flux:menu.item :href="route('user-password.edit')" icon="key" icon:variant="micro" wire:navigate>
                    Password
                </flux:menu.item>
                <flux:menu.item :href="route('two-factor.show')" icon="shield-check" icon:variant="micro" wire:navigate>
                    Two-Factor auth
                </flux:menu.item>
                <flux:menu.item
                    :href="route('appearance.edit')"
                    icon="adjustments-horizontal"
                    icon:variant="micro"
                    wire:navigate
                >
                    Appearance
                </flux:menu.item>
            </flux:menu.group>

            <flux:menu.group heading="Team">
                <flux:menu.item
                    :href="route('teams.edit', auth()->user()->currentTeam)"
                    icon="cog"
                    icon:variant="micro"
                    wire:navigate
                >
                    Settings
                </flux:menu.item>
                <flux:menu.item
                    :href="route('teams.members.index', auth()->user()->currentTeam)"
                    icon="users"
                    icon:variant="micro"
                    wire:navigate
                >
                    Members
                </flux:menu.item>
                <flux:menu.item :href="route('teams.create')" icon="plus" icon:variant="micro" wire:navigate>
                    Create new team
                </flux:menu.item>
            </flux:menu.group>

            @if (Auth::user()->allTeams()->count() > 1)
                <flux:menu.group heading="Switch teams">
                    <flux:menu.radio.group wire:model.live="team">
                        @foreach (Auth::user()->allTeams() as $team)
                            <flux:menu.radio :value="$team->id">{{ $team->name }}</flux:menu.radio>
                        @endforeach
                    </flux:menu.radio.group>
                </flux:menu.group>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    icon:variant="micro"
                    class="w-full"
                >
                    Log Out
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:header>
