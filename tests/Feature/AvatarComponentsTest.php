<?php

use App\View\Components\BoringAvatar;

it('bauhaus avatar renders for valid name', function () {
    $html = (new BoringAvatar('bauhaus', 'Jane Smith'))->render()->toHtml();

    expect($html)->toContain('<svg')
        ->and($html)->toContain('viewBox="0 0 80 80"')
        ->and($html)->toContain('role="img"');
});

it('beam avatar renders for valid name', function () {
    $html = (new BoringAvatar('beam', 'John Doe'))->render()->toHtml();

    expect($html)->toContain('<svg')
        ->and($html)->toContain('viewBox="0 0 36 36"')
        ->and($html)->toContain('role="img"');
});

it('marble avatar renders for valid name', function () {
    $html = (new BoringAvatar('marble', 'Alice'))->render()->toHtml();

    expect($html)->toContain('<svg')
        ->and($html)->toContain('viewBox="0 0 80 80"')
        ->and($html)->toContain('role="img"');
});

it('pixel avatar renders for valid name', function () {
    $html = (new BoringAvatar('pixel', 'Bob'))->render()->toHtml();

    expect($html)->toContain('<svg')
        ->and($html)->toContain('viewBox="0 0 80 80"')
        ->and($html)->toContain('role="img"');
});

it('ring avatar renders for valid name', function () {
    $html = (new BoringAvatar('ring', 'Charlie'))->render()->toHtml();

    expect($html)->toContain('<svg')
        ->and($html)->toContain('viewBox="0 0 90 90"')
        ->and($html)->toContain('role="img"');
});

it('sunset avatar renders for valid name', function () {
    $html = (new BoringAvatar('sunset', 'Diana'))->render()->toHtml();

    expect($html)->toContain('<svg')
        ->and($html)->toContain('viewBox="0 0 80 80"')
        ->and($html)->toContain('role="img"');
});

it('variant can be set through constructor', function () {
    $component = new BoringAvatar('bauhaus');
    $component->render();

    expect($component->variant)->toBe('bauhaus');
});

it('unsupported variant defaults to beam', function () {
    $component = new BoringAvatar('invalid-variant');
    $component->render();

    expect($component->variant)->toBe('beam');
});

it('square can be set through constructor', function () {
    $component = new BoringAvatar('beam', '', null, false, true);
    $component->render();

    expect($component->square)->toBeTrue();
});

it('custom colors can be passed', function () {
    $customColors = ['#ff0000', '#00ff00', '#0000ff'];
    $component = new BoringAvatar('beam', '', $customColors);
    $component->render();

    expect($component->colors)->toBe($customColors);
});

it('different names generate different hashes', function () {
    $component = new BoringAvatar;
    $hash1 = $component->hash('Alice');
    $hash2 = $component->hash('Bob');

    expect($hash1)->not->toBe($hash2);
});

it('same names generate same hashes', function () {
    $component = new BoringAvatar;
    $name = 'Test User';
    $hash1 = $component->hash($name);
    $hash2 = $component->hash($name);

    expect($hash1)->toBe($hash2);
});

it('contrast returns white for dark colors', function () {
    $component = new BoringAvatar;

    expect($component->contrast('#000000'))->toBe('#FFFFFF')
        ->and($component->contrast('#1e293b'))->toBe('#FFFFFF');
});

it('contrast returns black for light colors', function () {
    $component = new BoringAvatar;

    expect($component->contrast('#ffffff'))->toBe('#000000')
        ->and($component->contrast('#e2e8f0'))->toBe('#000000');
});

it('randomColor selects color from array', function () {
    $component = new BoringAvatar;
    $colors = ['#ff0000', '#00ff00', '#0000ff'];
    $color = $component->randomColor(0, $colors, count($colors));

    expect(in_array($color, $colors))->toBeTrue();
});

it('randomColor is deterministic for same input', function () {
    $component = new BoringAvatar;
    $colors = ['#ff0000', '#00ff00', '#0000ff'];
    $color1 = $component->randomColor(10, $colors, count($colors));
    $color2 = $component->randomColor(10, $colors, count($colors));

    expect($color1)->toBe($color2);
});

it('digit extracts correct digit', function () {
    $component = new BoringAvatar;

    expect($component->digit(1234, 0))->toBe(4)
        ->and($component->digit(1234, 1))->toBe(3)
        ->and($component->digit(1234, 2))->toBe(2)
        ->and($component->digit(1234, 3))->toBe(1);
});

it('boolean returns correct boolean based on digit parity', function () {
    $component = new BoringAvatar;

    expect($component->boolean(1234, 0))->toBeTrue()
        ->and($component->boolean(1235, 0))->toBeFalse()
        ->and($component->boolean(1236, 0))->toBeTrue();
});

it('unit returns correct unit value', function () {
    $component = new BoringAvatar;

    expect($component->unit(10, 5))->toBe(0)
        ->and($component->unit(15, 10))->toBe(5);
});

it('unit negates value when index digit is even', function () {
    $component = new BoringAvatar;

    expect($component->unit(1234, 10, 0))->toBe(-4)
        ->and($component->unit(1234, 10, 1))->toBe(4);
});

it('modulus returns correct modulo', function () {
    $component = new BoringAvatar;

    expect($component->modulus(10, 3))->toBe(1)
        ->and($component->modulus(15, 5))->toBe(0)
        ->and($component->modulus(7, 4))->toBe(3);
});
