<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BoringAvatar extends Component
{
    public const SIZE_BAUHAUS = 80;

    public const SIZE_BEAM = 36;

    public const SIZE_MARBLE = 80;

    public const SIZE_PIXEL = 80;

    public const SIZE_RING = 90;

    public const SIZE_SUNSET = 80;

    public const ELEMENTS_BAUHAUS = 4;

    public const ELEMENTS_MARBLE = 3;

    public const ELEMENTS_PIXEL = 64;

    public const ELEMENTS_RING = 5;

    public const ELEMENTS_SUNSET = 4;

    public const DEFAULT_COLORS = ['#0a0310', '#49007e', '#ff005b', '#ff7d10', '#ffb238'];

    public string $variant;

    public string $name;

    public ?array $colors;

    public bool $title;

    public bool $square;

    public int|string $size;

    public array $supportedVariants = ['bauhaus', 'beam', 'marble', 'pixel', 'ring', 'sunset'];

    public function __construct(
        string $variant = 'beam',
        string $name = '',
        ?array $colors = null,
        bool $title = false,
        bool $square = false,
        int|string $size = 40,
    ) {
        $this->variant = in_array($variant, $this->supportedVariants, true) ? $variant : 'beam';
        $this->name = $name;
        $this->colors = $colors ?? self::DEFAULT_COLORS;
        $this->title = $title;
        $this->square = $square;
        $this->size = $size;
        $this->attributes = new \Illuminate\View\ComponentAttributeBag;
    }

    public function render(): View
    {
        return match ($this->variant) {
            'bauhaus' => $this->renderBauhaus(),
            'beam' => $this->renderBeam(),
            'marble' => $this->renderMarble(),
            'pixel' => $this->renderPixel(),
            'ring' => $this->renderRing(),
            'sunset' => $this->renderSunset(),
        };
    }

    private function renderBauhaus(): View
    {
        $numFromName = $this->hash($this->name);
        $range = count($this->colors);
        $maskId = $this->generateMaskId($numFromName);

        $elementsProperties = [];
        for ($i = 0; $i < self::ELEMENTS_BAUHAUS; $i++) {
            $elementsProperties[] = [
                'color' => $this->colors[$this->modulus((int) ($numFromName + $i), $range)],
                'translateX' => $this->unit((int) ($numFromName * ($i + 1)), (int) (self::SIZE_BAUHAUS / 2 - ($i + 17)), 1),
                'translateY' => $this->unit((int) ($numFromName * ($i + 1)), (int) (self::SIZE_BAUHAUS / 2 - ($i + 17)), 2),
                'rotate' => $this->unit((int) ($numFromName * ($i + 1)), 360),
                'isSquare' => $this->isEvenBit($numFromName, 2),
            ];
        }

        return view('components.boring-avatar.bauhaus', $this->getViewData([
            'baseSize' => self::SIZE_BAUHAUS,
            'maskId' => $maskId,
            'elementsProperties' => $elementsProperties,
        ]));
    }

    private function renderBeam(): View
    {
        $numFromName = $this->hash($this->name);
        $range = count($this->colors);
        $maskId = $this->generateMaskId($numFromName);

        $wrapperColor = $this->colors[$this->modulus((int) $numFromName, $range)];

        $preTranslateX = $this->unit($numFromName, 10, 1);
        $wrapperTranslateX = $this->calculateTranslation($preTranslateX, (int) (self::SIZE_BEAM / 9));

        $preTranslateY = $this->unit($numFromName, 10, 2);
        $wrapperTranslateY = $this->calculateTranslation($preTranslateY, (int) (self::SIZE_BEAM / 9));

        $faceData = [
            'wrapperColor' => $wrapperColor,
            'faceColor' => $this->getContrastColor($wrapperColor),
            'backgroundColor' => $this->colors[$this->modulus((int) ($numFromName + 13), $range)],
            'wrapperTranslateX' => $wrapperTranslateX,
            'wrapperTranslateY' => $wrapperTranslateY,
            'wrapperRotate' => $this->unit($numFromName, 360),
            'wrapperScale' => 1 + $this->unit($numFromName, self::SIZE_BEAM / 12) / 10,
            'isMouthOpen' => $this->isEvenBit($numFromName, 2),
            'isCircle' => $this->isEvenBit($numFromName, 1),
            'eyeSpread' => $this->unit($numFromName, 5),
            'mouthSpread' => $this->unit($numFromName, 3),
            'faceRotate' => $this->unit($numFromName, 10, 3),
            'faceTranslateX' => $this->calculateFaceTranslation($wrapperTranslateX, self::SIZE_BEAM / 6, $numFromName, 8, 1),
            'faceTranslateY' => $this->calculateFaceTranslation($wrapperTranslateY, self::SIZE_BEAM / 6, $numFromName, 7, 2),
        ];

        $mouthPath = $this->generateMouthPath($faceData);

        return view('components.boring-avatar.beam', $this->getViewData([
            'baseSize' => self::SIZE_BEAM,
            'data' => $faceData,
            'mouthPath' => $mouthPath,
            'maskId' => $maskId,
        ]));
    }

    private function renderMarble(): View
    {
        $numFromName = $this->hash($this->name);
        $range = count($this->colors);
        $maskId = $this->generateMaskId($numFromName);
        $filterId = $this->generateFilterId($numFromName);

        $elementsProperties = [];
        for ($i = 0; $i < self::ELEMENTS_MARBLE; $i++) {
            $elementsProperties[] = [
                'color' => $this->colors[$this->modulus((int) ($numFromName + $i), $range)],
                'translateX' => $this->unit((int) ($numFromName * ($i + 1)), (int) (self::SIZE_MARBLE / 10), 1),
                'translateY' => $this->unit((int) ($numFromName * ($i + 1)), (int) (self::SIZE_MARBLE / 10), 2),
                'scale' => 1.2 + $this->unit((int) ($numFromName * ($i + 1)), (int) (self::SIZE_MARBLE / 20)) / 10,
                'rotate' => $this->unit((int) ($numFromName * ($i + 1)), 360, 1),
            ];
        }

        return view('components.boring-avatar.marble', $this->getViewData([
            'baseSize' => self::SIZE_MARBLE,
            'elementsProperties' => $elementsProperties,
            'filterId' => $filterId,
            'maskId' => $maskId,
        ]));
    }

    private function renderPixel(): View
    {
        $numFromName = $this->hash($this->name);
        $range = count($this->colors);
        $maskId = $this->generateMaskId($numFromName);

        $pixelColors = [];
        for ($i = 0; $i < self::ELEMENTS_PIXEL; $i++) {
            $pixelColors[] = $this->colors[$this->modulus($numFromName % ($i + 1), $range)];
        }

        $positions = $this->generatePixelPositions();

        return view('components.boring-avatar.pixel', $this->getViewData([
            'baseSize' => self::SIZE_PIXEL,
            'pixelColors' => $pixelColors,
            'positions' => $positions,
            'maskId' => $maskId,
        ]));
    }

    private function renderRing(): View
    {
        $numFromName = $this->hash($this->name);
        $range = count($this->colors);
        $maskId = $this->generateMaskId($numFromName);

        $selectedColors = [];
        for ($i = 0; $i < self::ELEMENTS_RING; $i++) {
            $selectedColors[] = $this->colors[$this->modulus((int) ($numFromName + $i), $range)];
        }

        $colorsList = [
            $selectedColors[0],
            $selectedColors[1],
            $selectedColors[1],
            $selectedColors[2],
            $selectedColors[3],
            $selectedColors[3],
            $selectedColors[0],
            $selectedColors[4],
            $selectedColors[3],
        ];

        return view('components.boring-avatar.ring', $this->getViewData([
            'baseSize' => self::SIZE_RING,
            'colorsList' => $colorsList,
            'maskId' => $maskId,
        ]));
    }

    private function renderSunset(): View
    {
        $numFromName = $this->hash($this->name);
        $range = count($this->colors);
        $maskId = $this->generateMaskId($numFromName);

        $colorsList = [];
        for ($i = 0; $i < self::ELEMENTS_SUNSET; $i++) {
            $colorsList[] = $this->colors[$this->modulus((int) ($numFromName + $i), $range)];
        }

        $nameWithoutSpace = $this->sanitizeName($this->name);
        $gradient0Id = "gradient-paint0-linear-{$nameWithoutSpace}";
        $gradient1Id = "gradient-paint1-linear-{$nameWithoutSpace}";

        return view('components.boring-avatar.sunset', $this->getViewData([
            'baseSize' => self::SIZE_SUNSET,
            'colorsList' => $colorsList,
            'nameWithoutSpace' => $nameWithoutSpace,
            'gradient0Id' => $gradient0Id,
            'gradient1Id' => $gradient1Id,
            'maskId' => $maskId,
        ]));
    }

    private function getViewData(array $additionalData): array
    {
        return array_merge($additionalData, [
            'name' => $this->name,
            'title' => $this->title,
            'square' => $this->square,
            'size' => $this->size,
            'attributes' => $this->attributes,
        ]);
    }

    private function generateMaskId(int $numFromName): string
    {
        $sanitizedName = $this->sanitizeName($this->name);

        return "{$this->variant}-mask-{$sanitizedName}-{$numFromName}";
    }

    private function generateFilterId(int $numFromName): string
    {
        $sanitizedName = $this->sanitizeName($this->name);

        return "marble-filter-{$sanitizedName}-{$numFromName}";
    }

    private function sanitizeName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $name);
    }

    private function calculateTranslation(int $preTranslate, int $threshold): int
    {
        if ($preTranslate < 5) {
            return $preTranslate + $threshold;
        }

        return $preTranslate;
    }

    private function calculateFaceTranslation(int $wrapperTranslation, int $threshold, int $numFromName, int $unitRange, int $unitIndex): int
    {
        if ($wrapperTranslation > $threshold) {
            return $wrapperTranslation / 2;
        }

        return $this->unit($numFromName, $unitRange, $unitIndex);
    }

    private function generateMouthPath(array $faceData): string
    {
        $yPosition = 19 + $faceData['mouthSpread'];

        if ($faceData['isMouthOpen']) {
            return "M15 {$yPosition}c2 1 4 1 6 0";
        }

        return "M13,{$yPosition} a1,0.75 0 0,0 10,0";
    }

    private function generatePixelPositions(): array
    {
        $positions = [];
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $positions[] = [$col * 10, $row * 10];
            }
        }

        return $positions;
    }

    public function hash(string $str): int
    {
        $hash = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $character = ord($str[$i]);
            $hash = (($hash << 5) - $hash) + $character;
            $hash = $hash & $hash;
        }

        return abs($hash);
    }

    public function contrast(string $color): string
    {
        return $this->getContrastColor($color);
    }

    public function randomColor(int $numFromName, array $colors, int $range): string
    {
        return $colors[$this->modulus($numFromName, $range)];
    }

    public function unit(int $numFromName, int $range, ?int $index = null): int
    {
        $value = $numFromName % $range;

        if ($index !== null && (($this->digit($numFromName, $index) % 2) === 0)) {
            return -$value;
        }

        return $value;
    }

    public function digit(int $numFromName, int $index): int
    {
        return (int) ($numFromName / pow(10, $index)) % 10;
    }

    public function isEvenBit(int $numFromName, int $index): bool
    {
        return ! (($this->digit($numFromName, $index) % 2));
    }

    public function boolean(int $numFromName, int $index): bool
    {
        return $this->isEvenBit($numFromName, $index);
    }

    public function modulus(int $numFromName, int $range): int
    {
        return abs($numFromName % $range);
    }

    private function getContrastColor(string $hexColor): string
    {
        $hexColor = ltrim($hexColor, '#');
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $yiq >= 128 ? '#000000' : '#FFFFFF';
    }
}
