@blaze

@php
$classes = Flux::classes([
    'px-3.5 pt-2 pb-1 w-full sm:px-3',
    'flex items-center',
    'text-start text-sm/5 font-medium sm:text-xs/5',
    'text-zinc-500 font-medium dark:text-zinc-400',
]);
@endphp

<div {{ $attributes->class($classes) }} data-flux-menu-heading>
    <div class="w-7 hidden [[data-flux-menu]:has(>[data-flux-menu-item-has-icon])_&]:block"></div>

    <div>{{ $slot }}</div>
</div>
