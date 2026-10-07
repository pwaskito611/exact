# NonEmptyLazySeq

`NonEmptyLazySeq` represents a deferred sequence intended to contain at least one value. It preserves the non-empty type through `map()`, `tap()`, and positive `take()` operations. Transformations that can remove all values, such as `filter()` and `drop()`, return `LazySeq`.

## Quick Example

```php
use Exact\Collection\NonEmptyLazySeq;

$numbers = NonEmptyLazySeq::of(1, 2, 3)
    ->map(static fn (int $value): int => $value * 10)
    ->take(2);

$first = $numbers->head(); // 10
$materialized = $numbers->toVec();
```

`fromArray()` requires a non-empty array and throws `OutOfBoundsException` for an empty one. `take()` requires a count of at least one and throws `InvalidArgumentException` otherwise. `count()`, `toArray()`, and JSON serialization consume the sequence, so do not use them with an infinite source.

`fromLazySeq()` returns `null` when the first value is `null` (including an empty sequence) and probes the source to determine this. For reusable or one-shot generator sources, account for that initial consumption; `LazySeq::memoize()` can cache values before conversion.

## When to Use NonEmptyLazySeq

Use `NonEmptyLazySeq` when a pipeline needs to guarantee at least one value while remaining lazy:

- You want to express "there is a first element" without materializing the whole sequence.
- You want the type system to preserve that guarantee through `map()` and `take()`.
- You are willing to accept that some operations (`filter()`, `drop()`) drop the guarantee and return a plain `LazySeq`.

Use [NonEmptyVec](non-empty-vec.md) when the non-empty sequence is eager and finite, and [LazySeq](lazy-seq.md) when emptiness is acceptable.

## The Non-Empty Invariant

The invariant is checked lazily. Factories check what they can immediately, but the source is only consumed when the sequence is iterated or materialized. If a `NonEmptyLazySeq`'s source produces nothing:

- `head()` throws `OutOfBoundsException` (delegated to `LazySeq::first()`).
- `toArray()` and `jsonSerialize()` throw `OutOfBoundsException` with the message `NonEmptyLazySeq source produced an empty sequence.`
- `fromArray([])` throws immediately, before any source is created.

Raw iteration via `getIterator()` does not verify the invariant; it simply yields whatever the source produces.

## Creating a NonEmptyLazySeq

```php
use Exact\Collection\NonEmptyLazySeq;
use Exact\Collection\LazySeq;

$fromValues  = NonEmptyLazySeq::of(1, 2, 3);
$fromOne     = NonEmptyLazySeq::of(42);          // one element is valid
$fromArray   = NonEmptyLazySeq::fromArray([1, 2, 3]);
$infinite    = NonEmptyLazySeq::iterate(1, static fn (int $n): int => $n * 2);

$converted = NonEmptyLazySeq::fromLazySeq(LazySeq::of(1, 2, 3));
// NonEmptyLazySeq<1, 2, 3> — or null if the LazySeq is empty

$maybe = NonEmptyLazySeq::fromLazySeq(LazySeq::of());
// null
```

- `NonEmptyLazySeq::of($head, ...$tail)` requires at least `$head`. Additional values are optional.
- `NonEmptyLazySeq::fromArray(array $items)` requires a non-empty array; an empty array throws `OutOfBoundsException`.
- `NonEmptyLazySeq::iterate($seed, $next)` produces an infinite sequence `$seed, $next($seed), ...`.
- `NonEmptyLazySeq::fromLazySeq(LazySeq $lazySeq): ?self` returns `null` when the sequence is empty or its first value is `null`.

### A note on `fromLazySeq()` and `null`

`fromLazySeq()` probes the sequence with `firstOrNull()`. This has two consequences:

1. **The source is consumed once.** For a one-shot `Generator`, the returned `NonEmptyLazySeq` will start from the second value — or from scratch if the source is reusable.
2. **A first value of `null` is treated as absent.** `LazySeq::of(null, 1, 2)` yields `fromLazySeq(...) === null`, even though the sequence contains three values.

