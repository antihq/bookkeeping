<?php

it('can render registration screen', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

it('can register new users', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
