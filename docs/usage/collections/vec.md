# Vec

`Vec` is an immutable, eager, ordered collection that can be empty. Every transformation returns a new `Vec`; the input is never modified.

## Quick Example

```php
use Exact\Collection\Vec;

$featuredProducts = Vec::of('book', 'pen', 'bag')
    ->filter(static fn (string $product): bool => str_starts_with($product, 'b'))
    ->map(static fn (string $product): string => strtoupper($product))
    ->append('NOTEBOOK');

$products = $featuredProducts->toArray(); // ['BOOK', 'BAG', 'NOTEBOOK']
```

## When to Use Vec

Use `Vec` when:

- You need index-based access to an ordered collection.
- You want eager, predictable transformations.
- Emptiness is a valid state.

Use [NonEmptyVec](non-empty-vec.md) when emptiness is invalid, [LazySeq](lazy-seq.md) when evaluation should be deferred or bounded, [Deque](deque.md) when both ends need efficient access, and [Set](set.md) when uniqueness matters.

## Creating a Vec

```php
use Exact\Collection\Vec;

$empty       = Vec::empty();
$fromValues  = Vec::of('book', 'pen');
$oneItem     = Vec::singleton('book');
$fromArray   = Vec::fromArray(['book', 'pen']);
$fromIter    = Vec::fromIterable(new ArrayIterator(['book', 'pen']));
```

- `Vec::empty()` creates an empty sequence.
- `Vec::of(mixed ...$items)` creates a sequence from the given values.
- `Vec::singleton(mixed $value)` creates a one-item sequence.
- `Vec::fromArray(array $items)` wraps an existing array; keys are discarded and indexes normalized to `0..n-1`.
- `Vec::fromIterable(iterable $items)` accepts any iterable; arrays avoid the intermediate iterator conversion.

All factories normalize their input to a zero-based list. Passing an associative array to `fromArray()` will keep values but drop keys.

## Querying Size and Emptiness

```php
$products = Vec::of('book', 'pen');

$hasProducts = $products->isNotEmpty(); // true
$isEmpty     = Vec::empty()->isEmpty(); // true

$count = $products->count(); // 2
$size  = $products->size();  // 2, alias for count()
```

- `isEmpty()` / `isNotEmpty()` report whether the sequence has no items.
- `count()` / `size()` return the number of items.

