<?php

use App\Models\User;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('can render login screen', function () {
    $response = get(route('login'));

    $response->assertStatus(200);
});

it('can authenticate using the login screen', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    assertAuthenticated();
});

it('can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    assertGuest();
});

it('redirects to two factor challenge when two factor is enabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    assertGuest();
});

it('can logout', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));
    assertGuest();
});

it('can not authenticate user with null password', function () {
    $user = User::factory()->create(['password' => null]);

    $response = post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors();

    assertGuest();
});
