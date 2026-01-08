@blaze

<flux:button
    :attributes="$attributes->class('shrink-0 *:data-[slot=icon]:size-6 size-10!')"
    variant="subtle"
    square
    x-data
    x-on:click="$dispatch('flux-sidebar-toggle')"
    aria-label="{{ __('Toggle sidebar') }}"
    data-flux-sidebar-toggle
/>
