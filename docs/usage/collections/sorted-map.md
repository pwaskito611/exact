# SortedMap

`SortedMap` stores key-value pairs ordered by a comparator. Keys are integers or strings; values retain their keys during transformations.

## Quick Example

```php
use Exact\Collection\SortedMap;

$byCode = SortedMap::fromArray(
    ['CA' => 'Canada', 'AU' => 'Australia', 'US' => 'United States'],
    static fn (int|string $left, int|string $right): int => $left <=> $right,
);

$orderedCodes = $byCode->keys(); // ['AU', 'CA', 'US']
$northAmerica = $byCode->range('CA', 'US');
```

`put()` maintains order, and `remove()` returns a new map. `range($low, $high)` includes keys between the bounds according to the comparator. `map()` transforms values while preserving keys; `mapKeys()` transforms keys and re-sorts the result. `merge()` uses values from the other map when keys overlap.

The comparator defines the ordering contract and should return a negative integer, zero, or a positive integer. `empty()` uses PHP's spaceship comparison by default; `fromArray()` and `fromIterable()` require a comparator.

## When to Use SortedMap

Use `SortedMap` when:

- Keys must be kept in a defined order, not insertion order.
- You want range queries over the key domain.
- Iteration, `keys()`, `values()`, and `entries()` must follow that order.

Use [Map](map.md) for insertion-order key-value data, [BiMap](bimap.md) for one-to-one reverse lookup, and [NonEmptyMap](non-empty-map.md) when an empty mapping is invalid.

## Comparator Semantics

The comparator is a callable `(int|string $a, int|string $b): int` returning an integer like `<=>`:

- Negative — `$a` sorts before `$b`.
- Zero — `$a` and `$b` are considered equal in order.
- Positive — `$a` sorts after `$b`.

The default comparator is `fn ($a, $b) => $a <=> $b`, used by `empty()` when no comparator is provided. `fromArray()` and `fromIterable()` require a comparator explicitly.

Custom orderings are possible:

```php
use Exact\Collection\SortedMap;

// Reverse lexicographic order
$descending = SortedMap::fromArray(
    ['b' => 2, 'a' => 1, 'c' => 3],
    static fn (int|string $a, int|string $b): int => $b <=> $a,
);

$descending->keys(); // ['c', 'b', 'a']
```

The comparator is stored on every derived map, so `put()`, `remove()`, `map()`, and `filter()` preserve the ordering contract.

## Creating a SortedMap

```php
use Exact\Collection\SortedMap;

$default    = SortedMap::empty();                       // spaceship comparator
$custom     = SortedMap::empty(
    static fn (int|string $a, int|string $b): int => $b <=> $a,
);

$fromArray  = SortedMap::fromArray(
    ['b' => 2, 'a' => 1],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$fromIter   = SortedMap::fromIterable(
    new ArrayIterator(['b' => 2, 'a' => 1]),
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);
```

- `SortedMap::empty(?callable $comparator = null)` creates an empty map. Falls back to the spaceship comparator.
- `SortedMap::fromArray(array $items, callable $comparator)` builds from an array; the comparator is required.
- `SortedMap::fromIterable(iterable $items, callable $comparator)` builds from any iterable; the comparator is required.

`fromArray()` and `fromIterable()` insert entries one by one via `put()`, so each step maintains the sort order.

## Adding and Removing Entries

```php
$map = SortedMap::fromArray(
    ['b' => 2],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$withA = $map->put('a', 1);      // ['a' => 1, 'b' => 2]
$withC = $map->put('c', 3);      // ['b' => 2, 'c' => 3]

$updated = $map->put('b', 20);   // ['b' => 20] — value replaced, position maintained

$removed = $map->remove('b');    // [] — new instance
$noOp    = $map->remove('z');    // same instance — key absent
```

### `put(int|string $key, mixed $value)`

Sets the entry and re-sorts using the comparator (`uksort`). Returns a new `SortedMap`. If the key already exists, its value is replaced and its position is recomputed. The original map is unchanged.

### `remove(int|string $key)`

Returns a new `SortedMap` without the key. If the key is absent, the same instance is returned — no new allocation.

## Looking Up a Key

```php
$map = SortedMap::fromArray(
    ['AU' => 'Australia', 'CA' => 'Canada', 'US' => 'United States'],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$ca          = $map->get('CA');            // 'Canada'
$maybe       = $map->getOrNull('MX');      // null
$fallback    = $map->getOrElse('MX', '—');  // '—'
$hasCA       = $map->has('CA');            // true
```

- `get(int|string $key)` returns the value or throws `OutOfBoundsException` when the key is absent.
- `getOrNull(int|string $key)` returns the value or `null`.
- `getOrElse(int|string $key, mixed $default)` returns the value or the given default.
- `has(int|string $key)` checks key existence using `array_key_exists()`.

