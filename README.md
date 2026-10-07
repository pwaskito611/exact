# Exact

Exact is a Scala-inspired domain modeling library for PHP. It provides explicit data types, immutable-style collections, value abstractions, and composable functional utilities.

## Quick Introduction

Exact is designed for PHP code that benefits from making domain states explicit instead of representing them only with nullable values, exceptions, or loosely structured arrays. Its data types model alternatives such as an optional value, a success or error, and a left or right branch.

The library focuses on type-oriented programming, immutability in its data and collection transformations, and small operations that can be combined through mapping, folding, pattern matching, and function composition.

## Core Features

- **Data Types** — `Option`, `Result`, `Either`, `Validated`, tuples, and pairs for explicit domain values and outcomes.
- **Algebraic Data Types** — named `Variant` values with `PatternMatch` and `Matcher` for case-based handling.
- **Collections** — `Vec`, `NonEmptyVec`, `Map`, `Set`, and lazy `LazySeq` with transformation and conversion operations.

## Folder Architecture

The source tree is organized by the role each abstraction plays in domain code:

```text
src/
├── Type/        Explicit domain types and algebraic data types
│   ├── Option/  Optional values: Option, Some, and None
│   ├── Either/  Left and right alternatives
│   ├── Validated/ Validation values that can accumulate errors
│   └── Tuple/   Fixed-arity tuple types
├── Collection/  Immutable and lazy collection abstractions
├── Pattern/     Variant values and pattern-matching support
└── Exception/   Library-specific exceptions
```

`Type` separates domain values from their operations: for example, `Option` and `Either` make alternative states explicit, while `Validated` represents accumulated validation outcomes. `Collection` contains reusable data structures such as vectors, maps, sets, deques, and lazy sequences. `Pattern` provides the matching mechanism for handling named variants, and `Exception` keeps library errors in one place.

The test tree mirrors these source areas. `tests/Unit` checks individual types and collection operations, `tests/Feature` exercises behavior across public APIs, and `tests/Integration` verifies how abstractions work together in domain and composition flows. Usage guides and design rationale are separated under `docs/usage` and `docs/technical`, respectively.

## Example

```php
<?php

use Exact\Type\Either\Either;
use Exact\Type\Option\Option;
use Exact\Type\Result\Result;

$email = Option::from('USER@example.com')
    ->map(static fn (string $value): string => strtolower($value))
    ->getOrElse('noreply@example.com');

$total = Result::ok(42)
    ->map(static fn (int $value): int => $value + 8)
    ->getOrElse(0);

$checkout = Either::right(250)
    ->map(static fn (int $value): int => $value + 50)
    ->getOrElse(0);
```

`Option::from(null)` creates `None`; a non-null value creates `Some`. `Result::map()` transforms an `Ok` value while preserving an `Err` branch. `Either` keeps a meaningful left/right alternative, which is useful when the domain distinguishes a business decision from a success path.

## Installation

Exact requires PHP 8.2 or later and has no runtime dependencies.

```bash
composer require pandu/exact
```

## Running Tests

Install the development dependencies and run the complete PHPUnit suite with:

```bash
composer install
composer test
```

## Documentation

- [Usage documentation](docs/usage/README.md)
- [Technical documentation](docs/technical/README.md)

## Project Status

The repository includes PHPUnit tests and is still evolving. The public API may change as the library develops, and no separate backward-compatibility or production-readiness guarantee is declared.

## License

MIT, as declared in `composer.json`.
