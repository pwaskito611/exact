# NonEmptyVec

`NonEmptyVec` is an ordered sequence whose first item is guaranteed to exist. Operations that may yield an empty result return an ordinary [Vec](vec.md) instead.

## Quick Example

```php
use Exact\Collection\NonEmptyVec;

$statuses = NonEmptyVec::of('pending', 'paid');
$labels = $statuses
    ->map(static fn (string $status): string => strtoupper($status))
    ->prepend('order:')
    ->append('fulfilled')
    ->toArray();
```

`map()`, `prepend()`, and `append()` preserve the non-empty guarantee.

## When to Use NonEmptyVec

Use `NonEmptyVec` when the domain requires at least one value:

- An order must have at least one line item.
- A workflow must have at least one stage.
- A response must carry at least one result.

Use [Vec](vec.md) when emptiness is a valid state, [NonEmptySet](non-empty-set.md) when values must also be distinct, and [Validated](../fundamentals/validated.md) when non-empty input is one rule among several.

## The Non-Empty Invariant

The invariant is enforced at construction: the private constructor throws `InvalidArgumentException` when the given array is empty. Every operation either:

- Returns `self` when the result is guaranteed non-empty (`map()`, `prepend()`, `append()`, `concat()`, `reverse()`, `tap()`), or
- Returns [Vec](vec.md) when the result may be empty (`filter()`, `reject()`, `tail()`, `init()`).

Callers can therefore rely on `NonEmptyVec` being non-empty at runtime.

## Creating a NonEmptyVec

```php
use Exact\Collection\NonEmptyVec;
use Exact\Collection\Vec;

$fromValues   = NonEmptyVec::of('pending', 'paid');
$fromOne      = NonEmptyVec::of('pending');           // one item is valid
$fromArray    = NonEmptyVec::fromArray(['pending', 'paid']);
$fromIterable = NonEmptyVec::fromIterable(new ArrayIterator(['pending', 'paid']));
$fromSeq      = NonEmptyVec::fromSeq(Vec::of('pending', 'paid')); // ?NonEmptyVec
```

- `NonEmptyVec::of(mixed $head, mixed ...$tail)` requires at least `$head`.
- `NonEmptyVec::fromArray(array $items)` requires a non-empty array; an empty array throws `InvalidArgumentException`.
- `NonEmptyVec::fromIterable(iterable $items)` accepts any iterable; empty input throws `InvalidArgumentException`.
- `NonEmptyVec::fromSeq(Vec $vec): ?self` returns `null` when the `Vec` is empty, otherwise wraps it.

### A note on `fromSeq()`

`fromSeq()` returns `null` for empty input, unlike the other factories which throw. This makes it useful when emptiness is an expected case:

```php
$nonEmpty = NonEmptyVec::fromSeq($maybeEmpty);

if ($nonEmpty === null) {
    // handle the empty case
}
```

## Reading Items

```php
$statuses = NonEmptyVec::of('pending', 'paid', 'fulfilled');

$first     = $statuses->head();    // 'pending'
$last      = $statuses->last();    // 'fulfilled'
$second    = $statuses->get(1);    // 'paid'
$maybe     = $statuses->getOrNull(10); // null

$count     = $statuses->count();   // 3
$size      = $statuses->size();    // 3, alias for count()
```

- `head()` returns the first item. Never throws.
- `last()` returns the last item. Never throws.
- `get(int $index)` returns the item at a zero-based index; throws `OutOfBoundsException` for a negative index or an index `>= count()`.
- `getOrNull(int $index)` returns the item or `null`.
- `count()` / `size()` return the number of items.

`NonEmptyVec` implements `Countable`, so `count($nonEmptyVec)` returns the same value as `$nonEmptyVec->count()`.

## Splitting Off the Ends

```php
$statuses = NonEmptyVec::of('pending', 'paid', 'fulfilled');

$rest    = $statuses->tail(); // Vec ['paid', 'fulfilled']
$allButLast = $statuses->init(); // Vec ['pending', 'paid']
```

