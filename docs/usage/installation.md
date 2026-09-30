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

The repository does not define a `composer test` script; run PHPUnit directly with the configured test suite.

## Next Steps

- Start with [Getting Started](getting-started.md).
- Choose between [Option](fundamentals/option.md) and [Result](fundamentals/result.md).