`Vec` implements `Countable`, so `count($vec)` (PHP's function) returns the same value as `$vec->count()`.

## Reading Items

```php
$products = Vec::of('book', 'pen', 'bag');

$first      = $products->first();      // 'book'
$alsoFirst  = $products->head();       // 'book' — alias for first()
$last       = $products->last();       // 'bag'

$firstMaybe = Vec::empty()->firstOrNull(); // null
$lastMaybe  = Vec::empty()->lastOrNull();  // null

$second     = $products->at(1);        // 'pen'
$alsoSecond = $products->get(1);       // 'pen' — alias for at()
$maybeThird = $products->atOrNull(2);  // 'bag'
$maybeTenth = $products->atOrNull(10); // null

$rest = $products->tail();             // Vec ['pen', 'bag']
```

- `first()` / `head()` return the first item. Throw `EmptyCollectionException` on an empty sequence.
- `last()` returns the last item. Throws `EmptyCollectionException` on an empty sequence.
- `firstOrNull()` / `lastOrNull()` return the item or `null`.
- `at(int $index)` / `get(int $index)` return the item at a zero-based index. Throw `OutOfBoundsException` for a negative index or an index `>= count()`.
- `atOrNull(int $index)` returns the item or `null`.
- `tail()` returns everything except the first item as a `Vec`. Throws `EmptyCollectionException` on an empty sequence.

Note that the `first*` / `last*` accessors throw `EmptyCollectionException`, while `at()` / `get()` throw `OutOfBoundsException`. The distinction reflects the reason: `first()` / `last()` are about the sequence being empty, whereas `at()` is about a specific out-of-range index.

## Checking Membership and Predicates

```php
$products = Vec::of('book', 'pen', 'bag');

$hasPen     = $products->contains('pen');    // true — strict comparison
$hasPencil  = $products->contains('pencil'); // false

$anyBook = $products->exists(
    static fn (string $p): bool => $p === 'book',
); // true

$allShort = $products->forall(
    static fn (string $p): bool => strlen($p) <= 4,
); // true
```

- `contains(mixed $value)` checks membership using strict comparison (`===`).
- `exists(callable $predicate)` returns `true` on the first match; short-circuits.
- `forall(callable $predicate)` returns `false` on the first failure; short-circuits.

Strict comparison means `1`, `'1'`, `1.0`, and `true` are four distinct values. Objects are compared by identity.

## Transforming

```php
$products = Vec::of('book', 'pen', 'bag');

$upperCased = $products->map(
    static fn (string $product): string => strtoupper($product),
); // ['BOOK', 'PEN', 'BAG']

$startsWithB = $products->filter(
    static fn (string $product): bool => str_starts_with($product, 'b'),
); // ['book', 'bag']

$notStartsWithB = $products->reject(
    static fn (string $product): bool => str_starts_with($product, 'b'),
); // ['pen']

$expanded = Vec::of('book', 'pen')->flatMap(
    static fn (string $product): Vec => Vec::of($product, "gift-{$product}"),
); // ['book', 'gift-book', 'pen', 'gift-pen']

$inspected = $products->tap(
    static fn (string $product): void => error_log($product),
);
```

- `map(callable $fn)` transforms each item. Returns a new `Vec`.
- `filter(callable $predicate)` keeps items where the predicate returns `true`.
- `reject(callable $predicate)` is `filter()` with a negated predicate.
- `flatMap(callable $fn)` expects the callback to return a `Vec` and concatenates the results. Throws `TypeError` when the callback returns anything else.
- `tap(callable $fn)` runs the callback for side effects and returns the same instance.

### `each()` — an alias for `tap()`

```php
$products->each(static function (string $product): void {
    error_log($product);
});
```

`each(callable $fn)` is an alias for `tap()`. Like `tap()`, it returns the same `Vec` instance so calls can keep chaining — it does **not** return `void`.

## Reducing

```php
$products = Vec::of('book', 'pen', 'bag');

$label = $products->foldLeft(
    'Catalog:',
    static fn (string $label, string $product): string => "{$label} {$product}",
); // 'Catalog: book pen bag'

$reversed = $products->foldRight(
    '',
    static fn (string $product, string $acc): string => "{$acc} {$product}",
); // ' bag pen book'

$concatenated = $products->reduce(
    static fn (string $acc, string $product): string => "{$acc}-{$product}",
); // 'book-pen-bag'
```

- `foldLeft($initial, callable $fn)` folds from the left. Callback receives `(accumulator, item)`.
- `foldRight($initial, callable $fn)` folds from the right. Callback receives `(item, accumulator)`.
- `reduce(callable $fn)` folds without an explicit initial value, using the first item as the seed. Callback receives `(accumulator, item)`. Throws `OutOfBoundsException` when the `Vec` is empty.

`foldRight()` reverses the array internally and folds left, so the callback's parameter order is mirrored. Use `foldLeft()` when in doubt — it is the natural left fold.

## Slicing and Limiting

```php
$products = Vec::of('a', 'b', 'c', 'd', 'e');

$firstTwo   = $products->take(2);   // ['a', 'b']
$afterTwo   = $products->drop(2);   // ['c', 'd', 'e']

$slice      = $products->slice(1, 3); // ['b', 'c', 'd']
$fromIndex2 = $products->slice(2);    // ['c', 'd', 'e']

$whileShort = Vec::of('a', 'bb', 'ccc', 'd')->takeWhile(
    static fn (string $s): bool => strlen($s) < 3,
); // ['a', 'bb']

$skipShort  = Vec::of('a', 'bb', 'ccc', 'd')->dropWhile(
    static fn (string $s): bool => strlen($s) < 3,
); // ['ccc', 'd']
```

- `take(int $count)` returns at most `$count` items from the start. `$count < 0` throws `InvalidArgumentException`.
- `drop(int $count)` returns everything after the first `$count` items. `$count < 0` throws `InvalidArgumentException`.
- `slice(int $offset, ?int $length = null)` returns a slice; delegates to `array_slice()`.
- `takeWhile(callable $predicate)` returns the longest prefix of items that satisfy the predicate.
- `dropWhile(callable $predicate)` skips the longest prefix that satisfies the predicate, then returns everything from the first non-matching item onward.

All five return a new `Vec`. `take(0)` returns an empty `Vec`; `drop(0)` returns the same items as a new instance.

## Combining and Reordering

```php
$products = Vec::of('book', 'pen');

$withBag   = $products->append('bag');    // ['book', 'pen', 'bag']
$withCover = $products->prepend('cover'); // ['cover', 'book', 'pen']

$combined = $products->concat(Vec::of('bag', 'note'));
// ['book', 'pen', 'bag', 'note']

$reversed = $products->reverse(); // ['pen', 'book']
```

- `append(mixed $value)` adds an item to the end.
- `prepend(mixed $value)` adds an item to the front.
- `concat(self $other)` concatenates two `Vec`s.
- `reverse()` returns a `Vec` with the items reversed.

## Deduplicating

```php
$products = Vec::of('book', 'pen', 'book', 1, '1');

$unique = $products->unique();
// ['book', 'pen', 1, '1'] — 'book' deduplicated, 1 and '1' kept distinct
```

`unique()` removes duplicates while preserving the order of first occurrence. The equality rule is the same as elsewhere in Exact's collections:

- Scalars and `null`: compared by `gettype()` + string cast. `1`, `'1'`, `1.0`, and `true` are four distinct values.
- Non-scalars: compared by `serialize()`. Two arrays with the same contents are equal; two objects with the same contents and same class are equal.

This is **stricter** than `Set::add()` for objects (which uses `===`, i.e. identity) and looser than `contains()` (which also uses `===`). The intent of `unique()` is structural deduplication, not identity-based.

## Zipping and Enumerating

```php
$a = Vec::of(1, 2, 3);
$b = Vec::of('a', 'b');

$zipped = $a->zip($b);
// [[1, 'a'], [2, 'b']]

$summed = $a->zipWith(
    Vec::of(10, 20, 30),
    static fn (int $x, int $y): int => $x + $y,
); // [11, 22, 33]

$indexed = Vec::of('a', 'b', 'c')->enumerate();
// [[0, 'a'], [1, 'b'], [2, 'c']]
```

- `zip(self $other)` returns a `Vec` of `[left, right]` pairs, stopping at the shorter input.
- `zipWith(self $other, callable $fn)` combines pairs with a callback, stopping at the shorter input.
- `enumerate()` returns a `Vec` of `[index, item]` pairs, starting at index `0`.

`zip()` and `zipWith()` never throw for mismatched lengths — they stop at `min(count($this), count($other))`.

## Comparing Vectors

```php
$a = Vec::of(1, 2, 3);
$b = Vec::of(1, 2, 3);
$c = Vec::of(3, 2, 1);

$a->equals($b); // true
$a->equals($c); // false — order differs
```

`equals(self $other)` compares the underlying lists with strict equality (`===`). Both values and their order must match.

## Interop

```php
$products = Vec::of('book', 'pen');

$array = $products->toArray(); // ['book', 'pen']

foreach ($products as $index => $value) {
    // iterate in order; $index is 0..n-1
}

$json = json_encode($products);
// ["book","pen"]
```

- `toArray()` returns the items as a zero-based list.
- `getIterator()` yields items with integer indexes starting at `0`.
- `jsonSerialize()` produces a JSON array.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(): self` | Create an empty sequence. |
| `of` | `static of(mixed ...$items): self` | Create from the given values. |
| `singleton` | `static singleton(mixed $value): self` | Create a one-item sequence. |
| `fromArray` | `static fromArray(array $items): self` | Wrap an array; keys discarded. |
| `fromIterable` | `static fromIterable(iterable $items): self` | Wrap any iterable. |
| `isEmpty` | `isEmpty(): bool` | Report whether the sequence has no items. |
| `isNotEmpty` | `isNotEmpty(): bool` | Report whether the sequence has at least one item. |
| `count` | `count(): int` | Number of items. |
| `size` | `size(): int` | Alias for `count()`. |
| `at` | `at(int $index): mixed` | Item at a zero-based index; throws `OutOfBoundsException`. |
| `atOrNull` | `atOrNull(int $index): mixed` | Item or `null`. |
| `first` | `first(): mixed` | First item; throws `EmptyCollectionException` on empty. |
| `last` | `last(): mixed` | Last item; throws `EmptyCollectionException` on empty. |
| `firstOrNull` | `firstOrNull(): mixed` | First item or `null`. |
| `lastOrNull` | `lastOrNull(): mixed` | Last item or `null`. |
| `head` | `head(): mixed` | Alias for `first()`. |
| `tail` | `tail(): self` | Everything except the first item; throws `EmptyCollectionException` on empty. |
| `get` | `get(int $index): mixed` | Alias for `at()`. |
| `map` | `map(callable $fn): self` | Transform each item. |
| `filter` | `filter(callable $predicate): self` | Keep items that satisfy a predicate. |
| `reject` | `reject(callable $predicate): self` | Drop items that satisfy a predicate. |
| `flatMap` | `flatMap(callable $fn): self` | Concatenate the `Vec` returned for each item; throws `TypeError` otherwise. |
| `foldLeft` | `foldLeft(mixed $initial, callable $fn): mixed` | Fold from the left; callback receives `(accumulator, item)`. |
| `foldRight` | `foldRight(mixed $initial, callable $fn): mixed` | Fold from the right; callback receives `(item, accumulator)`. |
| `reduce` | `reduce(callable $fn): mixed` | Fold using the first item as seed; throws `OutOfBoundsException` on empty. |
| `contains` | `contains(mixed $value): bool` | Check membership using strict comparison. |
| `exists` | `exists(callable $predicate): bool` | True if any item matches; short-circuits. |
| `forall` | `forall(callable $predicate): bool` | True if all items match; short-circuits. |
| `equals` | `equals(self $other): bool` | Compare items and their order. |
| `append` | `append(mixed $value): self` | Add to the end. |
| `prepend` | `prepend(mixed $value): self` | Add to the front. |
| `concat` | `concat(self $other): self` | Concatenate two sequences. |
| `reverse` | `reverse(): self` | Reverse the items. |
| `slice` | `slice(int $offset, ?int $length = null): self` | Slice like `array_slice()`. |
| `take` | `take(int $count): self` | First `$count` items; negative count throws `InvalidArgumentException`. |
| `drop` | `drop(int $count): self` | Everything after the first `$count` items; negative count throws. |
| `takeWhile` | `takeWhile(callable $predicate): self` | Longest prefix satisfying the predicate. |
| `dropWhile` | `dropWhile(callable $predicate): self` | Skip the longest prefix satisfying the predicate. |
| `unique` | `unique(): self` | Remove structural duplicates; preserves first occurrence. |
| `zip` | `zip(self $other): self` | Pair items with `$other`; stops at the shorter. |
| `zipWith` | `zipWith(self $other, callable $fn): self` | Pair items with a callback; stops at the shorter. |
| `enumerate` | `enumerate(): self` | Yield `[index, item]` pairs. |
| `tap` | `tap(callable $fn): self` | Side effect per item; returns the same instance. |
| `each` | `each(callable $fn): self` | Alias for `tap()`; returns the same instance. |
| `toArray` | `toArray(): array` | Return items as a list. |
| `getIterator` | `getIterator(): Traversable` | Iterate items in order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize items as a JSON array. |

## Next Steps

- Use [NonEmptyVec](non-empty-vec.md) when emptiness is not valid.
- Use [LazySeq](lazy-seq.md) when evaluation should be deferred or bounded.
- Use [Deque](deque.md) when both ends need efficient access.
- Use [Set](set.md) when uniqueness matters and order is not required.
- See [Collections Overview](overview.md) to choose a collection.
