# Getting Started

## What is Exact?

Exact provides small PHP types for representing domain states explicitly. Instead of passing a nullable value or an unstructured error through several functions, you can use `Option`, `Result`, and related types and transform them with a consistent API.

## A Simple Example

```php
<?php

use Exact\Data\Option\Option;
use Exact\Data\Result\Result;

$email = Option::from('USER@example.com')
    ->map(static fn (string $value): string => strtolower($value))
    ->getOrElse('noreply@example.com');

$total = Result::ok(42)
    ->map(static fn (int $value): int => $value + 8)
    ->getOrElse(0);
```

The final values are `user@example.com` and `50`.

## Understanding the Example

`Option::from()` turns `null` into `None` and every other value into `Some`. `map()` transforms the contained value only when it exists. `getOrElse()` extracts the value or supplies a fallback.

`Result::ok()` creates a successful result. An `Ok` value is transformed by `map()`, while an `Err` value passes through unchanged. Use `Result::err()` when the failure information matters.

## Using Option

Use `Option` when absence is expected and you do not need to explain why a value is absent:

```php
$displayName = Option::from($userName)
    ->filter(static fn (string $name): bool => $name !== '')
    ->getOrElse('Anonymous');
```

See [Option](fundamentals/option.md) for chaining and branching operations.

## Using Result

Use `Result` when an operation can fail and the error value should travel with the result:

```php
$message = Result::ok(10)
    ->map(static fn (int $value): int => $value * 2)
    ->fold(
        static fn (mixed $error): string => "Failed: {$error}",
        static fn (int $value): string => "Value: {$value}",
    );
```

See [Result](fundamentals/result.md) and [Error Handling](patterns/error-handling.md).

## Composing Operations

Most Exact transformations return a new value, so workflows can be chained:

```php
$value = Option::some(5)
    ->map(static fn (int $number): int => $number + 1)
    ->filter(static fn (int $number): bool => $number > 0)
    ->getOrElse(0);
```

For workflows that cross several types, continue with [Domain Modeling](patterns/domain-modeling.md) and [Composition](functional/composition.md).

## Where to Go Next

- [Either](fundamentals/either.md) for two-sided outcomes.
- [Validated](fundamentals/validated.md) for combining validation results.
- [Collections Overview](collections/overview.md) for immutable-style collection transformations.