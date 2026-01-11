<svg viewBox="0 0 {{ $baseSize }} {{ $baseSize }}" fill="none" role="img" xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['width' => $size, 'height' => $size]) }}>
    @if ($title)
        <title>{{ $name }}</title>
    @endif

    <mask id="{{ $maskId }}" maskUnits="userSpaceOnUse" x="0" y="0" width="{{ $baseSize }}" height="{{ $baseSize }}">
        <rect width="{{ $baseSize }}" height="{{ $baseSize }}" rx="{{ $square ? 0 : $baseSize * 2 }}" fill="#FFFFFF" />
    </mask>

    <g mask="url(#{{ $maskId }})">
        <rect width="{{ $baseSize }}" height="{{ $baseSize }}" fill="{{ $data['backgroundColor'] }}" />

        <rect
            x="0"
            y="0"
            width="{{ $baseSize }}"
            height="{{ $baseSize }}"
            transform="translate({{ $data['wrapperTranslateX'] }} {{ $data['wrapperTranslateY'] }}) rotate({{ $data['wrapperRotate'] }} {{ $baseSize / 2 }} {{ $baseSize / 2 }}) scale({{ $data['wrapperScale'] }})"
            fill="{{ $data['wrapperColor'] }}"
            rx="{{ $data['isCircle'] ? $baseSize : $baseSize / 6 }}" />

        <g transform="translate({{ $data['faceTranslateX'] }} {{ $data['faceTranslateY'] }}) rotate({{ $data['faceRotate'] }} {{ $baseSize / 2 }} {{ $baseSize / 2 }})">
            @if ($data['isMouthOpen'])
                <path
                    d="{{ $mouthPath }}"
                    stroke="{{ $data['faceColor'] }}"
                    fill="none"
                    stroke-linecap="round" />
            @else
                <path d="{{ $mouthPath }}" fill="{{ $data['faceColor'] }}" />
            @endif

            <rect
                x="{{ 14 - $data['eyeSpread'] }}"
                y="14"
                width="1.5"
                height="2"
                rx="1"
                stroke="none"
                fill="{{ $data['faceColor'] }}" />

            <rect
                x="{{ 20 + $data['eyeSpread'] }}"
                y="14"
                width="1.5"
                height="2"
                rx="1"
                stroke="none"
                fill="{{ $data['faceColor'] }}" />
        </g>
    </g>
</svg>
