<x-layouts::auth>
    <div class="flex flex-col gap-8">
        <x-auth-header title="Create your account" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-8">
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

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">Create account</flux:button>
            </div>
        </form>

        <flux:text>
            Already have an account?
            <flux:link :href="route('login')" wire:navigate>Sign in</flux:link>
        </flux:text>
    </div>
</x-layouts::auth>
