<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests to the login page', function () {
    get('/dashboard')->assertRedirect('/login');
});

it('allows authenticated users to visit the dashboard', function () {
    actingAs($user = User::factory()->withPersonalTeam()->create());

    get('/dashboard')->assertRedirect();
});
