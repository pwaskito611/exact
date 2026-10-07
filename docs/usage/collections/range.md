# Range

`Range` represents a lazy interval of integers. `Range::of()` includes the start and excludes the end; the step may be positive or negative.

## Quick Example

```php
use Exact\Collection\Range;

$evens = Range::of(0, 10, 2);
$values = $evens->toArray(); // [0, 2, 4, 6, 8]

$countdown = Range::closed(5, 1)->toArray(); // [5, 4, 3, 2, 1]
$firstThree = Range::of(1, 1_000_000)->take(3)->toArray();
```

`closed()` includes both endpoints and infers the direction. A zero step throws `InvalidArgumentException`. `count()` and `contains()` inspect the interval without materializing an array. `map()`, `filter()`, `take()`, and `drop()` return `LazySeq` pipelines.

Avoid calling `toArray()` on a very large range; use `take()` or iterate lazily when only a prefix is needed.

## When to Use Range

Use `Range` when:

- You need a numeric interval without pre-building the full list.
- The step may be positive or negative and the direction matters.
- You want an inspectable interval (`count()`, `contains()`) without materializing.

Use [LazySeq](lazy-seq.md) for deferred sequence transformations in general, and [Vec](vec.md) for an eager materialized sequence.

## Creating a Range

```php
use Exact\Collection\Range;

$zeroToFive    = Range::of(0, 5);        // [0, 1, 2, 3, 4]
$evens         = Range::of(0, 10, 2);    // [0, 2, 4, 6, 8]
$countdown     = Range::of(5, 0, -1);    // [5, 4, 3, 2, 1]
$inclusive     = Range::closed(1, 5);    // [1, 2, 3, 4, 5]
$inclusiveDown = Range::closed(5, 1);    // [5, 4, 3, 2, 1]
$single        = Range::closed(5, 5);    // [5]
```

- `Range::of(int $start, int $end, int $step = 1)` is half-open: `$start` is included, `$end` is excluded.
- `Range::closed(int $start, int $end)` is inclusive on both ends. The direction is inferred from the two endpoints.

Both factories throw `InvalidArgumentException` when `$step === 0`:

```php
Range::of(0, 10, 0); // InvalidArgumentException: Range step cannot be zero.
```

### Half-open vs. closed

| Expression | Yields |
|---|---|
| `Range::of(0, 5)` | `0, 1, 2, 3, 4` |
| `Range::of(0, 6)` | `0, 1, 2, 3, 4, 5` |
| `Range::closed(0, 5)` | `0, 1, 2, 3, 4, 5` |
| `Range::of(0, 10, 2)` | `0, 2, 4, 6, 8` |
| `Range::of(10, 0, -2)` | `10, 8, 6, 4, 2` |
| `Range::closed(5, 5)` | `5` |
| `Range::of(5, 5)` | *(empty)* |

The half-open convention matches PHP's `range()` for `of()` when `$start < $end`, but `Range` also supports negative steps natively, which `range()` does not.

### Direction inference in `closed()`

`closed()` chooses the step sign automatically:

- `$start <= $end` → step `+1`, end set to `$end + 1`.
- `$start > $end` → step `-1`, end set to `$end - 1`.

Both cases produce an internal half-open range that includes both endpoints. Equal endpoints produce a single-element range.

## Reading the Interval

```php
$range = Range::of(0, 10, 2);

$start = $range->start(); // 0
$end   = $range->end();   // 10 — the exclusive end, as passed
$step  = $range->step();  // 2
```

- `start()` returns the included start value.
- `end()` returns the exclusive end value (the same value passed to the factory).
- `step()` returns the step, positive or negative.

These reflect the construction parameters, not the last yielded value. For `Range::closed(5, 1)`, the internal end is `0` (exclusive), even though the last value is `1`.

## Inspecting Without Materializing

```php
$evens = Range::of(0, 10, 2);

$isEmpty = $evens->isEmpty();     // false
$count   = $evens->count();       // 5
$hasFour = $evens->contains(4);   // true
$hasFive = $evens->contains(5);   // false

Range::of(5, 5)->isEmpty();       // true
Range::of(5, 5)->count();         // 0
```

- `isEmpty()` reports whether the range yields no values. For a positive step, this is `$start >= $end`; for a negative step, `$start <= $end`.
- `count()` computes the number of values without iterating. Returns `0` for an empty range.
- `contains(int $value)` checks membership without materializing.

`contains()` verifies three things:

1. The value lies within the interval (between `$start` inclusive and `$end` exclusive, in the direction of the step).
2. The value is on the arithmetic progression defined by `$step`.

