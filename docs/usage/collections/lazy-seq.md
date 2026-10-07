# LazySeq

`LazySeq` processes an iterable only when you consume it. Use it when a pipeline should stop after a bounded number of values.

`from()` accepts an iterable, while `defer()` accepts a callable that produces an iterable. Both create a lazy sequence; `map()`, `filter()`, and `take()` build further lazy sequences rather than consuming values immediately.

## Quick Example

```php
use Exact\Collection\LazySeq;

$numbers = LazySeq::defer(function (): iterable {
    for ($number = 1; $number <= 1000; $number++) {
        yield $number;
    }
});

$firstThreeEvenSquares = $numbers
    ->filter(static fn (int $number): bool => $number % 2 === 0)
    ->map(static fn (int $number): int => $number ** 2)
    ->take(3)
    ->toSeq();
```

The result is a `Vec` containing `[4, 16, 36]`. `map()`, `filter()`, and `take()` create another lazy pipeline; `toSeq()` consumes it. `take()` stops requesting source values when it has enough. A negative count throws `InvalidArgumentException`.

## When to Use LazySeq

Use `LazySeq` when:

- The source is expensive and you may only need part of it.
- The source is infinite (or very large) and you only need a bounded window.
- You want to compose transformations without materializing intermediate collections.

Use [Vec](vec.md) when you need random access, counting, or repeated iteration over a fixed collection.

## Laziness and Re-execution

Every terminal operation (`toArray()`, `toSeq()`, `first()`, `count()`, `fold()`, …) re-runs the source. Combinators return a new `LazySeq` that stores a `callable(): iterable`; nothing is consumed until iteration begins.

If the source is expensive, wrap it with `memoize()` so the first iteration is cached for subsequent consumers.

`from()` wraps an existing iterable in a generator. If that iterable is a one-shot generator, each terminal operation will produce a different result — or nothing. Prefer `defer()` when you can recreate the source, or `memoize()` when the source is a one-shot but its result is worth caching.

## Creating a Sequence

```php
use Exact\Collection\LazySeq;

$fromArray    = LazySeq::from([1, 2, 3]);
$fromIterable = LazySeq::from(new ArrayIterator([1, 2, 3]));
$deferred     = LazySeq::defer(static fn (): iterable => loadRows());
$ofValues     = LazySeq::of(1, 2, 3);

$powers       = LazySeq::iterate(1, static fn (int $n): int => $n * 2);
$countdown    = LazySeq::unfold(5, static fn (int $n): ?int => $n > 1 ? $n - 1 : null);
$random       = LazySeq::generate(static fn (): ?int => random_int(0, 9) ?: null);
$range        = LazySeq::range(0, 100, 5);
```

- `LazySeq::from(iterable $source)` wraps an iterable; iteration starts when consumed.
- `LazySeq::defer(callable $source)` stores a callable that returns an iterable, evaluated per iteration.
- `LazySeq::of(mixed ...$values)` creates a sequence from the given values.
- `LazySeq::iterate($seed, $next)` produces an infinite sequence `$seed, $next($seed), ...`.
- `LazySeq::unfold($seed, $next)` yields `$seed` then `$next($seed)` until `$next` returns `null`.
- `LazySeq::generate(callable $generator)` yields values until the callable returns `null`.
- `LazySeq::range($start, $end, $step = 1)` yields values in `[$start, $end)` with a positive or negative step. A zero step throws `InvalidArgumentException`.

`range()` is half-open: `range(0, 5)` yields `0, 1, 2, 3, 4`, not `5`. A negative step iterates downward and also stops before `$end`.

## Transforming

```php
$lazy = LazySeq::range(1, 11);

$doubled   = $lazy->map(static fn (int $n): int => $n * 2);
$evens     = $lazy->filter(static fn (int $n): bool => $n % 2 === 0);
$odds      = $lazy->reject(static fn (int $n): bool => $n % 2 === 0);
$flattened = $lazy->flatMap(static fn (int $n): array => [$n, -$n]);
$inspected = $lazy->tap(static fn (int $n): void => error_log("n={$n}"));
```