- `tail()` returns everything except the first item, as a [Vec](vec.md).
- `init()` returns everything except the last item, as a [Vec](vec.md).

Both can produce an empty `Vec` (for a one-item input), so they do not return `NonEmptyVec`.

## Transforming Items

```php
$statuses = NonEmptyVec::of('pending', 'paid');

$upperCased = $statuses->map(
    static fn (string $status): string => strtoupper($status),
);
// NonEmptyVec ['PENDING', 'PAID']

$onlyPaid = $statuses->filter(
    static fn (string $status): bool => $status === 'paid',
);
// Vec ['paid']

$notPaid = $statuses->reject(
    static fn (string $status): bool => $status === 'paid',
);
// Vec ['pending']

$inspected = $statuses->tap(
    static fn (string $status): void => error_log("status={$status}"),
);
```

- `map(callable $fn)` transforms every item. Returns `NonEmptyVec`.
- `filter(callable $predicate)` keeps items where the predicate is `true`. Returns [Vec](vec.md).
- `reject(callable $predicate)` is `filter()` with a negated predicate. Returns [Vec](vec.md).
- `tap(callable $fn)` runs the callback for side effects and returns the same instance.

## Reducing

```php
$numbers = NonEmptyVec::of(1, 2, 3, 4);

$sumLeft = $numbers->foldLeft(
    0,
    static fn (int $acc, int $n): int => $acc + $n,
); // 10 — ((0 + 1) + 2) + 3 + 4

$sumRight = $numbers->foldRight(
    0,
    static fn (int $n, int $acc): int => $n + $acc,
); // 10 — 1 + (2 + (3 + (4 + 0)))

$product = $numbers->reduce(
    static fn (int $a, int $b): int => $a * $b,
); // 24
```

- `foldLeft($initial, callable $fn)` folds from the left. Callback receives `(accumulator, item)`.
- `foldRight($initial, callable $fn)` folds from the right. Callback receives `(item, accumulator)`. Implementation reverses the array and folds left, so the callback order matters.
- `reduce(callable $fn)` folds without an explicit initial value, using the head as the seed. Callback receives `(accumulator, item)`. Never throws because the sequence is guaranteed non-empty.

## Checking Values and Predicates

```php
$statuses = NonEmptyVec::of('pending', 'paid');

$hasPaid    = $statuses->contains('paid');    // true
$anyPaid    = $statuses->exists(
    static fn (string $s): bool => $s === 'paid',
); // true
$allValid   = $statuses->forall(
    static fn (string $s): bool => strlen($s) > 3,
); // true
```

- `contains(mixed $value)` checks membership using strict comparison (`===`).
- `exists(callable $predicate)` returns `true` on the first match; short-circuits.
- `forall(callable $predicate)` returns `false` on the first failure; short-circuits.

## Combining and Reordering

```php
$statuses = NonEmptyVec::of('pending', 'paid');

$withCancelled = $statuses->append('cancelled');   // ['pending', 'paid', 'cancelled']
$withOrder     = $statuses->prepend('order:');     // ['order:', 'pending', 'paid']

$combined = $statuses->concat(NonEmptyVec::of('fulfilled'));
// ['pending', 'paid', 'fulfilled']

$reversed = $statuses->reverse(); // ['paid', 'pending']
```

- `append(mixed $value)` adds to the end; returns `NonEmptyVec`.
- `prepend(mixed $value)` adds to the front; returns `NonEmptyVec`.
- `concat(self $other)` concatenates two non-empty sequences; returns `NonEmptyVec`.
- `reverse()` returns a `NonEmptyVec` with the items reversed.

Concatenating a `NonEmptyVec` with another `NonEmptyVec` cannot produce an empty sequence, so the return type stays `NonEmptyVec`. To concatenate with a `Vec`, convert the `Vec` first or use `Vec::concat()` instead.

## Comparing Sequences

```php
$a = NonEmptyVec::of(1, 2, 3);
$b = NonEmptyVec::of(1, 2, 3);
$c = NonEmptyVec::of(3, 2, 1);

$a->equals($b); // true
$a->equals($c); // false — order differs
```

