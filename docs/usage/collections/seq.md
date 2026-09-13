# Seq

`Seq` is an ordered sequence that may be empty.

## Basic Usage

```php
use Exact\Collection\Seq;

$numbers = Seq::of(1, 2, 3, 4);
$evenSquares = $numbers
    ->filter(static fn (int $number): bool => $number % 2 === 0)
    ->map(static fn (int $number): int => $number ** 2);

$values = $evenSquares->toArray(); // [4, 16]
```

## Common Operations

`head()` returns the first value and `tail()` returns the remaining sequence. Both throw `EmptyCollectionException` for an empty sequence. `get()` accesses a zero-based index and throws `OutOfBoundsException` when it is missing.

`flatMap()` combines the values from `Seq` results:

```php
$expanded = Seq::of(1, 2)->flatMap(
    static fn (int $value): Seq => Seq::of($value, $value * 10),
);
```

Use `foldLeft()` to reduce a sequence to one value:

```php
$total = Seq::of(2, 3, 5)->foldLeft(
    0,
    static fn (int $total, int $value): int => $total + $value,
);
```

`contains()` uses strict comparison. `each()` runs a callback for every value.

## Immutability in Practice

```php
$original = Seq::of(1, 2);
$extended = $original->append(3);

$original->toArray(); // [1, 2]
$extended->toArray(); // [1, 2, 3]
```

## Next Steps

- Use [NonEmptySeq](non-empty-seq.md) when emptiness is not valid.
- Use [LazySeq](lazy-seq.md) for deferred input.
- Use [Function Composition](../functional/composition.md) for reusable transformations.