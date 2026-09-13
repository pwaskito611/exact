# Exact

Exact is a Scala-inspired domain modeling library for PHP. It provides explicit data types, immutable-style collections, value abstractions, and composable functional utilities.

## Quick Introduction

Exact is designed for PHP code that benefits from making domain states explicit instead of representing them only with nullable values, exceptions, or loosely structured arrays. Its data types model alternatives such as an optional value, a success or error, and a left or right branch.

The library focuses on type-oriented programming, immutability in its data and collection transformations, and small operations that can be combined through mapping, folding, pattern matching, and function composition.

## Core Features

- **Data Types** — `Option`, `Result`, `Either`, `Validated`, tuples, and pairs for explicit domain values and outcomes.
- **Algebraic Data Types** — named `Variant` values with `PatternMatch` and `Matcher` for case-based handling.
- **Value Modeling** — `Newtype` for immutable wrapped values and `ValueObject` as a base abstraction for equality-based domain values.
- **Collections** — `Seq`, `NonEmptySeq`, `Map`, `Set`, and lazy `LazySeq` with transformation and conversion operations.
- **Function Composition** — right-to-left composition, left-to-right piping, currying, partial application, and immutable value pipelines.

## Example

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

`Option::from(null)` creates `None`; a non-null value creates `Some`. `Result::map()` transforms an `Ok` value while preserving an `Err` branch.

## Installation

Exact requires PHP 8.2 or later and has no runtime dependencies.

```bash
composer require pandu/exact
```

## Documentation

- [Usage documentation](docs/usage/)
- [Technical documentation](docs/technical/)

The documentation directories are present in the repository; detailed documentation files are not currently included.

## Project Status

The repository includes PHPUnit tests and is still evolving. The public API may change as the library develops, and no separate backward-compatibility or production-readiness guarantee is declared.

## License

MIT, as declared in `composer.json`.
