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
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
