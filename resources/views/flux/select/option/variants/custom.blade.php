@aware([ 'indicator' ])

@props([
    'filterable' => null,
    'indicator' => null,
    'loading' => null,
    'label' => null,
    'value' => null,
])

@php
$classes = Flux::classes()
    ->add('group/option overflow-hidden data-hidden:hidden group flex items-center py-2.5 pl-2 pr-3.5 w-full focus:outline-hidden sm:py-1.5 sm:pl-2 sm:pr-3')
    ->add('rounded-lg')
    ->add('text-start text-base/6 select-none sm:text-sm/6')
    ->add('text-zinc-950 data-active:text-white data-active:bg-blue-500 [&[disabled]]:text-zinc-400 dark:text-white dark:data-active:bg-blue-500 dark:[&[disabled]]:text-zinc-400')
    ;

$livewireAction = $attributes->whereStartsWith('wire:click')->isNotEmpty();
$alpineAction = $attributes->whereStartsWith('x-on:click')->isNotEmpty();

$loading ??= $loading ?? $livewireAction;

if ($loading) {
    $attributes = $attributes->merge(['wire:loading.attr' => 'data-flux-loading']);
}
@endphp

<ui-option
    @if ($value !== null) value="{{ $value }}" @endif
    @if ($value) wire:key="{{ $value }}" @endif
    @if ($filterable === false) filter="manual" @endif
    @if ($livewireAction || $alpineAction) action @endif
    {{ $attributes->class($classes) }}
    data-flux-option
>
    <div class="w-7 sm:w-6 shrink-0 [ui-selected_&]:hidden">
        <flux:select.indicator :variant="$indicator" />
    </div>

    {{ $label ?? $slot }}

    <?php if ($loading): ?>
        <flux:icon.loading class="hidden [[data-flux-loading]>&]:block ms-auto text-zinc-400 [[data-flux-menu-item]:hover_&]:text-current" variant="micro" />
    <?php endif; ?>
</ui-option>
