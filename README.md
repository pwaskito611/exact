# Exact

Exact is a PHP data-modeling library focused on Algebraic Data Types, immutable collections, and explicit pattern matching.

## Quick Introduction

Exact helps PHP applications represent domain states explicitly instead of relying only on nullable values, exceptions, or loosely structured arrays. `Option`, `Either`, and `Validated` model distinct forms of alternatives and validation outcomes.

Its immutable collection transformations and named variants support predictable data processing and explicit case handling.

## Core Features

- **Types** — `Option`, `Either`, and `Validated` for optional values, alternatives, and validation outcomes; tuples and pairs for grouped values.
- **Algebraic Data Types** — named `Variant` values with `PatternMatch` and `Matcher` for case-based handling.
- **Immutable Collections** — `Vec`, `NonEmptyVec`, `LazySeq`, `NonEmptyLazySeq`, `Range`, `Deque`, `PriorityQueue`, `Map`, `NonEmptyMap`, `SortedMap`, `BiMap`, `MultiMap`, `Set`, `NonEmptySet`, `SortedSet`, and `MultiSet`.

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

`Type` contains `Option`, `Either`, `Validated`, and tuple types. `Collection` contains reusable eager and lazy data structures such as vectors, maps, sets, deques, and sequences. `Pattern` provides named variants and matching for handling domain cases, and `Exception` keeps library-specific exceptions in one place.

The test tree mirrors these source areas. `tests/Unit` checks individual types and collection operations, `tests/Feature` exercises behavior across public APIs, and `tests/Integration` verifies how abstractions work together in domain and composition flows. Usage guides and design rationale are separated under `docs/usage` and `docs/technical`, respectively.

## Example

```php
<?php

use Exact\Pattern\PatternMatch;
use Exact\Pattern\Variant;
use Exact\Type\Either\Either;
use Exact\Type\Option\Option;

$email = Option::from('USER@example.com')
    ->map(static fn (string $value): string => strtolower($value))
    ->getOrElse('noreply@example.com');

$delivery = Either::right('courier')
    ->map(static fn (string $method): string => strtoupper($method))
    ->getOrElse('PICKUP');

$label = PatternMatch::on(Variant::of('Paid', 'R-204'))
    ->case('Paid', static fn (string $receipt): string => "Paid: {$receipt}")
    ->default(static fn (mixed $payload, string $name): string => $name)
    ->run();
```

`Option::from(null)` creates `None`; a non-null value creates `Some`. `Either` carries one of two meaningful alternatives. `Variant` and `PatternMatch` make named domain cases explicit and dispatch them to matching handlers.

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

The repository includes PHPUnit tests and continues to evolve. Starting with v2, the public API is intended to remain stable, and breaking changes will be avoided whenever reasonably possible. Backward compatibility will be prioritized across v2 releases, with breaking changes introduced only when necessary and clearly documented.

## License

MIT, as declared in `composer.json`.
