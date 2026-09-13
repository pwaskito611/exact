# Installation

Exact is a PHP library that requires PHP 8.2 or later. It has no runtime dependencies.

Install it with Composer:

```bash
composer require exact/exact
```

Exact uses the `Exact\` namespace. Composer loads it from the package's `src/` directory.

## Development

To work on a local checkout:

```bash
composer install
composer test
```

`composer test` runs the PHPUnit suite.

## Next Steps

- Start with [Getting Started](getting-started.md).
- Choose between [Option](fundamentals/option.md) and [Result](fundamentals/result.md).