# Exact

Exact is a data-oriented programming toolkit for PHP. It helps you model, transform, and compose data with Algebraic Data Types, immutable collections, explicit pattern matching, and typed in-memory queries.

## Quick Introduction

Exact gives PHP applications data types and operations for making domain states and data transformations explicit. `Option`, `Either`, and `Validated` model optional values, alternatives, and validation outcomes without relying only on nullable values, exceptions, or loosely structured arrays.

Its immutable eager and lazy collections support predictable transformations, while named variants and pattern matching make domain cases explicit. The separate Query module composes filtering, joins, projections, and aggregation over any iterable.

> **AI coding assistants:** Read [EXACT.md](EXACT.md) before using or changing Exact APIs. It is the concise agent guide to the library's conventions, API semantics, and lookup workflow.

## Core Features

- **Types** — `Option`, `Either`, and `Validated` for optional values, alternatives, and validation outcomes; tuples and pairs for grouped values.
- **Algebraic Data Types** — named `Variant` values with `PatternMatch` and `Matcher` for case-based handling.
- **Immutable Collections** — `Vec`, `NonEmptyVec`, `LazySeq`, `NonEmptyLazySeq`, `Range`, `Deque`, `PriorityQueue`, `Map`, `NonEmptyMap`, `SortedMap`, `BiMap`, `MultiMap`, `Set`, `NonEmptySet`, `SortedSet`, and `MultiSet`.
- **Composable Queries** — `Exact\Query\Query` builds typed in-memory pipelines for filtering, projection, joins, grouping, ordering, set operations, aggregation, and lazy execution.

## Query Example

Compose a join, filter, and projection into a typed in-memory pipeline:

```php
use Exact\Collection\Vec;
use Exact\Query\Query;

$orders = Vec::of(
    ['id' => 101, 'customerId' => 1, 'total' => 80],
    ['id' => 102, 'customerId' => 2, 'total' => 150],
);
$customers = Vec::of(
    ['id' => 1, 'name' => 'Ari'],
    ['id' => 2, 'name' => 'Bea'],
);

$report = Query::from($orders)
    ->innerJoin(
        $customers,
        static fn (array $order): int => $order['customerId'],
        static fn (array $customer): int => $customer['id'],
        static fn (array $order, array $customer): array => [
            'orderId' => $order['id'],
            'customer' => $customer['name'],
            'total' => $order['total'],
        ],
    )
    ->where(static fn (array $row): bool => $row['total'] >= 100)
    ->select(static fn (array $row): array => [
        'customer' => $row['customer'],
        'total' => $row['total'],
    ])
    ->execute();
```

`$report` is an eager `Vec` containing the projected rows. Use `executeLazy()` when a deferred sequence result is appropriate; grouping, sorting, and some joins still require materialization. The [Query guide](docs/usage/query.md) documents the full API and execution behavior.

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
├── Query/       Composable in-memory data processing pipelines
├── Pattern/     Variant values and pattern-matching support
└── Exception/   Library-specific exceptions
```

`Type` contains `Option`, `Either`, `Validated`, and tuple types. `Collection` contains reusable eager and lazy data structures such as vectors, maps, sets, deques, and sequences. `Query` composes any iterable collection without adding query-specific methods to collection classes. `Pattern` provides named variants and matching for handling domain cases, and `Exception` keeps library-specific exceptions in one place.

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
- [Query API guide](docs/usage/query.md)

## Project Status

The repository includes PHPUnit tests and continues to evolve. Starting with v2, the public API is intended to remain stable, and breaking changes will be avoided whenever reasonably possible. Backward compatibility will be prioritized across v2 releases, with breaking changes introduced only when necessary and clearly documented.

## License

MIT, as declared in `composer.json`.
