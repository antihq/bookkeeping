<x-layouts::auth>
    <div class="flex flex-col gap-8">
        <x-auth-header title="Sign in to your account" />

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-8">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Email"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    label="Password"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:text class="absolute end-0 top-0">
                        <flux:link :href="route('password.request')" wire:navigate>
                            Forgot password?
                        </flux:link>
                    </flux:text>
                @endif
            </div>

            <input type="hidden" name="remember" value="1" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    Login
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <flux:text>
                Don't have an account?
                <flux:link :href="route('register')" wire:navigate>Sign up</flux:link>
            </flux:text>
        @endif
    </div>
</x-layouts::auth>
