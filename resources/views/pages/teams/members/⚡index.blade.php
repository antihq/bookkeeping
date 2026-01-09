<?php

use App\Models\Team;
use App\Models\TeamInvitation as ModelsTeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\Events\AddingTeamMember;
use Laravel\Jetstream\Events\InvitingTeamMember;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Laravel\Jetstream\Events\TeamMemberRemoved;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Mail\TeamInvitation;
use Laravel\Jetstream\Role as JetstreamRole;
use Laravel\Jetstream\Rules\Role;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public $email = '';

    public $role = '';

    public function mount()
    {
        $this->authorize('view', $this->team);
    }

    public function addMember()
    {
        $this->resetErrorBag();

        $this->authorize('addTeamMember', $this->team);

        $this->validateMember();

        if (Features::sendsTeamInvitations()) {
            InvitingTeamMember::dispatch($this->team, $this->email, $this->role);

            $invitation = $this->team->teamInvitations()->create([
                'email' => $this->pull('email'),
                'role' => $this->pull('role'),
            ]);

            Mail::to($invitation->email)->send(new TeamInvitation($invitation));
        } else {
            $newTeamMember = Jetstream::findUserByEmailOrFail($this->pull('email'));

            AddingTeamMember::dispatch($this->team, $newTeamMember);

            $this->team->users()->attach($newTeamMember, ['role' => $this->pull('role')]);

            TeamMemberAdded::dispatch($this->team, $newTeamMember);
        }
    }

    public function cancelInvitation($invitationId)
    {
        if (! empty($invitationId)) {
            ModelsTeamInvitation::whereKey($invitationId)
                ->where('team_id', $this->team->id)
                ->delete();
        }
    }

    public function removeMember($userId)
    {
        $user = Jetstream::findUserByIdOrFail($userId);

        $this->authorize('removeTeamMember', $this->team);

        $this->ensureUserDoesNotOwnTeam($user);

        $this->team->removeUser($user);

        TeamMemberRemoved::dispatch($this->team, $user);
    }

    public function leave()
    {
        $this->ensureUserDoesNotOwnTeam($this->user);

        $this->team->removeUser($this->user);

        return $this->redirectRoute('dashboard');
    }

    #[Computed]
    public function user()
    {
        return Auth::user();
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

    protected function validateMember(): void
    {
        if (Features::sendsTeamInvitations()) {
            $this->validateWithInvitations();
        } else {
            $this->validateWithoutInvitations();
        }
    }

    protected function validateWithInvitations(): void
    {
        Validator::make(
            [
                'email' => $this->email,
                'role' => $this->role,
            ],
            $this->memberRulesWithInvitations(),
            [
                'email.unique' => 'This user has already been invited to the team.',
            ],
        )
            ->after($this->ensureUserIsNotAlreadyOnTeam())
            ->validateWithBag('addTeamMember');
    }

    protected function validateWithoutInvitations(): void
    {
        Validator::make(
            [
                'email' => $this->email,
                'role' => $this->role,
            ],
            $this->memberRulesWithoutInvitations(),
            [
                'email.exists' => 'We were unable to find a registered user with this email address.',
            ],
        )
            ->after($this->ensureUserIsNotAlreadyOnTeam())
            ->validateWithBag('addTeamMember');
    }

    protected function memberRulesWithInvitations(): array
    {
        return array_filter([
            'email' => [
                'required',
                'email',
                Rule::unique(Jetstream::teamInvitationModel())->where(function ($query) {
                    $query->where('team_id', $this->team->id);
                }),
            ],
            'role' => Jetstream::hasRoles() ? ['required', 'string', new Role] : null,
        ]);
    }

    protected function memberRulesWithoutInvitations(): array
    {
        return array_filter([
            'email' => ['required', 'email', 'exists:users'],
            'role' => Jetstream::hasRoles() ? ['required', 'string', new Role] : null,
        ]);
    }

    protected function ensureUserIsNotAlreadyOnTeam(): Closure
    {
        return function ($validator) {
            $validator
                ->errors()
                ->addIf($this->team->hasUserWithEmail($this->email), 'email', 'This user already belongs to the team.');
        };
    }

    protected function ensureUserDoesNotOwnTeam(User $teamMember): void
    {
        if ($teamMember->id === $this->team->owner->id) {
            $this->addError('team', 'You may not leave a team that you created.');
        }
    }
};
?>