```php
Range::of(0, 10, 2)->contains(4);   // true
Range::of(0, 10, 2)->contains(5);   // false — not on the progression
Range::of(10, 0, -2)->contains(4);  // true
Range::of(10, 0, -2)->contains(5);  // false
```

`contains()` does not accept non-integers; the parameter is typed `int`.

## Transforming to LazySeq

```php
$range = Range::of(1, 100);

$firstTen   = $range->take(10);
$afterTen   = $range->drop(10);
$evens      = $range->filter(static fn (int $n): bool => $n % 2 === 0);
$squares    = $range->map(static fn (int $n): int => $n ** 2);

$tenSquares = $range
    ->map(static fn (int $n): int => $n ** 2)
    ->take(10)
    ->toArray();
```

- `take(int $count)` limits the range to at most `$count` values.
- `drop(int $count)` skips the first `$count` values.
- `filter(callable $predicate)` keeps values that satisfy the predicate.
- `map(callable $fn)` transforms each value.

All four return a [LazySeq](lazy-seq.md), not a `Range`. The reason is that the resulting set of values is no longer guaranteed to be an arithmetic progression with a fixed step, so the `Range`-specific operations (`count()`, `contains()`, `start()`, `end()`, `step()`) would not apply.

Combine them freely:

```php
$result = Range::of(1, 1_000_000)
    ->filter(static fn (int $n): bool => $n % 7 === 0)
    ->map(static fn (int $n): int => $n * 2)
    ->take(5)
    ->toArray();
// [14, 28, 42, 56, 70]
```

## Materializing

```php
$range = Range::of(0, 5);

$array = $range->toArray(); // [0, 1, 2, 3, 4]
$again = $range->toArray(); // [0, 1, 2, 3, 4] — re-iterates the same interval
```

`toArray()` returns all values as a list. Unlike `LazySeq`, a `Range` is not backed by a one-shot generator — its `getIterator()` builds a fresh generator each time. Re-iterating a `Range` is always safe and produces the same values.

Because `toArray()` materializes everything, avoid it on large ranges:

```php
Range::of(0, 1_000_000_000)->toArray(); // don't
Range::of(0, 1_000_000_000)->take(10)->toArray(); // fine
```

## Iterating

```php
foreach (Range::of(0, 5) as $value) {
    // 0, 1, 2, 3, 4
}

foreach (Range::closed(3, 1) as $value) {
    // 3, 2, 1
}
```

`getIterator()` returns a `Generator` that yields the values in order. Iteration is lazy: breaking out of a `foreach` stops generating further values immediately, without computing the rest of the range.

The generator adapts to the sign of the step:

```php
// Positive step
for ($i = $this->start; $i < $this->end; $i += $this->step) { yield $i; }

// Negative step
for ($i = $this->start; $i > $this->end; $i += $this->step) { yield $i; }
```

The comparison direction flips with the step sign, so a negative step yields values strictly greater than `$end`.

## JSON Serialization

```php
$json = json_encode(Range::of(0, 5));
// "[0,1,2,3,4]"
```

`jsonSerialize()` returns the same list as `toArray()`. Like `toArray()`, it materializes the entire range, so avoid it for very large ranges. For a prefix, serialize `take($n)->toArray()` instead.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `of` | `static of(int $start, int $end, int $step = 1): self` | Half-open interval `[$start, $end)`. Zero step throws `InvalidArgumentException`. |
| `closed` | `static closed(int $start, int $end): self` | Inclusive interval. Direction inferred from the endpoints. |
| `start` | `start(): int` | The included start value. |
| `end` | `end(): int` | The exclusive end value. |
| `step` | `step(): int` | The step, positive or negative. |
| `isEmpty` | `isEmpty(): bool` | Whether the range yields no values. |
| `count` | `count(): int` | Number of values; computed without iterating. |
| `contains` | `contains(int $value): bool` | Whether a value belongs to the interval and progression. |
| `take` | `take(int $count): LazySeq` | Limit to `$count` values; returns a `LazySeq`. |
| `drop` | `drop(int $count): LazySeq` | Skip the first `$count` values; returns a `LazySeq`. |
| `filter` | `filter(callable $predicate): LazySeq` | Keep values that satisfy a predicate; returns a `LazySeq`. |
| `map` | `map(callable $fn): LazySeq` | Transform each value; returns a `LazySeq`. |
| `toArray` | `toArray(): array` | Materialize all values. |
| `getIterator` | `getIterator(): Traversable` | Lazy generator over the values. |
| `jsonSerialize` | `jsonSerialize(): array` | Same as `toArray()`. |

## Next Steps

- See [LazySeq](lazy-seq.md) for deferred sequence transformations.
- See [Vec](vec.md) for an eager materialized sequence.
- See [Collections Overview](overview.md) to choose a collection.