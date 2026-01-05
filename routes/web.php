<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::livewire('accounts', 'pages::accounts.index')->name('accounts.index');
    Route::livewire('accounts/create', 'pages::accounts.create')->name('accounts.create');
    Route::livewire('accounts/{account}', 'pages::accounts.show')->name('accounts.show');
    Route::livewire('accounts/{account}/edit', 'pages::accounts.edit')->name('accounts.edit');

    Route::livewire('teams/create', 'pages::teams.create')->name('teams.create');
    Route::livewire('teams/{team}', 'pages::teams.show')->name('teams.edit');
    Route::livewire('teams/{team}/members', 'pages::teams.members.index')->name('teams.members.index');

    Route::redirect('account', 'settings/profile');

    Route::livewire('account/profile', 'pages::account.profile')->name('profile.edit');
    Route::livewire('account/password', 'pages::account.password')->name('user-password.edit');
    Route::livewire('account/appearance', 'pages::account.appearance')->name('appearance.edit');

    Route::livewire('account/two-factor', 'pages::account.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
