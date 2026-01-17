<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\RoutePath;

Route::redirect('/', 'dashboard')->name('home');

Route::view('site.webmanifest', 'site-webmanifest');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('accounts', 'pages::accounts.index')->name('accounts.index');
    Route::livewire('accounts/create', 'pages::accounts.create')->name('accounts.create');
    Route::livewire('accounts/{account}', 'pages::accounts.show')->name('accounts.show');
    Route::livewire('accounts/{account}/edit', 'pages::accounts.edit')->name('accounts.edit');

    Route::livewire('transactions', 'pages::transactions.index')->name('transactions.index');
    Route::livewire('transactions/{transaction}', 'pages::transactions.show')->name('transactions.show');
    Route::livewire('breakdown', 'pages::breakdown')->name('breakdown.index');
});

Route::middleware(['auth'])->group(function () {
    Route::livewire('teams/create', 'pages::teams.create')->name('teams.create');
    Route::livewire('teams/{team}', 'pages::teams.show')->name('teams.edit');
    Route::livewire('teams/{team}/members', 'pages::teams.members.index')->name('teams.members.index');

    Route::redirect('account', 'settings/profile');

    Route::livewire('account/profile', 'pages::account.profile')->name('profile.edit');
    Route::livewire('account/password', 'pages::account.password')->name('user-password.edit');
    Route::livewire('account/appearance', 'pages::account.appearance')->name('appearance.edit');
    Route::livewire('account/devices', 'pages::account.devices')->middleware(['password.confirm'])->name('devices.create');

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

Route::get('device-login/{user}', function (Request $request, User $user) {
    if (! $request->hasValidSignature()) {
        abort(401);
    }

    Auth::login($user);

    return redirect()->route('dashboard');
})->name('device-login')->middleware('signed');

Route::get(RoutePath::for('password.reset', '/reset-password/{token}'), [NewPasswordController::class, 'create'])
    ->name('password.reset');

Route::post(RoutePath::for('password.update', '/reset-password'), [NewPasswordController::class, 'store'])
    ->name('password.update');
