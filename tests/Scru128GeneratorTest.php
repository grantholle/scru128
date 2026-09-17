<?php

use GrantHolle\Scru128\Scru128;
use GrantHolle\Scru128\Scru128Generator;

it('generates unique monotonic ids', function () {
    $gen = new Scru128Generator;
    $prev = $gen->generate();
    $seen = [$prev->bytes => true];

    for ($i = 0; $i < 10_000; $i++) {
        $id = $gen->generate();
        expect($id->bytes <=> $prev->bytes)->toBe(1)
            ->and($seen)->not->toHaveKey($id->bytes);
        $seen[$id->bytes] = true;
        $prev = $id;
    }
});

it('increments counter_lo within the same millisecond', function () {
    $gen = new Scru128Generator;
    $ts = 1_600_000_000_000;
    $a = $gen->generateAt($ts);
    $b = $gen->generateAt($ts);

    expect($b->timestamp())->toBe($ts)
        ->and($b->counterHi())->toBe($a->counterHi())
        ->and($b->counterLo())->toBe($a->counterLo() + 1)
        ->and($b->entropy())->not->toBe($a->entropy());
});

it('advances timestamp on counter overflow', function () {
    $gen = new class extends Scru128Generator
    {
        public function seed(int $ts): void
        {
            $this->timestamp = $ts;
            $this->tsCounterHi = $ts;
            $this->counterHi = 0xFFFFFF;
            $this->counterLo = 0xFFFFFF;
        }
    };
    $ts = 1_600_000_000_000;
    $gen->seed($ts);
    $id = $gen->generateAt($ts);

    expect($id->timestamp())->toBe($ts + 1)
        ->and($id->counterHi())->toBe(0);
});

it('renews counter_hi every second', function () {
    $gen = new Scru128Generator;
    $ts = 1_600_000_000_000;
    $a = $gen->generateAt($ts);
    $b = $gen->generateAt($ts + 999);
    $c = $gen->generateAt($ts + 1000);

    expect($b->counterHi())->toBe($a->counterHi())
        ->and($c->counterHi())->not->toBe($a->counterHi()); // 1 in 2^24 flake
});

it('tolerates small rollbacks and resets on large ones', function () {
    $gen = new Scru128Generator;
    $ts = 1_600_000_000_000;
    $a = $gen->generateAt($ts);
    $b = $gen->generateAt($ts - 9_999);
    $c = $gen->generateAt($ts - 10_000);

    expect($b->timestamp())->toBe($ts)
        ->and($b->bytes <=> $a->bytes)->toBe(1)
        ->and($c->timestamp())->toBe($ts - 10_000);
});

it('uses the global generator without configuration', function () {
    Scru128::setGenerator(null);
    $a = Scru128::generate();
    $b = Scru128::generate();
    expect($b->bytes <=> $a->bytes)->toBe(1);

    $custom = new Scru128Generator;
    Scru128::setGenerator($custom);
    expect(Scru128::generator())->toBe($custom);
    Scru128::setGenerator(null);
});
