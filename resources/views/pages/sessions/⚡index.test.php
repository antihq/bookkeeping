<?php

use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('other browser sessions can be logged out', function () {
    actingAs(User::factory()->create());

    Livewire::test('pages::sessions.index')
        ->set('password', 'password')
        ->call('logoutOtherBrowserSessions')
        ->assertSuccessful();
});
