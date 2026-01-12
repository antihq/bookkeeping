@blaze

@php
$attributes = $attributes->merge([
    'variant' => 'subtle',
    'class' => '-me-1',
    'square' => true,
    'size' => null,
]);
@endphp

<flux:button
    :$attributes
    :size="$size === 'sm' || $size === 'xs' ? 'xs' : 'sm'"
    x-data="{ copied: false }"
    x-on:click="copied = ! copied; navigator.clipboard && navigator.clipboard.writeText($el.closest('[data-flux-input]').querySelector('input').value); setTimeout(() => copied = false, 2000)"
    x-bind:data-copyable-copied="copied"
    aria-label="{{ __('Copy to clipboard') }}"
>
    <flux:icon.clipboard-document-check variant="micro" class="hidden size-5 sm:size-4 [[data-copyable-copied]>&]:block" />
    <flux:icon.clipboard-document variant="micro" class="block size-5 sm:size-4 [[data-copyable-copied]>&]:hidden" />
</flux:button>