If either behavior is a problem, memoize the source first:

```php
use Exact\Collection\LazySeq;
use Exact\Collection\NonEmptyLazySeq;

$memoized = LazySeq::defer(static fn (): iterable => readRows())->memoize();

$nonEmpty = NonEmptyLazySeq::fromLazySeq($memoized);
// The probe uses the cached values; subsequent uses re-iterate the cache.
```

## Transforming a NonEmptyLazySeq

```php
$source = NonEmptyLazySeq::of(1, 2, 3);

// Preserves the non-empty type:
$doubled = $source->map(static fn (int $n): int => $n * 2);
$inspected = $source->tap(static fn (int $n): void => error_log("n={$n}"));
$firstTwo = $source->take(2);

// Drops the non-empty type (returns LazySeq):
$evens = $source->filter(static fn (int $n): bool => $n % 2 === 0);
$afterFirst = $source->drop(1);
$flattened = $source->flatMap(static fn (int $n): array => [$n, -$n]);
```

- `map(callable $fn)` returns `NonEmptyLazySeq<U>` because mapping never removes values.
- `tap(callable $fn)` returns `NonEmptyLazySeq<T>` because side effects do not change the sequence.
- `take(int $count)` returns `NonEmptyLazySeq<T>`. Requires `$count >= 1`; throws `InvalidArgumentException` otherwise.
- `filter(callable $predicate)` returns `LazySeq<T>` — all values may be filtered out.
- `drop(int $count)` returns `LazySeq<T>` — dropping everything is possible.
- `flatMap(callable $fn)` returns `LazySeq<U>` — a callback may return an empty iterable for every input.

All of these are lazy: they build a new deferred sequence without consuming the source. Only terminal operations trigger execution.

## Slicing

```php
$source = NonEmptyLazySeq::of(1, 2, 3, 4);

$firstTwo = $source->take(2);   // NonEmptyLazySeq [1, 2]
$rest     = $source->drop(2);   // LazySeq [3, 4]
$tail     = $source->tail();    // LazySeq [2, 3, 4] — same as drop(1)
```

- `take(int $count)` preserves the non-empty guarantee and requires `$count >= 1`.
- `drop(int $count)` returns a plain `LazySeq`, because dropping all values is allowed.
- `tail()` is a shortcut for `drop(1)`. Also returns `LazySeq`.

`take(0)` is intentionally invalid: the result would not be non-empty. Use `LazySeq::take(0)` if you need that case.

## Terminal Operations

```php
$source = NonEmptyLazySeq::of(10, 20, 30);

$first       = $source->head();                               // 10
$firstOrNull = $source->headOrNull();                         // 10
$found       = $source->find(static fn (int $n): bool => $n > 15); // 20
$hasLarge    = $source->any(static fn (int $n): bool => $n > 25);  // true
$allPositive = $source->all(static fn (int $n): bool => $n > 0);   // true
$total       = $source->count();                              // 3
$sum         = $source->fold(0, static fn (int $acc, int $n): int => $acc + $n); // 60
$product     = $source->reduce(static fn (int $a, int $b): int => $a * $b);      // 6000
```

- `head()` returns the first value. Because of the non-empty invariant, this normally succeeds; it throws `OutOfBoundsException` if the source turns out to be empty.
- `headOrNull()` returns the first value or `null` — safe even when the invariant is violated.
- `find(callable $predicate)` returns the first match or `null`.
- `any(callable $predicate)` short-circuits on the first match.
- `all(callable $predicate)` short-circuits on the first failure.
- `count()` consumes the whole sequence.
- `fold($initial, callable $fn)` reduces from an initial accumulator.
- `reduce(callable $fn)` reduces using the first value as the accumulator.

`count()`, `toArray()`, and `jsonSerialize()` **must not** be called on infinite sources. `NonEmptyLazySeq::iterate(...)` creates an infinite source; combine it with `take()` before materializing.

## Materializing