- `map(callable $fn)` yields `$fn($item)` for each item.
- `filter(callable $predicate)` yields only items where `$predicate` is `true`.
- `reject(callable $predicate)` is `filter()` with a negated predicate.
- `flatMap(callable $fn)` expects `$fn` to return an iterable and yields its items in order.
- `tap(callable $fn)` runs `$fn` for side effects and yields the original item unchanged.

All combinators are lazy: they do not consume values until the returned `LazySeq` is iterated.

## Slicing and Limiting

```php
$lazy = LazySeq::range(1, 1000);

$firstFive      = $lazy->take(5);
$skipTen        = $lazy->drop(10);
$smallerThan50  = $lazy->takeWhile(static fn (int $n): bool => $n < 50);
$from50On       = $lazy->dropWhile(static fn (int $n): bool => $n < 50);
$unique         = LazySeq::from([1, 2, 2, 3, 1])->distinct();
$pairs          = LazySeq::range(1, 5)->pairwise();
$chunksOfThree  = $lazy->chunk(3);
```

- `take(int $count)` yields at most `$count` items and stops requesting the source. `take(0)` yields nothing without touching the source.
- `drop(int $count)` discards the first `$count` items and yields the rest.
- `takeWhile(callable $predicate)` yields items while `$predicate` is `true`, then stops.
- `dropWhile(callable $predicate)` skips items while `$predicate` is `true`, then yields everything (including items that would still match).
- `distinct()` yields items not seen before, using `gettype()`+string for scalars and `serialize()` for non-scalars.
- `pairwise()` yields `[previous, current]` for every consecutive pair.
- `chunk(int $size)` yields lists of up to `$size` items; the final chunk may be shorter.

`take()` and `drop()` throw `InvalidArgumentException` for negative counts. `chunk()` throws for sizes below 1.

## Combining Sequences

```php
$first  = LazySeq::range(1, 4);
$second = LazySeq::range(10, 13);

$joined   = $first->concat($second);
$zipped   = $first->zip($second);
$paired   = $first->zipWith(
    $second,
    static fn (int $a, int $b): int => $a + $b,
);
$indexed  = $first->enumerate();
```

- `concat(self $other)` yields all items from `$this`, then all items from `$other`.
- `zip(self $other)` yields `[left, right]` pairs until either sequence is exhausted.
- `zipWith(self $other, callable $fn)` yields `$fn($left, $right)` for each pair.
- `enumerate()` yields `[index, item]` starting at index `0`.

`zip()` and `zipWith()` are finite: they stop as soon as one side ends. This is how an infinite `LazySeq` can be paired with a bounded one.

## Terminal Operations

Terminal operations consume the sequence. Every call re-executes the source unless the sequence is memoized.

```php
$lazy = LazySeq::range(1, 6);

$first      = $lazy->first();                          // 1
$maybe      = $lazy->firstOrNull();                    // 1
$found      = $lazy->find(static fn (int $n): bool => $n > 3);  // 4
$hasEven    = $lazy->any(static fn (int $n): bool => $n % 2 === 0); // true
$allPositive = $lazy->all(static fn (int $n): bool => $n > 0);      // true
$count      = $lazy->count();                          // 5
$sum        = $lazy->fold(0, static fn (int $acc, int $n): int => $acc + $n); // 15
$product    = $lazy->reduce(static fn (int $a, int $b): int => $a * $b); // 120
```

- `first()` returns the first item and throws `OutOfBoundsException` on empty input.
- `firstOrNull()` returns the first item or `null`.
- `find(callable $predicate)` returns the first matching item or `null`.
- `any(callable $predicate)` returns `true` if any item matches; short-circuits.
- `all(callable $predicate)` returns `true` if every item matches; short-circuits on the first failure.
- `count()` iterates the whole sequence and returns the number of items.
- `fold($initial, callable $fn)` reduces from an initial accumulator.
- `reduce(callable $fn)` reduces using the first item as the initial accumulator; throws `OutOfBoundsException` on empty input.

Because these consume the source, they can only be safely called on a finite `LazySeq` — or on an infinite one that you have truncated with `take()` / `takeWhile()`.

## Materializing

```php
$lazy = LazySeq::range(1, 4);

$vec   = $lazy->toSeq();    // Vec [1, 2, 3]
$array = $lazy->toArray();  // [1, 2, 3]
$again = $lazy->toArray();  // [1, 2, 3] — source is re-executed
```

