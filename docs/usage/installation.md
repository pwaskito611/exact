# Installation

Exact is a PHP library that requires PHP 8.2 or later. It has no runtime dependencies.

Install it with Composer:

```bash
composer require pandu/exact
```

Exact uses the `Exact\` namespace. Composer loads it from the package's `src/` directory.

## Development

To work on a local checkout:

```bash
composer install
vendor/bin/phpunit
```

Run the configured PHPUnit suite with `composer test`.

## Next Steps

- Start with [Getting Started](getting-started.md).
- Start with [Option](fundamentals/option.md), [Either](fundamentals/either.md), and [Validated](fundamentals/validated.md).