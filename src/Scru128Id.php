<?php

namespace GrantHolle\Scru128;

use InvalidArgumentException;
use Stringable;

/**
 * A SCRU128 ID: 128 bits laid out as `timestamp(48) | counter_hi(24) | counter_lo(24) | entropy(32)`.
 *
 * Held as a 16-byte big-endian binary string, so `$a->bytes <=> $b->bytes` sorts chronologically.
 */
readonly class Scru128Id implements Stringable
{
    /** 2^48 - 1 */
    public const int MAX_TIMESTAMP = 0xFFFFFFFFFFFF;

    private const string DIGITS = '0123456789abcdefghijklmnopqrstuvwxyz';

    public function __construct(public string $bytes)
    {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgumentException('SCRU128 ID must be exactly 16 bytes');
        }
    }

    public static function fromFields(int $timestamp, int $counterHi, int $counterLo, int $entropy): self
    {
        if ($timestamp < 0 || $timestamp > self::MAX_TIMESTAMP) {
            throw new InvalidArgumentException("timestamp out of range: {$timestamp}");
        }

        if ($counterHi < 0 || $counterHi > 0xFFFFFF) {
            throw new InvalidArgumentException("counter_hi out of range: {$counterHi}");
        }

        if ($counterLo < 0 || $counterLo > 0xFFFFFF) {
            throw new InvalidArgumentException("counter_lo out of range: {$counterLo}");
        }

        if ($entropy < 0 || $entropy > 0xFFFFFFFF) {
            throw new InvalidArgumentException("entropy out of range: {$entropy}");
        }

        return new self(
            substr(pack('J', $timestamp), 2)
            .substr(pack('N', $counterHi), 1)
            .substr(pack('N', $counterLo), 1)
            .pack('N', $entropy)
        );
    }

    public static function fromString(string $text): self
    {
        $text = strtolower($text);

        if (! preg_match('/^[0-9a-z]{25}$/', $text)) {
            throw new InvalidArgumentException("invalid SCRU128 string: {$text}");
        }

        // ponytail: schoolbook base36 -> base256 over a byte array; no gmp/bcmath needed
        $bytes = array_fill(0, 16, 0);

        foreach (str_split($text) as $char) {
            $carry = (int) strpos(self::DIGITS, $char);

            for ($i = 15; $i >= 0; $i--) {
                $carry += $bytes[$i] * 36;
                $bytes[$i] = $carry & 0xFF;
                $carry >>= 8;
            }

            if ($carry > 0) {
                throw new InvalidArgumentException("SCRU128 string out of range: {$text}");
            }
        }

        return new self(pack('C*', ...$bytes));
    }

    public static function fromHex(string $hex): self
    {
        if (! preg_match('/^[0-9a-f]{32}$/i', $hex)) {
            throw new InvalidArgumentException("invalid SCRU128 hex: {$hex}");
        }

        return new self((string) hex2bin($hex));
    }

    /** 48-bit millisecond-precision Unix timestamp. */
    public function timestamp(): int
    {
        return $this->int(0, 6);
    }

    /** 24-bit counter_hi field. */
    public function counterHi(): int
    {
        return $this->int(6, 3);
    }

    /** 24-bit counter_lo field. */
    public function counterLo(): int
    {
        return $this->int(9, 3);
    }

    /** 32-bit entropy field. */
    public function entropy(): int
    {
        return $this->int(12, 4);
    }

    /** 32-digit lowercase hex representation. */
    public function toHex(): string
    {
        return bin2hex($this->bytes);
    }

    /** 25-digit lowercase base36 representation. */
    public function __toString(): string
    {
        $digits = array_fill(0, 25, 0);

        for ($b = 0; $b < 16; $b++) {
            $carry = ord($this->bytes[$b]);

            for ($i = 24; $i >= 0; $i--) {
                $carry += $digits[$i] << 8;
                $digits[$i] = $carry % 36;
                $carry = intdiv($carry, 36);
            }
        }

        return implode('', array_map(fn (int $d) => self::DIGITS[$d], $digits));
    }

    /** Big-endian unsigned int from $length bytes at $offset. */
    private function int(int $offset, int $length): int
    {
        $value = 0;

        for ($i = 0; $i < $length; $i++) {
            $value = $value << 8 | ord($this->bytes[$offset + $i]);
        }

        return $value;
    }
}
