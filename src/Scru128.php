<?php

namespace GrantHolle\Scru128;

/**
 * Global generator; SCRU128 needs no configuration.
 */
class Scru128
{
    private static ?Scru128Generator $generator = null;

    public static function generate(): Scru128Id
    {
        return self::generator()->generate();
    }

    public static function generator(): Scru128Generator
    {
        return self::$generator ??= new Scru128Generator;
    }

    public static function setGenerator(?Scru128Generator $generator): void
    {
        self::$generator = $generator;
    }
}