### A subtlety: `getOrNull()` vs `getOrElse()` vs `has()`

`getOrNull()` uses `$this->items[$key] ?? null`, so a key whose value is `null` looks absent. `getOrElse()` and `has()` use `array_key_exists()`, so a key with a `null` value is correctly reported as present:

```php
$map = SortedMap::fromArray(
    ['flag' => null],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$map->has('flag');              // true
$map->getOrElse('flag', 'x');   // null — correct
$map->getOrNull('flag');        // null — same as absent
```

Use `has()` when the distinction matters. This inconsistency mirrors the behavior of [NonEmptyMap](non-empty-map.md), and differs from [Map](map.md) whose `getOrNull()` uses `array_key_exists()`.

## Boundary Keys

```php
$map = SortedMap::fromArray(
    ['AU' => 'Australia', 'CA' => 'Canada', 'US' => 'United States'],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$first = $map->firstKey(); // 'AU'
$last  = $map->lastKey();  // 'US'

SortedMap::empty()->firstKey(); // null
SortedMap::empty()->lastKey();  // null
```

- `firstKey()` returns the smallest key by the comparator, or `null` when empty.
- `lastKey()` returns the largest key by the comparator, or `null` when empty.

## Size and Emptiness

```php
$map = SortedMap::fromArray(
    ['a' => 1, 'b' => 2],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$count   = $map->count();     // 2
$size    = $map->size();      // 2, alias for count()
$isEmpty = SortedMap::empty()->isEmpty(); // true
```

- `count()` / `size()` return the number of entries.
- `isEmpty()` reports whether the map has no entries.

