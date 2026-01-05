<x-layouts::app.header :title="$title ?? null">
    <flux:main class="flex flex-1 flex-col px-0! pt-0! pb-0! lg:px-2!">
        <div
            class="grow p-6 lg:rounded-lg lg:bg-white lg:p-10 lg:shadow-xs lg:ring-1 lg:ring-zinc-950/5 dark:lg:bg-zinc-900 dark:lg:ring-white/10"
        >
            {{ $slot }}
        </div>
    </flux:main>

    <flux:footer class="px-6! py-3!">
        <flux:text class="text-xs lg:text-sm">
            <flux:link href="/" :accent="false" wire:navigate>{{ config('app.name') }}</flux:link>
            is designed, built, and backed by
            <flux:link href="https://x.com/oliverservinX" :accent="false">Oliver Servín</flux:link>
            . Need help? Send an email to
            <flux:link href="mailto:oliver@antihq.com" :accent="false">oliver@antihq.com</flux:link>
            .
        </flux:text>
    </flux:footer>

    @persist('toast')
        <flux:toast position="bottom center" />
    @endpersist
</x-layouts::app.header>