`equals(self $other)` compares the underlying lists with `===`, so both values and their order must match.

## Interop

```php
$statuses = NonEmptyVec::of('pending', 'paid');

$array = $statuses->toArray();
// ['pending', 'paid']

$vec = $statuses->toVec();     // Vec ['pending', 'paid']
$seq = $statuses->toSeq();     // Vec ['pending', 'paid'] — alias for toVec()

foreach ($statuses as $index => $value) {
    // iterate in order, $index is 0..n-1
}

$json = json_encode($statuses);
// ["pending","paid"]
```

- `toArray()` returns the items as a non-empty list.
- `toVec()` converts to a plain [Vec](vec.md).
- `toSeq()` is an alias for `toVec()`.
- `getIterator()` yields items with integer indexes starting at 0.
- `jsonSerialize()` produces a JSON array, not an object.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `of` | `static of(mixed $head, mixed ...$tail): self` | Create a non-empty sequence. |
| `fromArray` | `static fromArray(array $items): self` | Create from a non-empty array; empty input throws `InvalidArgumentException`. |
| `fromIterable` | `static fromIterable(iterable $items): self` | Create from any iterable; empty input throws `InvalidArgumentException`. |
| `fromSeq` | `static fromSeq(Vec $vec): ?self` | Wrap a `Vec`; returns `null` when the `Vec` is empty. |
| `count` | `count(): int` | Number of items. |
| `size` | `size(): int` | Alias for `count()`. |
| `head` | `head(): mixed` | First item. Never throws. |
| `last` | `last(): mixed` | Last item. Never throws. |
| `tail` | `tail(): Vec` | Everything except the first item. |
| `init` | `init(): Vec` | Everything except the last item. |
| `get` | `get(int $index): mixed` | Item at a zero-based index; throws `OutOfBoundsException`. |
| `getOrNull` | `getOrNull(int $index): mixed` | Item at an index or `null`. |
| `map` | `map(callable $fn): self` | Transform each item; preserves the non-empty guarantee. |
| `filter` | `filter(callable $predicate): Vec` | Keep items that satisfy a predicate; returns `Vec`. |
| `reject` | `reject(callable $predicate): Vec` | Drop items that satisfy a predicate; returns `Vec`. |
| `foldLeft` | `foldLeft(mixed $initial, callable $fn): mixed` | Fold from the left; callback receives `(accumulator, item)`. |
| `foldRight` | `foldRight(mixed $initial, callable $fn): mixed` | Fold from the right; callback receives `(item, accumulator)`. |
| `reduce` | `reduce(callable $fn): mixed` | Fold using the head as the seed; never throws. |
| `contains` | `contains(mixed $value): bool` | Check membership using strict comparison. |
| `exists` | `exists(callable $predicate): bool` | True if any item matches; short-circuits. |
| `forall` | `forall(callable $predicate): bool` | True if all items match; short-circuits. |
| `equals` | `equals(self $other): bool` | Compare items and their order. |
| `append` | `append(mixed $value): self` | Add to the end; preserves the non-empty guarantee. |
| `prepend` | `prepend(mixed $value): self` | Add to the front; preserves the non-empty guarantee. |
| `concat` | `concat(self $other): self` | Concatenate two non-empty sequences; returns `NonEmptyVec`. |
| `reverse` | `reverse(): self` | Return a `NonEmptyVec` with the items reversed. |
| `tap` | `tap(callable $fn): self` | Side effect per item; returns the same instance. |
| `toArray` | `toArray(): array` | Return items as a non-empty list. |
| `toVec` | `toVec(): Vec` | Convert to a plain `Vec`. |
| `toSeq` | `toSeq(): Vec` | Alias for `toVec()`. |
| `getIterator` | `getIterator(): Traversable` | Iterate items in order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize items as a JSON array. |

## Next Steps

- Compare its guarantee with [Vec](vec.md) when an empty result is valid.
- Use [NonEmptySet](non-empty-set.md) when values must also be distinct.
- Use [Validated](../fundamentals/validated.md) when non-empty input is one validation rule among several.