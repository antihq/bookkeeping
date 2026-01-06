<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="dark antialiased lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950"
>
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-svh w-full flex-col bg-white lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950">
        <livewire:header class="px-6!" />

        <!-- Mobile Menu -->
        <flux:sidebar
            sticky
            collapsible="mobile"
            class="rounded-r-lg bg-white shadow-xs ring-1 ring-zinc-950/5 lg:hidden dark:bg-zinc-900 dark:ring-white/10"
        >
            <flux:sidebar.header>
                <flux:spacer />
                <flux:sidebar.collapse
                    class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2"
                />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')"
                    :accent="false"
                    wire:navigate
                >
                    Dashboard
                </flux:sidebar.item>

                <flux:sidebar.group heading="Accounts">
                    @foreach (auth()->user()->currentTeam->accounts as $account)
                        <flux:sidebar.item
                            :href="route('accounts.show', $account)"
                            :current="request()->account?->is($account)"
                            :accent="false"
                            wire:navigate
                        >
                            <span class="inline-flex w-full items-center justify-between gap-4">
                                {{ $account->name }}
                                <flux:text class="text-[13px] font-normal tabular-nums" inline>
                                    {{ $account->formatted_balance }}
                                </flux:text>
                            </span>
                        </flux:sidebar.item>
                    @endforeach

                    <flux:sidebar.item :href="route('accounts.create')" :accent="false" icon="plus" wire:navigate>
                        Create new account
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
