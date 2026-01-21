<?php

use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

it('hides categories menu item when team has no categories', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    Livewire::test('header')
        ->assertDontSee('Categories');
});

it('shows categories menu item when team has categories', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;

    Category::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user);

    Livewire::test('header')
        ->assertSee('Categories');
});
