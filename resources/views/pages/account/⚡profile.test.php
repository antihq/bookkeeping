<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders successfully', function () {
    $user = User::factory()->withPersonalTeam()->create();

    Auth::login($user);

    Livewire::test('pages::account.profile')
        ->assertStatus(200);
});

it('profile page is displayed', function () {
    $user = User::factory()->withPersonalTeam()->create();

    actingAs($user);

    get('/account/profile')->assertOk();
});

it('profile information can be updated', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = Livewire::test('pages::account.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

it('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = Livewire::test('pages::account.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('user can delete their account', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = Livewire::test('account.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
});

it('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = Livewire::test('account.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

it('user can upload a profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    actingAs($user);

    $photo = UploadedFile::fake()->image('photo.jpg');

    Livewire::test('pages::account.profile')
        ->set('photo', $photo)
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->profile_photo_path)->not->toBeNull();
    expect($user->profile_photo_path)->toContain('profile-photos');
});

it('profile photo must be an image', function () {
    $user = User::factory()->create();

    actingAs($user);

    $file = UploadedFile::fake()->create('document.txt', 100);

    $livewire = Livewire::test('pages::account.profile');

    $livewire->assertHasNoErrors();

    $livewire->set('photo', $file);

    $livewire->assertHasErrors(['photo' => 'image']);
});

it('profile photo must not exceed 10MB', function () {
    $user = User::factory()->create();

    actingAs($user);

    $photo = UploadedFile::fake()->image('photo.jpg')->size(10241);

    Livewire::test('pages::account.profile')
        ->set('photo', $photo)
        ->assertHasErrors(['photo' => 'max']);
});

it('user can remove their profile photo', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    actingAs($user);

    $photo = UploadedFile::fake()->image('photo.jpg');

    Livewire::test('pages::account.profile')
        ->set('photo', $photo)
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->profile_photo_path)->not->toBeNull();

    Livewire::test('pages::account.profile')
        ->call('removePhoto');

    $user->refresh();

    expect($user->profile_photo_path)->toBeNull();
});
