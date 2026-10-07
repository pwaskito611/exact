# NonEmptyMap

`NonEmptyMap` is a key-value map that guarantees at least one entry. Use it when an empty mapping is invalid in the domain.

## Quick Example

```php
use Exact\Collection\NonEmptyMap;

$prices = NonEmptyMap::singleton('book', 12)
    ->put('pen', 3);

$firstEntry = $prices->head(); // ['book', 12]
$bookPrice = $prices->get('book');
$allPrices = $prices->toMap();
```

`map()` and `put()` preserve the non-empty invariant. `filter()` and `remove()` may remove the last entry and therefore return an ordinary `Map`. `fromMap()` and `fromArray()` also reject empty input with `InvalidArgumentException`.

`get()` throws `OutOfBoundsException` for a missing key; `getOrNull()` is available when absence is expected. Both `map()` and `filter()` callbacks receive the value and key.

## When to Use NonEmptyMap

Use `NonEmptyMap` when the domain requires at least one entry:

- A configuration that must contain a default.
- A pricing table with a guaranteed base entry.
- A routing table where "no routes" is a domain error.

Use [Map](map.md) when emptiness is a valid state, and [NonEmptyVec](non-empty-vec.md) / [NonEmptyLazySeq](non-empty-lazy-seq.md) when the non-empty guarantee is about sequences rather than key-value entries.

## The Non-Empty Invariant

The invariant is enforced at construction: the private constructor throws `InvalidArgumentException` if the given array is empty. Every factory and every operation that could violate the invariant either:

- Returns `self` when the result is guaranteed non-empty (`put()`, `map()`, `merge()`, `tap()`), or
- Returns `Map` when the result may be empty (`remove()`, `filter()`).

This means callers can rely on `NonEmptyMap` being non-empty at runtime without additional checks.

## Creating a NonEmptyMap

```php
use Exact\Collection\Map;
use Exact\Collection\NonEmptyMap;

$single     = NonEmptyMap::singleton('book', 12);
$fromArray  = NonEmptyMap::fromArray(['book' => 12, 'pen' => 3]);
$fromMap    = NonEmptyMap::fromMap(Map::of('book', 12));
$fromIter   = NonEmptyMap::fromIterable(new ArrayIterator(['book' => 12]));
```

- `NonEmptyMap::singleton(int|string $key, mixed $value)` creates a map with a single entry.
- `NonEmptyMap::fromArray(array $items)` requires a non-empty array; an empty array throws `InvalidArgumentException`.
- `NonEmptyMap::fromMap(Map $map)` converts a `Map`; throws `InvalidArgumentException` when the map is empty.
- `NonEmptyMap::fromIterable(iterable $items)` accepts any iterable; empty input throws `InvalidArgumentException`.

There is no `empty()` factory — the type exists precisely to prevent that state.

## Reading the First Entry

```php
$prices = NonEmptyMap::singleton('book', 12)->put('pen', 3);

[$firstKey, $firstValue] = $prices->head();
// $firstKey   === 'book'
// $firstValue === 12

$entry = $prices->head(); // ['book', 12]
```

`head()` returns the first entry as a two-element array `[key, value]`. Because `NonEmptyMap` always has at least one entry, `head()` never throws and never returns `null`.

The first entry is the one inserted first. `put()` appends to the end (or overwrites in place), so the first inserted key remains the head unless it is removed.

## Looking Up a Key

```php
$prices = NonEmptyMap::singleton('book', 12)->put('pen', 3);

$bookPrice = $prices->get('book');        // 12
$maybe     = $prices->getOrNull('bag');   // null
$hasBook   = $prices->has('book');        // true
$hasBag    = $prices->has('bag');         // false
```

- `get(int|string $key)` returns the value or throws `OutOfBoundsException` when the key is absent.
- `getOrNull(int|string $key)` returns the value or `null`.
- `has(int|string $key)` returns `true` when the key is present.

### A subtlety of `getOrNull()`

`getOrNull()` uses PHP's `?? null`, so a key whose value is `null` is indistinguishable from a missing key:

```php
$map = NonEmptyMap::singleton('a', null);

$map->has('a');        // true
$map->getOrNull('a');  // null — same as a missing key
```

