# Getting Started

## What is Exact?

Exact is a PHP data-modeling library focused on Algebraic Data Types, immutable collections, and explicit pattern matching. Its types help represent domain states and alternatives directly in PHP values.

## A Simple Example

```php
<?php

use Exact\Type\Option\Option;
use Exact\Type\Either\Either;
use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;

$email = Option::from('USER@example.com')
    ->map(static fn (string $value): string => strtolower($value))
    ->getOrElse('noreply@example.com');

$choice = Either::right(42)
    ->map(static fn (int $value): int => $value + 8)
    ->getOrElse(0);

$label = PatternMatch::on(Variant::of('Paid', 'R-204'))
    ->case('Paid', static fn (string $receipt): string => "Paid: {$receipt}")
    ->default(static fn (mixed $payload, string $name): string => $name)
    ->run();
```

The example normalizes an optional email, transforms the `Right` branch of an `Either`, and handles a named `Paid` variant explicitly.

## Understanding the Example

`Option::from()` turns `null` into `None` and every other value into `Some`. `map()` transforms the contained value only when it exists. `getOrElse()` extracts the value or supplies a fallback.

## Using Option and Either

Use `Option` when absence is expected and you do not need to explain why a value is absent:

```php
$displayName = Option::from($userName)
    ->filter(static fn (string $name): bool => $name !== '')
    ->getOrElse('Anonymous');
```

See [Option](fundamentals/option.md) for chaining and branching operations.

Use `Either` when both alternatives carry domain meaning. See [Either](fundamentals/either.md).

Most Exact transformations return a new value, so workflows can be chained:

```php
$value = Option::some(5)
    ->map(static fn (int $number): int => $number + 1)
    ->filter(static fn (int $number): bool => $number > 0)
    ->getOrElse(0);
```

For workflows that cross several types, continue with [Domain Modeling](patterns/domain-modeling.md).

## Where to Go Next

- [Either](fundamentals/either.md) for two-sided domain alternatives.
- [Validated](fundamentals/validated.md) for combining validation results.
- [Collections Overview](collections/overview.md) for immutable-style collection transformations.