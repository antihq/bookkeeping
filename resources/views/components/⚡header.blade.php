<?php

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
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
    <flux:spacer />

    <flux:navbar>
        <flux:navbar.item
            :href="route('dashboard')"
            :current="request()->routeIs('accounts.index')"
            wire:navigate
        >
            Overview
        </flux:navbar.item>

        <flux:dropdown position="bottom">
            <flux:navbar.item icon:trailing="chevron-down" icon:variant="micro">
                Accounts
            </flux:navbar.item>

            <flux:menu>
                <flux:menu.item :href="route('transactions.index')" icon="rectangle-stack" wire:navigate>
                    All accounts
                </flux:menu.item>

                @if (auth()->user()->currentTeam->accounts->isNotEmpty())
                    <flux:menu.separator />

                    @foreach (auth()->user()->currentTeam->accounts as $account)
                        <flux:menu.item
                            :href="route('accounts.show', $account)"
                            :icon="request()->account?->is($account) ? 'check' : null"
                            wire:navigate
                        >
                            <span class="inline-flex w-full items-center justify-between gap-4">
                                {{ $account->name }}
                                <flux:text class="text-[13px] font-normal tabular-nums" inline>
                                    {{ $account->formatted_balance }}
                                </flux:text>
                            </span>
                        </flux:menu.item>
                    @endforeach
                @endif

                <flux:menu.separator />

                <flux:menu.item :href="route('accounts.create')" icon="plus" wire:navigate>
                    Add new account
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:navbar>

    <flux:spacer />

    <!-- Desktop User Menu -->
    <flux:dropdown position="top" align="end">
        <flux:profile class="cursor-pointer" :chevron="false">
            <x-slot:avatar>
                @if (auth()->user()->profile_photo_path)
                    <flux:avatar :src="auth()->user()->profile_photo_url" size="xs" circle />
                @else
                    <x-boring-avatar
                        :name="auth()->user()->name ?? auth()->user()->email"
                        variant="beam"
                        class="[:where(&)]:size-7 sm:[:where(&)]:size-6"
                    />
                @endif
            </x-slot>
        </flux:profile>

        <flux:menu class="min-w-64">
            <flux:menu.group heading="Account">
                <flux:menu.item :href="route('profile.edit')" icon="user" icon:variant="micro" wire:navigate>
                    Profile
                </flux:menu.item>
                <flux:menu.item :href="route('user-password.edit')" icon="key" icon:variant="micro" wire:navigate>
                    Password
                </flux:menu.item>
                <flux:menu.item :href="route('two-factor.show')" icon="shield-check" icon:variant="micro" wire:navigate>
                    Two-factor auth
                </flux:menu.item>
                <flux:menu.item
                    :href="route('devices.create')"
                    icon="device-phone-mobile"
                    icon:variant="micro"
                    wire:navigate
                >
                    Devices
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
                    icon="cog-8-tooth"
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
                    Log out
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:header>