Unlike [Map](map.md)'s `getOrNull()`, which uses `array_key_exists()`, this `NonEmptyMap` method does not distinguish the two cases. Use `has()` when the distinction matters.

## Reading Keys and Values

```php
$prices = NonEmptyMap::singleton('book', 12)->put('pen', 3);

$keys     = $prices->keys();    // ['book', 'pen']
$values   = $prices->values();  // [12, 3]
$count    = $prices->count();   // 2
$size     = $prices->size();    // 2, alias for count()
```

- `keys()` returns the keys in insertion order.
- `values()` returns the values in insertion order.
- `count()` / `size()` return the number of entries.

`keys()` and `values()` are parallel: `$values[$i]` belongs to `$keys[$i]`. `NonEmptyMap` does not implement `Countable`, so `count($nonEmptyMap)` is not the way to get the size — use `$nonEmptyMap->count()`.

## Adding Entries

```php
$prices = NonEmptyMap::singleton('book', 12);

$withPen = $prices->put('pen', 3);       // ['book' => 12, 'pen' => 3]
$updated = $prices->put('book', 15);     // ['book' => 15]
```

`put(int|string $key, mixed $value)` returns a new `NonEmptyMap` with the entry set. Overwrites an existing key. Because adding an entry cannot make the map empty, the return type stays `NonEmptyMap`.

## Removing Entries

```php
$prices = NonEmptyMap::singleton('book', 12)->put('pen', 3);

$withoutPen = $prices->remove('pen');    // Map ['book' => 12]
$asMap      = $prices->remove('book');   // Map ['pen' => 3]
$stillNonEmpty = $prices->remove('bag'); // Map ['book' => 12, 'pen' => 3]
```

`remove(int|string $key)` returns a [Map](map.md), not a `NonEmptyMap`, because removing the last entry would violate the invariant. Removing an absent key is a no-op that still returns a `Map`.

To convert the result back when you know it is non-empty:

```php
$stillNonEmpty = NonEmptyMap::fromMap($prices->remove('bag'));
```

## Transforming Entries

```php
$prices = NonEmptyMap::singleton('book', 12)->put('pen', 3);

// map: (value, key)
$doubled = $prices->map(
    static fn (int $price, string $product): int => $price * 2,
);
// NonEmptyMap ['book' => 24, 'pen' => 6]

// filter: (value, key)
$cheaperThan5 = $prices->filter(
    static fn (int $price, string $product): bool => $price < 5,
);
// Map ['pen' => 3]
```

- `map(callable $fn)` transforms values; the callback receives `(value, key)`. Returns `NonEmptyMap` because mapping preserves the entry count.
- `filter(callable $predicate)` keeps entries where the predicate is `true`; the callback receives `(value, key)`. Returns [Map](map.md) because the result may be empty.

Both iterate entries in insertion order. `filter()` never throws, even when all entries are filtered out — the result is a plain `Map`, possibly empty.

## Reducing, Merging, and Side Effects

```php
$prices = NonEmptyMap::singleton('book', 12)->put('pen', 3);

// fold: (accumulator, value, key)
$total = $prices->fold(
    0,
    static fn (int $acc, int $price, string $product): int => $acc + $price,
); // 15

$merged = NonEmptyMap::singleton('book', 12)->merge(
    NonEmptyMap::singleton('pen', 3),
);
// NonEmptyMap ['book' => 12, 'pen' => 3]

$overwritten = NonEmptyMap::singleton('book', 12)->merge(
    NonEmptyMap::singleton('book', 20),
);
// NonEmptyMap ['book' => 20]

$inspected = $prices->tap(
    static fn (int $price, string $product): void => error_log("{$product}: {$price}"),
);
```

- `fold($initial, callable $fn)` reduces entries; the callback receives `(accumulator, value, key)`.
- `merge(self $other)` combines two `NonEmptyMap` instances. Because both inputs are non-empty, the union is non-empty, so the return type is `NonEmptyMap`. Entries from `$other` overwrite matching keys in `$this`.
- `tap(callable $fn)` runs the callback for side effects and returns the same instance. The callback receives `(value, key)`.

## Converting to Map

