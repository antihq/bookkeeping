<?php

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('can render registration screen', function () {
    $response = get(route('register'));

    $response->assertStatus(200);
});

it('can register new users', function () {
    Notification::fake();

    $response = post(route('register.store'), [
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    assertAuthenticated();

    Notification::assertSentTo(
        User::where('email', 'test@example.com')->first(),
        WelcomeNotification::class
    );
});
