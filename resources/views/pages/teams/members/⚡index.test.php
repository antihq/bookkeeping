<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Mail\TeamInvitation;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('team members can be invited to team', function () {
    if (! Features::sendsTeamInvitations()) {
        $this->markTestSkipped('Team invitations not enabled.');
    }

    Mail::fake();

    actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('pages::teams.members.index', ['team' => $user->currentTeam])
        ->set([
            'email' => 'test@example.com',
            'role' => 'admin',
        ])->call('addMember');

    Mail::assertSent(TeamInvitation::class);

    expect($user->currentTeam->fresh()->teamInvitations)->toHaveCount(1);
});

test('team member invitations can be cancelled', function () {
    if (! Features::sendsTeamInvitations()) {
        $this->markTestSkipped('Team invitations not enabled.');
    }

    Mail::fake();

    actingAs($user = User::factory()->withPersonalTeam()->create());

    // Add the team member...
    $component = Livewire::test('pages::teams.members.index', ['team' => $user->currentTeam])
        ->set([
            'email' => 'test@example.com',
            'role' => 'admin',
        ])->call('addMember');

    $invitationId = $user->currentTeam->fresh()->teamInvitations->first()->id;

    // Cancel the team invitation...
    $component->call('cancelInvitation', $invitationId);

    expect($user->currentTeam->fresh()->teamInvitations)->toHaveCount(0);
});

test('team members can be removed from teams', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->create(), ['role' => 'admin']
    );

    $component = Livewire::test('pages::teams.members.index', ['team' => $user->currentTeam])
        ->call('removeMember', $otherUser->id)
        ->assertHasNoErrors();

    expect($user->currentTeam->fresh()->users)->toHaveCount(0);
});

test('only team owner can remove team members', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->create(), ['role' => 'admin']
    );

    actingAs($otherUser);

    Livewire::test('pages::teams.members.index', ['team' => $user->currentTeam])
        ->call('removeMember', $user->id)
        ->assertStatus(403);
});

test('users can leave teams', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->create(), ['role' => 'admin']
    );

    actingAs($otherUser);

    Livewire::test('pages::teams.members.index', ['team' => $user->currentTeam])
        ->call('leave');

    expect($user->currentTeam->fresh()->users)->toHaveCount(0);
});

test('team owners cant leave their own team', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('pages::teams.members.index', ['team' => $user->currentTeam])
        ->call('leave')
        ->assertHasErrors(['team']);

    expect($user->currentTeam->fresh())->not->toBeNull();
});