```php
use Exact\Collection\Map;
use Exact\Collection\NonEmptyMap;

$prices = NonEmptyMap::singleton('book', 12);

$map = $prices->toMap(); // Map ['book' => 12]
```

`toMap()` returns a `Map` view of the same entries. Use it at API boundaries where the non-empty guarantee is no longer needed.

## Comparing NonEmptyMaps

```php
$a = NonEmptyMap::fromArray(['book' => 12, 'pen' => 3]);
$b = NonEmptyMap::fromArray(['book' => 12, 'pen' => 3]);
$c = NonEmptyMap::fromArray(['pen' => 3, 'book' => 12]);
$d = NonEmptyMap::fromArray(['book' => 15, 'pen' => 3]);

$a->equals($b); // true
$a->equals($c); // false — different key order
$a->equals($d); // false — different value
```

`equals(self $other)` compares the underlying arrays with `===`, which checks both key-value pairs **and their order**. Unlike [Map](map.md)'s order-independent `equals()`, `NonEmptyMap`'s comparison is order-sensitive.

If you need order-independent comparison, convert both to `Map` first:

```php
$a->toMap()->equals($c->toMap()); // true
```

## Interop

```php
$prices = NonEmptyMap::singleton('book', 12)->put('pen', 3);

$array = $prices->toArray();
// ['book' => 12, 'pen' => 3]

foreach ($prices as $key => $value) {
    // iterate in insertion order
}

$json = json_encode($prices);
// {"book":12,"pen":3}
```

- `toArray()` returns the keyed PHP array.
- `getIterator()` yields entries in insertion order, preserving keys.
- `jsonSerialize()` produces the same keyed array, so `json_encode()` emits a JSON object.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `singleton` | `static singleton(int\|string $key, mixed $value): self` | Create a map with one entry. |
| `fromArray` | `static fromArray(array $items): self` | Create from a non-empty array; empty input throws `InvalidArgumentException`. |
| `fromMap` | `static fromMap(Map $map): self` | Convert a `Map`; empty input throws `InvalidArgumentException`. |
| `fromIterable` | `static fromIterable(iterable $items): self` | Wrap any iterable; empty input throws `InvalidArgumentException`. |
| `head` | `head(): array` | First entry as `[key, value]`. Never throws. |
| `get` | `get(int\|string $key): mixed` | Return the value; throws `OutOfBoundsException` when absent. |
| `getOrNull` | `getOrNull(int\|string $key): mixed` | Return the value or `null`. Uses `??` — a stored `null` looks absent. |
| `has` | `has(int\|string $key): bool` | Check whether a key is present. |
| `keys` | `keys(): array` | Return keys in insertion order. |
| `values` | `values(): array` | Return values in insertion order. |
| `count` | `count(): int` | Number of entries. |
| `size` | `size(): int` | Alias for `count()`. |
| `put` | `put(int\|string $key, mixed $value): self` | Set an entry; overwrites an existing key. Returns `NonEmptyMap`. |
| `remove` | `remove(int\|string $key): Map` | Remove an entry; returns `Map` because the result may be empty. |
| `map` | `map(callable $fn): self` | Transform values; callback receives `(value, key)`. Returns `NonEmptyMap`. |
| `filter` | `filter(callable $predicate): Map` | Keep entries by value; callback receives `(value, key)`. Returns `Map`. |
| `fold` | `fold(mixed $initial, callable $fn): mixed` | Reduce entries; callback receives `(accumulator, value, key)`. |
| `merge` | `merge(self $other): self` | Union of two non-empty maps; entries from `$other` overwrite matching keys. |
| `tap` | `tap(callable $fn): self` | Side effect per entry; returns the same instance. Callback receives `(value, key)`. |
| `toMap` | `toMap(): Map` | Convert to a plain `Map`. |
| `equals` | `equals(self $other): bool` | Compare entries with `===`; key order matters. |
| `toArray` | `toArray(): array` | Return the keyed array. |
| `getIterator` | `getIterator(): Traversable` | Iterate entries in insertion order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize the keyed array. |

## Next Steps

- See [Map](map.md) for maps that may be empty.
- See [SortedMap](sorted-map.md) for comparator-ordered keys.
- See [Collections Overview](overview.md) to choose a collection.