`SortedMap` implements `Countable`, so `count($sortedMap)` (PHP's function) returns the same value as `$sortedMap->count()`.

## Reading the Contents

```php
$map = SortedMap::fromArray(
    ['CA' => 'Canada', 'AU' => 'Australia', 'US' => 'United States'],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$keys    = $map->keys();    // ['AU', 'CA', 'US']
$values  = $map->values();  // ['Australia', 'Canada', 'United States']
$entries = $map->entries(); // [['AU', 'Australia'], ['CA', 'Canada'], ['US', 'United States']]
```

- `keys()` returns the keys in comparator order.
- `values()` returns the values in comparator order.
- `entries()` returns `[key, value]` pairs as lists, in comparator order.

`keys()` and `values()` are parallel: `$values[$i]` belongs to `$keys[$i]`.

## Range Queries

```php
$map = SortedMap::fromArray(
    ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$middle = $map->range('B', 'C'); // ['B' => 2, 'C' => 3]
$all    = $map->range('A', 'D'); // same as $map
$none   = $map->range('X', 'Z'); // empty SortedMap
```

`range(int|string $low, int|string $high)` returns a new `SortedMap` containing only entries whose key satisfies `$low <= $key <= $high` under the comparator. **Both endpoints are inclusive.**

The result uses the same comparator as the original map. If `$low` is greater than `$high` under the comparator, the result is empty.

## Transforming Entries

The callback signatures differ between methods; check the parameter order carefully.

```php
$map = SortedMap::fromArray(
    ['b' => 2, 'a' => 1, 'c' => 3],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

// map: (value, key) — keys preserved, sorted order preserved
$doubled = $map->map(
    static fn (int $value, string $key): int => $value * 2,
);

// mapKeys: (key, value) — keys transformed, result re-sorted
$upperCased = $map->mapKeys(
    static fn (string $key, int $value): string => strtoupper($key),
);

// filter: (value, key) — entries kept, order preserved
$smaller = $map->filter(
    static fn (int $value, string $key): bool => $value < 3,
);
```

- `map(callable $fn)` transforms values; the callback receives `(value, key)`. Returns a `SortedMap` with the same keys and comparator.
- `mapKeys(callable $fn)` transforms keys; the callback receives `(key, value)`. Returns a new `SortedMap` re-sorted by the comparator. If two keys collide, the later one wins.
- `filter(callable $predicate)` keeps entries where the predicate is `true`; the callback receives `(value, key)`.

`map()` and `filter()` preserve the keys, so the underlying array remains sorted and no re-sorting is performed. `mapKeys()` uses `put()` internally, so the result is re-sorted by the comparator.

## Reducing and Merging

```php
$map = SortedMap::fromArray(
    ['b' => 2, 'a' => 1],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

// fold: (accumulator, value, key)
$sum = $map->fold(
    0,
    static fn (int $acc, int $value, string $key): int => $acc + $value,
); // 3

$merged = $map->merge(
    SortedMap::fromArray(
        ['c' => 3, 'b' => 20],
        static fn (int|string $a, int|string $b): int => $a <=> $b,
    ),
);
// ['a' => 1, 'b' => 20, 'c' => 3] — 'b' overwritten by $other
```

- `fold($initial, callable $fn)` reduces entries in comparator order; the callback receives `(accumulator, value, key)`.
- `merge(self $other)` returns a new `SortedMap` where entries from `$other` overwrite matching keys in `$this`. Both maps must share the same comparator contract for the result to be sensible; the returned map keeps `$this`'s comparator.

## Comparing Maps

```php
$a = SortedMap::fromArray(
    ['a' => 1, 'b' => 2],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);
$b = SortedMap::fromArray(
    ['b' => 2, 'a' => 1],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);
$c = SortedMap::fromArray(
    ['a' => 1, 'b' => 3],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$a->equals($b); // true — both sort to ['a' => 1, 'b' => 2]
$a->equals($c); // false — different value
```

`equals(self $other)` compares the underlying arrays with `===`. Because `SortedMap` keeps its entries sorted by the comparator, two maps built with the same comparator have the same internal order and compare equal when they hold the same key-value pairs.

Maps built with **different** comparators will generally compare unequal even when they hold the same entries, because the internal orders differ. Compare `.toArray()` after normalizing if you need comparator-independent equality.

## Interop

```php
$map = SortedMap::fromArray(
    ['b' => 2, 'a' => 1],
    static fn (int|string $a, int|string $b): int => $a <=> $b,
);

$array = $map->toArray();
// ['a' => 1, 'b' => 2] — sorted order

foreach ($map as $key => $value) {
    // iterate in comparator order
}

$json = json_encode($map);
// {"a":1,"b":2}
```

- `toArray()` returns the keyed PHP array in comparator order.
- `getIterator()` yields entries in comparator order, preserving keys.
- `jsonSerialize()` produces the same keyed array, so `json_encode()` emits a JSON object.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(?callable $comparator = null): self` | Create an empty map. Uses the spaceship comparator by default. |
| `fromArray` | `static fromArray(array $items, callable $comparator): self` | Create from an array; comparator required. |
| `fromIterable` | `static fromIterable(iterable $items, callable $comparator): self` | Create from any iterable; comparator required. |
| `put` | `put(int\|string $key, mixed $value): self` | Set an entry; re-sorts using the comparator. |
| `remove` | `remove(int\|string $key): self` | Remove an entry; returns the same instance when the key is absent. |
| `get` | `get(int\|string $key): mixed` | Return the value; throws `OutOfBoundsException` when absent. |
| `getOrNull` | `getOrNull(int\|string $key): mixed` | Return the value or `null`. Uses `??` — a stored `null` looks absent. |
| `getOrElse` | `getOrElse(int\|string $key, mixed $default): mixed` | Return the value or the given default. Uses `array_key_exists()`. |
| `has` | `has(int\|string $key): bool` | Check key existence with `array_key_exists()`. |
| `firstKey` | `firstKey(): int\|string\|null` | Smallest key by comparator, or `null`. |
| `lastKey` | `lastKey(): int\|string\|null` | Largest key by comparator, or `null`. |
| `isEmpty` | `isEmpty(): bool` | Report whether the map has no entries. |
| `count` | `count(): int` | Number of entries. |
| `size` | `size(): int` | Alias for `count()`. |
| `keys` | `keys(): array` | Keys in comparator order. |
| `values` | `values(): array` | Values in comparator order. |
| `entries` | `entries(): array` | `[key, value]` pairs in comparator order. |
| `range` | `range(int\|string $low, int\|string $high): self` | Entries with `$low <= key <= $high` under the comparator. |
| `map` | `map(callable $fn): self` | Transform values; callback receives `(value, key)`. Keys and order preserved. |
| `mapKeys` | `mapKeys(callable $fn): self` | Transform keys; callback receives `(key, value)`. Result re-sorted. |
| `filter` | `filter(callable $predicate): self` | Keep entries by value; callback receives `(value, key)`. |
| `fold` | `fold(mixed $initial, callable $fn): mixed` | Reduce entries in comparator order; callback receives `(accumulator, value, key)`. |
| `merge` | `merge(self $other): self` | Union; entries from `$other` overwrite matching keys. |
| `equals` | `equals(self $other): bool` | Strict `===` comparison of the internal sorted array. |
| `toArray` | `toArray(): array` | Keyed array in comparator order. |
| `getIterator` | `getIterator(): Traversable` | Iterate entries in comparator order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize the keyed array. |

## Next Steps

- See [Map](map.md) for insertion-order key-value data.
- See [Map variants](map-variants.md) for `BiMap` and `NonEmptyMap`.
- See [SortedSet](sorted-set.md) for comparator-ordered unique values.
- See [Collections Overview](overview.md) to choose a collection.