<?php

namespace GrantHolle\Scru128;

class Scru128Generator
{
    /** Clock rollback tolerated before resetting the generator state, in milliseconds. */
    private const int ROLLBACK_ALLOWANCE = 10_000;

    protected int $timestamp = 0;

    protected int $counterHi = 0;

    protected int $counterLo = 0;

    /** Timestamp at which counter_hi was last renewed. */
    protected int $tsCounterHi = 0;

    public function generate(): Scru128Id
    {
        return $this->generateAt((int) floor(microtime(true) * 1000));
    }

    /** @internal exposed for tests; $timestamp is in milliseconds */
    public function generateAt(int $timestamp): Scru128Id
    {
        if ($timestamp > $this->timestamp) {
            $this->timestamp = $timestamp;
            $this->counterLo = random_int(0, 0xFFFFFF);
        } elseif ($timestamp + self::ROLLBACK_ALLOWANCE > $this->timestamp) {
            // small rollback (or counter-overflow drift): keep prev timestamp, advance counter
            $this->counterLo++;

            if ($this->counterLo > 0xFFFFFF) {
                $this->counterLo = 0;
                $this->counterHi++;

                if ($this->counterHi > 0xFFFFFF) {
                    $this->counterHi = 0;
                    $this->timestamp++;
                    $this->counterLo = random_int(0, 0xFFFFFF);
                }
            }
        } else {
            // ponytail: large rollback resets state (spec option 2) rather than throwing
            $this->tsCounterHi = 0;
            $this->timestamp = $timestamp;
            $this->counterLo = random_int(0, 0xFFFFFF);
        }

        if ($this->timestamp - $this->tsCounterHi >= 1000 || $this->tsCounterHi < 1) {
            $this->tsCounterHi = $this->timestamp;
            $this->counterHi = random_int(0, 0xFFFFFF);
        }

        return Scru128Id::fromFields($this->timestamp, $this->counterHi, $this->counterLo, random_int(0, 0xFFFFFFFF));
    }
}
