@props([
    'status',
])

@if ($status)
    <flux:text {{ $attributes }}>
        {{ $status }}
    </flux:text>
@endif
