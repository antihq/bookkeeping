<x-layouts::app.header :title="$title ?? null">
    <flux:main class="flex flex-1 flex-col px-0! pt-0! pb-0! sm:px-2!">
        <div
            class="grow bg-white p-6 sm:rounded-lg sm:p-10 sm:shadow-xs ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10"
        >
            {{ $slot }}
        </div>
    </flux:main>

    <flux:footer class="flex px-6! sm:py-3! sm:mx-auto">
        <flux:text class="text-sm/5 sm:text-[13px]/5">
            <flux:link href="/" :accent="false" wire:navigate>{{ config('app.name') }}</flux:link>
            is designed, built, and backed by
            <flux:link href="https://x.com/oliverservinX" :accent="false">Oliver Servín</flux:link>.
            Need help? Send an email to
            <flux:link href="mailto:oliver@antihq.com" :accent="false">oliver@antihq.com</flux:link>.
        </flux:text>
    </flux:footer>

    @persist('toast')
        <flux:toast position="bottom center" />
    @endpersist
</x-layouts::app.header>
