<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('can update team names', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('pages::teams.show', ['team' => $user->currentTeam])
        ->set('name', 'Test Team')
        ->call('update');

    expect($user->fresh()->ownedTeams)->toHaveCount(1);
    expect($user->currentTeam->fresh()->name)->toEqual('Test Team');
});

test('teams can be deleted', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    $user->ownedTeams()->save($team = Team::factory()->make([
        'personal_team' => false,
    ]));

    $team->users()->attach(
        $otherUser = User::factory()->create(), ['role' => 'test-role']
    );

    Livewire::test('pages::teams.show', ['team' => $team->fresh()])
        ->call('delete');

    expect($team->fresh())->toBeNull();
    expect($otherUser->fresh()->teams)->toHaveCount(0);
});

test('personal teams cant be deleted', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('pages::teams.show', ['team' => $user->currentTeam])
        ->call('delete')
        ->assertHasErrors(['team']);

    expect($user->currentTeam->fresh())->not->toBeNull();
});

test('delete modal is shown for non-personal teams', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    $user->ownedTeams()->save($team = Team::factory()->make([
        'personal_team' => false,
    ]));

    Livewire::test('pages::teams.show', ['team' => $team->fresh()])
        ->assertSee('Delete team?', false)
        ->assertSee('You\'re about to delete this team.', false)
        ->assertSee('This action cannot be reversed.', false)
        ->assertSee('Cancel', false)
        ->assertSee('Delete team', false);
});

test('delete modal is not shown for personal teams', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('pages::teams.show', ['team' => $user->currentTeam])
        ->assertDontSee('Delete Team')
        ->assertDontSee('Delete team?');
});
