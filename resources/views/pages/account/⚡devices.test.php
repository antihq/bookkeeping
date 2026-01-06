<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('signed url logs in user when accessed', function () {
    $component = Livewire::actingAs($user = User::factory()->create())->test('pages::account.devices');

    $signedUrl = $component->get('loginUrl');

    Auth::logout();

    expect(Auth::check())->toBeFalse();

    get($signedUrl)->assertRedirect('/dashboard');

    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->id)->toBe($user->id);
});

it('signed url rejects invalid signature', function () {
    $user = User::factory()->create();

    expect(Auth::check())->toBeFalse();

    $invalidUrl = route('device-login', [$user, 'signature' => 'invalid']);

    get($invalidUrl)->assertStatus(403);

    expect(Auth::check())->toBeFalse();
});

it('signed url rejects expired urls', function () {
    $user = User::factory()->create();

    expect(Auth::check())->toBeFalse();

    $signedUrl = URL::temporarySignedRoute(
        'device-login',
        now()->subMinutes(1),
        ['user' => $user->id],
    );

    get($signedUrl)->assertStatus(403);

    expect(Auth::check())->toBeFalse();
});
