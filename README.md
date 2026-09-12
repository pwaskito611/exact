# Exact

Exact adalah library PHP untuk domain modeling yang terinspirasi oleh pendekatan Scala. Library ini fokus pada immutable value, explicit domain semantics, dan composition untuk operasi bisnis yang lebih aman.

## Fitur utama

- `Option`
- `Result`
- `Either`
- `Validated`
- immutable collection
- value object style
- composable functional API

## Instalasi

```bash
composer require exact/exact
```

## Quick start

```php
<?php

use Exact\Option;
use Exact\Result;

$email = Option::from('user@example.com')
    ->map(static fn (string $value) => strtolower($value));

$result = Result::ok(42)
    ->map(static fn (int $value) => $value + 8);

var_dump($email->getOrElse('noreply@example.com'));
var_dump($result->getOrElse(0));
```

## Dokumentasi

- [docs/usage/README.md](docs/usage/README.md)
- [docs/teknikal/README.md](docs/teknikal/README.md)

## Pengembangan

```bash
composer install
./vendor/bin/phpunit
```
