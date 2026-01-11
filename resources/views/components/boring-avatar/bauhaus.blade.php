<svg viewBox="0 0 {{ $baseSize }} {{ $baseSize }}" fill="none" role="img" xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['width' => $size, 'height' => $size]) }}>
    @if ($title)
        <title>{{ $name }}</title>
    @endif

    <mask id="{{ $maskId }}" maskUnits="userSpaceOnUse" x="0" y="0" width="{{ $baseSize }}" height="{{ $baseSize }}">
        <rect width="{{ $baseSize }}" height="{{ $baseSize }}" rx="{{ $square ? 0 : $baseSize * 2 }}" fill="#FFFFFF" />
    </mask>

    <g mask="url(#{{ $maskId }})">
        <rect width="{{ $baseSize }}" height="{{ $baseSize }}" fill="{{ $elementsProperties[0]['color'] }}" />

        <rect
            x="{{ ($baseSize - 60) / 2 }}"
            y="{{ ($baseSize - 20) / 2 }}"
            width="{{ $baseSize }}"
            height="{{ $elementsProperties[1]['isSquare'] ? $baseSize : $baseSize / 8 }}"
            fill="{{ $elementsProperties[1]['color'] }}"
            transform="translate({{ $elementsProperties[1]['translateX'] }} {{ $elementsProperties[1]['translateY'] }}) rotate({{ $elementsProperties[1]['rotate'] }} {{ $baseSize / 2 }} {{ $baseSize / 2 }})" />

        <circle
            cx="{{ $baseSize / 2 }}"
            cy="{{ $baseSize / 2 }}"
            fill="{{ $elementsProperties[2]['color'] }}"
            r="{{ $baseSize / 5 }}"
            transform="translate({{ $elementsProperties[2]['translateX'] }} {{ $elementsProperties[2]['translateY'] }})" />

        <line
            x1="0"
            y1="{{ $baseSize / 2 }}"
            x2="{{ $baseSize }}"
            y2="{{ $baseSize / 2 }}"
            stroke-width="2"
            stroke="{{ $elementsProperties[3]['color'] }}"
            transform="translate({{ $elementsProperties[3]['translateX'] }} {{ $elementsProperties[3]['translateY'] }}) rotate({{ $elementsProperties[3]['rotate'] }} {{ $baseSize / 2 }} {{ $baseSize / 2 }})" />
    </g>
</svg>
