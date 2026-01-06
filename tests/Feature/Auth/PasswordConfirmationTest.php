<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('can render confirm password screen', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get(route('password.confirm'));

    $response->assertStatus(200);
});
