# Either

`Either` stores one of two branches: `Left` or `Right`. Its `map()` and `flatMap()` operations are right-biased, so they transform only `Right`.

## When to Use It

Use `Either` when both branches are meaningful alternatives and the domain naturally names them `Left` and `Right`. Use [Result](result.md) when the branches are specifically success and error.

## Basic Usage

```php
use Exact\Data\Either\Either;

$accepted = Either::right(42);
$rejected = Either::left('not available');
```

## Right-Biased Transformations

```php
$value = Either::right(10)
    ->map(static fn (int $number): int => $number * 2)
    ->flatMap(static fn (int $number): Either => Either::right($number + 1));
```

`mapLeft()` transforms only the `Left` branch. A `Left` passes through `map()` and `flatMap()` unchanged.

## Handling Branches

```php
$message = $value->fold(
    static fn (mixed $error): string => "Left: {$error}",
    static fn (int $number): string => "Right: {$number}",
);
```

Use `isLeft()` and `isRight()` when a branch check is useful. `getLeft()` and `getRight()` throw `LogicException` when called on the opposite branch.

## Next Steps

- Compare branch-oriented workflows with [Result](result.md).
- Use [Pattern Matching](../patterns/pattern-matching.md) when branch names are part of a larger state model.