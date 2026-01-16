<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark bg-zinc-100 antialiased dark:bg-zinc-950">
    <head>
        @include('partials.head')
    </head>
    <body class="flex min-h-svh w-full flex-col bg-zinc-100 dark:bg-zinc-950">
        <livewire:header />

        {{ $slot }}

        @fluxScripts
    </body>
</html>
