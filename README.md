# SCRU128 for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/grantholle/scru128.svg?style=flat-square)](https://packagist.org/packages/grantholle/scru128)
[![Tests](https://github.com/grantholle/scru128/actions/workflows/run-tests.yml/badge.svg)](https://github.com/grantholle/scru128/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/grantholle/scru128.svg?style=flat-square)](https://packagist.org/packages/grantholle/scru128)

A PHP implementation of [SCRU128](https://github.com/scru128/spec): Sortable, Clock and Random number-based Unique identifiers.

- 128-bit, fits in a `BINARY(16)` / `UUID` column
- Sortable by generation time, both as bytes and as text
- 25-digit case-insensitive Base36 text (`0372ijojuxuhjsfkeryi2mrtm`)
- Millisecond timestamp resolution, usable until year 10889
- No node ID or coordination required; 80 bits of counter and entropy per millisecond

If you'd prefer 64-bit storage and can coordinate node IDs, use [SCRU64](https://github.com/grantholle/scru64) instead.

## Installation

```bash
composer require grantholle/scru128
```

Requires PHP 8.4+ on a 64-bit platform.

## Usage

### Global generator

```php
use GrantHolle\Scru128\Scru128;

$id = Scru128::generate();

echo $id;          // "03gvsffv2ksajblj0y6czqvdd"
echo $id->toHex(); // "01996d1a5c2c3f8a2b7c0d1e2f3a4b5c"
```

No configuration is needed. To swap the shared generator (e.g. in tests):

```php
use GrantHolle\Scru128\Scru128;
use GrantHolle\Scru128\Scru128Generator;

Scru128::setGenerator(new Scru128Generator);
```

### Explicit generator

```php
use GrantHolle\Scru128\Scru128Generator;

$generator = new Scru128Generator;

$id = $generator->generate();
```

`generate()` never blocks or throws. IDs from a single generator are strictly increasing; when the counters overflow within a millisecond the timestamp is advanced by one. Clock rollbacks of up to 10 seconds are absorbed; a larger rollback resets the generator to the new clock value.

### Working with IDs

```php
use GrantHolle\Scru128\Scru128Id;

$id = Scru128Id::fromString('0372ijojuxuhjsfkeryi2mrtm');   // case-insensitive
$id = Scru128Id::fromHex('017fef39c2641ba56a9483188841e05a');
$id = new Scru128Id($bytes);                                 // 16-byte binary string
$id = Scru128Id::fromFields(timestamp: 1648044214741, counterHi: 0x1ba56a, counterLo: 0x948318, entropy: 0x8841e05a);

(string) $id;      // "0372ijojuxuhjsfkeryi2mrtm"
$id->toHex();      // "017fef39c2641ba56a9483188841e05a"
$id->bytes;        // 16 raw bytes, big-endian
$id->timestamp();  // Unix time in milliseconds
$id->counterHi();  // 24-bit
$id->counterLo();  // 24-bit
$id->entropy();    // 32-bit
```

Store `$id->bytes` in a `BINARY(16)` column or `(string) $id` in a `CHAR(25)` column; both sort chronologically. In PHP, `$a->bytes <=> $b->bytes` and `strcmp((string) $a, (string) $b)` give the same order.

## Testing

```bash
composer test
composer analyse
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Grant Holle](https://github.com/grantholle)
- [SCRU128 specification](https://github.com/scru128/spec) by LiosK
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