```php
use Exact\Collection\LazySeq;
use Exact\Collection\NonEmptyLazySeq;
use Exact\Collection\Vec;

$source = NonEmptyLazySeq::of(1, 2, 3);

$lazy = $source->toLazySeq(); // LazySeq [1, 2, 3]
$vec  = $source->toVec();     // Vec [1, 2, 3]
$list = $source->toArray();   // [1, 2, 3]
```

- `toLazySeq()` calls the stored source callable and returns a plain `LazySeq<T>`. Every call re-executes the source.
- `toVec()` materializes into a [Vec](vec.md) via `toArray()`.
- `toArray()` materializes into a list and asserts the non-empty invariant.

Because each terminal call re-executes the source, `NonEmptyLazySeq` inherits the re-execution semantics of `LazySeq`. Wrap with `LazySeq::memoize()` before wrapping into a `NonEmptyLazySeq` if the source is expensive or one-shot.

## Iterating

```php
foreach (NonEmptyLazySeq::of(1, 2, 3) as $value) {
    // 1, 2, 3
}
```

`getIterator()` returns the underlying `LazySeq`'s iterator. Iteration is lazy and stops as soon as the consumer stops requesting values.

Iteration does not enforce the non-empty invariant: an empty source will simply yield no values. Use `toArray()`, `jsonSerialize()`, or `head()` when you need the invariant checked.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `of` | `static of(mixed $head, mixed ...$tail): self` | Create a non-empty sequence from the given values. |
| `fromArray` | `static fromArray(array $items): self` | Create from a non-empty array; empty input throws `OutOfBoundsException`. |
| `fromLazySeq` | `static fromLazySeq(LazySeq $lazySeq): ?self` | Wrap a `LazySeq`; returns `null` if it is empty or its first value is `null`. Consumes the source once. |
| `iterate` | `static iterate(mixed $seed, callable $next): self` | Infinite sequence starting from `$seed`. |
| `map` | `map(callable $fn): self` | Lazy transform; preserves the non-empty type. |
| `filter` | `filter(callable $predicate): LazySeq` | Lazy keep; returns `LazySeq` because the result may be empty. |
| `flatMap` | `flatMap(callable $fn): LazySeq` | Lazy flat map; returns `LazySeq`. |
| `tap` | `tap(callable $fn): self` | Side effect without changing values; preserves the non-empty type. |
| `take` | `take(int $count): self` | Limit to `$count` values; `$count` must be at least 1. |
| `drop` | `drop(int $count): LazySeq` | Skip the first `$count` values; returns `LazySeq`. |
| `head` | `head(): mixed` | First value. |
| `headOrNull` | `headOrNull(): mixed` | First value or `null`. |
| `tail` | `tail(): LazySeq` | All values except the first. |
| `find` | `find(callable $predicate): mixed` | First match or `null`. |
| `any` | `any(callable $predicate): bool` | True if any value matches; short-circuits. |
| `all` | `all(callable $predicate): bool` | True if all values match; short-circuits. |
| `count` | `count(): int` | Number of values; consumes the source. Never call on infinite sources. |
| `fold` | `fold(mixed $initial, callable $fn): mixed` | Reduce with an initial accumulator. |
| `reduce` | `reduce(callable $fn): mixed` | Reduce using the first value as the accumulator. |
| `toLazySeq` | `toLazySeq(): LazySeq` | Return the underlying `LazySeq`, re-executing the source. |
| `toVec` | `toVec(): Vec` | Materialize into a `Vec`. |
| `toArray` | `toArray(): array` | Materialize into a non-empty list; asserts the invariant. |
| `getIterator` | `getIterator(): Traversable` | Iterate lazily; does not enforce the invariant. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize as a non-empty list; consumes the source. |

## Next Steps

- See [LazySeq](lazy-seq.md) for general deferred processing.
- Compare with [NonEmptyVec](non-empty-vec.md) for eager, finite values.
- See [Collections Overview](overview.md) to choose a collection.