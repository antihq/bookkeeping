@blaze

@php $iconTrailing ??= $attributes->pluck('icon:trailing'); @endphp
@php $iconVariant ??= $attributes->pluck('icon:variant'); @endphp

@props([
    'iconVariant' => 'mini',
    'iconTrailing' => null,
    'variant' => 'default',
    'indent' => false,
    'suffix' => null,
    'label' => null,
    'kbd' => null,
])

@php
if ($kbd) $suffix = $kbd;

$iconClasses = Flux::classes()
    // When using the outline icon variant, we need to size it down to match the default icon sizes...
    ->add($iconVariant === 'outline' ? 'size-5 sm:size-4' : 'size-5 sm:size-4')
    ;

$iconTrailingClasses = Flux::classes()
    ->add('ms-auto')
    // When using the outline icon variant, we need to size it down to match the default icon sizes...
    ->add($iconVariant === 'outline' ? 'size-5 sm:size-4' : 'size-5 sm:size-4')
    ;

$classes = Flux::classes()
    ->add('group/menu-radio flex items-center px-3.5 py-2.5 w-full focus:outline-hidden sm:px-3 sm:py-1.5')
    ->add('rounded-lg')
    ->add('text-start text-base/6 sm:text-sm/6')
    ->add('[[disabled]_&]:opacity-50 [&[disabled]]:opacity-50')
    ->add([
        'text-zinc-950 data-active:bg-blue-500 data-active:text-white dark:text-white',
        '**:data-flux-menu-item-icon:text-zinc-500 dark:**:data-flux-menu-item-icon:text-zinc-400 [&[data-active]_[data-flux-menu-item-icon]]:text-current',
    ])
    ;
@endphp

<ui-menu-radio {{ $attributes->class($classes) }} data-flux-menu-item-has-icon data-flux-menu-radio>
    <div class="w-7">
        <div class="hidden group-data-checked/menu-radio:block">
            <flux:icon :variant="$iconVariant" icon="check" :class="$iconClasses" data-flux-menu-item-icon />
        </div>
    </div>

    {{ $label ?? $slot }}

    <?php if ($suffix): ?>
        <div class="ms-auto opacity-50 text-xs">
            {{ $suffix }}
        </div>
    <?php endif; ?>

    <?php if (is_string($iconTrailing) && $iconTrailing !== ''): ?>
        <flux:icon :icon="$iconTrailing" :variant="$iconVariant" :class="$iconTrailingClasses" data-flux-menu-item-icon />
    <?php elseif ($iconTrailing): ?>
        {{ $iconTrailing }}
    <?php endif; ?>
</ui-menu-radio>
