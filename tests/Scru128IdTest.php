<?php

use GrantHolle\Scru128\Scru128Id;

it('encodes and decodes the spec example', function () {
    $id = Scru128Id::fromHex('017fef39c2641ba56a9483188841e05a');

    expect((string) $id)->toBe('0372ijojuxuhjsfkeryi2mrtm')
        ->and(Scru128Id::fromString('0372IJOJUXUHJSFKERYI2MRTM')->bytes)->toBe($id->bytes)
        ->and($id->toHex())->toBe('017fef39c2641ba56a9483188841e05a');
});

it('handles the bounds', function () {
    $max = str_repeat("\xff", 16);

    expect((string) new Scru128Id(str_repeat("\0", 16)))->toBe(str_repeat('0', 25))
        ->and((string) new Scru128Id($max))->toBe('f5lxx1zz5pnorynqglhzmsp33')
        ->and(Scru128Id::fromString('f5lxx1zz5pnorynqglhzmsp33')->bytes)->toBe($max)
        ->and(fn () => Scru128Id::fromString('f5lxx1zz5pnorynqglhzmsp34'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Scru128Id::fromString(str_repeat('z', 25)))->toThrow(InvalidArgumentException::class);
});

it('round trips random values and splits fields', function () {
    for ($i = 0; $i < 100; $i++) {
        $ts = random_int(0, Scru128Id::MAX_TIMESTAMP);
        $hi = random_int(0, 0xFFFFFF);
        $lo = random_int(0, 0xFFFFFF);
        $entropy = random_int(0, 0xFFFFFFFF);
        $id = Scru128Id::fromFields($ts, $hi, $lo, $entropy);

        expect($id->timestamp())->toBe($ts)
            ->and($id->counterHi())->toBe($hi)
            ->and($id->counterLo())->toBe($lo)
            ->and($id->entropy())->toBe($entropy)
            ->and(Scru128Id::fromString((string) $id)->bytes)->toBe($id->bytes)
            ->and(Scru128Id::fromHex($id->toHex())->bytes)->toBe($id->bytes);
    }
});

it('sorts chronologically as bytes and as text', function () {
    $a = Scru128Id::fromFields(1, 0xFFFFFF, 0xFFFFFF, 0xFFFFFFFF);
    $b = Scru128Id::fromFields(2, 0, 0, 0);
    $c = Scru128Id::fromFields(2, 0, 1, 0);

    expect($a->bytes <=> $b->bytes)->toBe(-1)
        ->and($b->bytes <=> $c->bytes)->toBe(-1)
        ->and(strcmp((string) $a, (string) $b))->toBeLessThan(0)
        ->and(strcmp((string) $b, (string) $c))->toBeLessThan(0);
});

it('rejects invalid input', function (Closure $fn) {
    expect($fn)->toThrow(InvalidArgumentException::class);
})->with([
    fn () => new Scru128Id(str_repeat("\0", 15)),
    fn () => new Scru128Id(str_repeat("\0", 17)),
    fn () => Scru128Id::fromString(str_repeat('0', 24)),
    fn () => Scru128Id::fromString(str_repeat('0', 26)),
    fn () => Scru128Id::fromString(str_repeat('0', 24).'-'),
    fn () => Scru128Id::fromHex(str_repeat('0', 31)),
    fn () => Scru128Id::fromHex(str_repeat('0', 31).'g'),
    fn () => Scru128Id::fromFields(-1, 0, 0, 0),
    fn () => Scru128Id::fromFields(Scru128Id::MAX_TIMESTAMP + 1, 0, 0, 0),
    fn () => Scru128Id::fromFields(0, 0x1000000, 0, 0),
    fn () => Scru128Id::fromFields(0, 0, 0x1000000, 0),
    fn () => Scru128Id::fromFields(0, 0, 0, 0x100000000),
]);