<section class="mx-auto max-w-6xl space-y-8">
    <flux:heading size="xl">Team members</flux:heading>

    <div class="space-y-14">
        @if ($team->users->isNotEmpty())
            <div class="space-y-6">
                <header class="space-y-1">
                    <flux:heading>Team members</flux:heading>
                    <flux:text>All team members that currently have access to this team.</flux:text>
                </header>

                <div class="max-w-3xl divide-y divide-zinc-100 text-zinc-950 dark:divide-white/5 dark:text-white">
                    @foreach ($team->users->sortBy('name') as $member)
                        <livewire:member :$team :$member key="member-{{ $member->id }}" />
                    @endforeach
                </div>
            </div>
        @endif

        @if (Gate::check('addTeamMember', $team))
            <div class="space-y-6">
                <header class="space-y-1">
                    <flux:heading>Add team member</flux:heading>
                    <flux:text>
                        @if (Features::sendsTeamInvitations())
                            Add a new team member to your team, allowing them to collaborate with you.
                        @else
                                Add a new team member to your team by their email address.
                        @endif
                    </flux:text>
                </header>

                <form wire:submit="addMember" class="w-full max-w-lg space-y-8">
                    <flux:input
                        wire:model="email"
                        type="email"
                        label="Email"
                        placeholder="john@example.com"
                        class="max-w-sm"
                        required
                        autofocus
                    />

                    @if (count($this->roles) > 0)
                        <flux:radio.group wire:model="role" label="Role">
                            @foreach ($this->roles as $role)
                                <flux:radio
                                    name="role"
                                    :value="$role->key"
                                    :label="$role->name"
                                    :description="$role->description"
                                />
                            @endforeach
                        </flux:radio.group>
                    @endif

                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-end">
                            <flux:button variant="primary" type="submit" class="w-full">Add member</flux:button>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        @if ($team->teamInvitations->isNotEmpty() && Gate::check('addTeamMember', $team))
            <div class="space-y-6">
                <header class="space-y-1">
                    <flux:heading>Pending invitations</flux:heading>
                    <flux:text>These people have been invited to your team and haven't accepted yet.</flux:text>
                </header>

                <div class="max-w-3xl divide-y divide-zinc-100 text-zinc-950 dark:divide-white/5 dark:text-white">
                    @foreach ($team->teamInvitations as $invitation)
                        <div class="flex items-center justify-between gap-4 py-4">
                            <div class="flex min-w-0 items-center gap-2">
                                <flux:avatar circle size="xs" />
                                <flux:heading size="sm">{{ $invitation->email }}</flux:heading>
                            </div>

                            <div class="flex min-w-fit justify-end">
                                @if (Jetstream::hasRoles())
                                    <flux:text class="text-[13px]">
                                        {{ Jetstream::findRole($invitation->role)?->name }}
                                    </flux:text>
                                @endif
                            </div>

                            <div class="flex min-w-fit justify-end">
                                @if (Gate::check('removeTeamMember', $team))
                                    <flux:button
                                        wire:click="cancelInvitation({{ $invitation->id }})"
                                        inset="top bottom"
                                    >
                                        Cancel
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($team->owner->id !== $this->user->id)
            <div class="space-y-6">
                <header class="space-y-1">
                    <flux:heading>Leave team</flux:heading>
                    <flux:text>Are you sure you want to leave this team?</flux:text>
                </header>

                <flux:modal.trigger name="leave-team">
                    <flux:button variant="danger">Leave team</flux:button>
                </flux:modal.trigger>

                <flux:modal name="leave-team" class="w-full max-w-xs sm:max-w-md">
                    <div class="space-y-6 sm:space-y-4">
                        <div>
                            <flux:heading>Leave team?</flux:heading>
                            <flux:text class="mt-2">
                                You're about to leave this team. You'll lose access to all team resources.
                            </flux:text>
                        </div>
                        <div class="flex flex-col-reverse items-center justify-end gap-3 *:w-full sm:flex-row sm:*:w-auto">
                            <flux:modal.close>
                                <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button wire:click="leave" variant="primary">Leave team</flux:button>
                        </div>
                    </div>
                </flux:modal>
            </div>
        @endif
    </div>
</section>
