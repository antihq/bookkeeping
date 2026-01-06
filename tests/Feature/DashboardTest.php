<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected to the login page', function () {
    get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    get('/dashboard')->assertRedirect();
});