- `toSeq()` forces the sequence into a [Vec](vec.md). Runs the source once.
- `toArray()` forces the sequence into a list. Runs the source once.

Neither caches the result. If you plan to iterate multiple times, call `memoize()` first:

```php
$memoized = LazySeq::defer(static fn (): iterable => expensiveQuery())
    ->memoize();

$firstRun  = $memoized->toArray(); // executes the source once
$secondRun = $memoized->toArray(); // reuses the cached list
```

`memoize()` returns a new `LazySeq` that caches the source's items on first consumption. The cache lives as long as the memoized sequence.

## Iterating

```php
foreach (LazySeq::range(0, 3) as $value) {
    // 0, 1, 2
}
```

`getIterator()` returns the source generator directly. Iteration respects laziness: breaking out of a `foreach` stops requesting source values.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `from` | `static from(iterable $source): self` | Wrap an existing iterable. |
| `defer` | `static defer(callable $source): self` | Defer source creation until iteration. |
| `of` | `static of(mixed ...$values): self` | Create from the given values. |
| `iterate` | `static iterate(mixed $seed, callable $next): self` | Infinite sequence starting from `$seed`. |
| `unfold` | `static unfold(mixed $seed, callable $next): self` | Yield `$seed`, then apply `$next` until it returns `null`. |
| `generate` | `static generate(callable $generator): self` | Yield until the callable returns `null`. |
| `range` | `static range(mixed $start, mixed $end, mixed $step = 1): self` | Half-open numeric range. Zero step throws. |
| `map` | `map(callable $fn): self` | Lazy transform. |
| `filter` | `filter(callable $predicate): self` | Lazy keep. |
| `reject` | `reject(callable $predicate): self` | Lazy drop by predicate. |
| `flatMap` | `flatMap(callable $fn): self` | Lazy flat map; `$fn` returns an iterable. |
| `tap` | `tap(callable $fn): self` | Side effect without changing values. |
| `take` | `take(int $count): self` | Yield at most `$count` items; negative throws. |
| `drop` | `drop(int $count): self` | Skip the first `$count` items; negative throws. |
| `takeWhile` | `takeWhile(callable $predicate): self` | Yield while predicate holds. |
| `dropWhile` | `dropWhile(callable $predicate): self` | Skip while predicate holds, then yield the rest. |
| `distinct` | `distinct(): self` | Drop duplicates by value. |
| `pairwise` | `pairwise(): self` | Yield `[previous, current]` pairs. |
| `chunk` | `chunk(int $size): self` | Group into lists of size `$size`; below 1 throws. |
| `concat` | `concat(self $other): self` | Concatenate two sequences. |
| `zip` | `zip(self $other): self` | Pair items until one side ends. |
| `zipWith` | `zipWith(self $other, callable $fn): self` | Pair with a callback until one side ends. |
| `enumerate` | `enumerate(): self` | Yield `[index, item]` pairs. |
| `first` | `first(): mixed` | First item; throws `OutOfBoundsException` on empty. |
| `firstOrNull` | `firstOrNull(): mixed` | First item or `null`. |
| `find` | `find(callable $predicate): mixed` | First match or `null`. |
| `any` | `any(callable $predicate): bool` | True if any item matches; short-circuits. |
| `all` | `all(callable $predicate): bool` | True if all items match; short-circuits. |
| `count` | `count(): int` | Number of items; consumes the source. |
| `fold` | `fold(mixed $initial, callable $fn): mixed` | Reduce with an initial accumulator. |
| `reduce` | `reduce(callable $fn): mixed` | Reduce using the first item; throws on empty. |
| `toSeq` | `toSeq(): Vec` | Force into a `Vec`. |
| `toArray` | `toArray(): array` | Force into a list. |
| `memoize` | `memoize(): self` | Cache the source's items for subsequent iterations. |
| `getIterator` | `getIterator(): Traversable` | Iterate lazily. |

## Next Steps

- Combine lazy processing with [Vec](vec.md) in [E-commerce](../examples/ecommerce.md).
- Use [Pattern Matching](../patterns/pattern-matching.md) to handle named domain states.