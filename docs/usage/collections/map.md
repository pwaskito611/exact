# Map

`Map` stores values under string or integer keys. It is immutable: every update returns a new map.

## Quick Example

```php
use Exact\Collection\Map;

$prices = Map::of('book', 10, 'pen', 3);
$priceList = $prices
    ->map(static fn (int $price, string $product): string => "{$product}: {$price}")
    ->put('bag', 'bag: 25')
    ->remove('pen')
    ->toArray();
```

`put()` and `remove()` return updated maps; `$prices` remains unchanged.

## When to Use Map

Use `Map` when the entry's key carries meaning and you want keyed lookup. Use [Vec](vec.md) for ordered values without keys, and [Set](set.md) when uniqueness matters more than keys.

## Creating a Map

```php
use Exact\Collection\Map;

$empty      = Map::empty();
$fromArray  = Map::fromArray(['book' => 10, 'pen' => 3]);
$fromIter   = Map::fromIterable(new ArrayIterator(['book' => 10]));
$single     = Map::singleton('book', 10);
$prices     = Map::of('book', 10, 'pen', 3);
```

- `Map::empty()` creates a map with no entries.
- `Map::of(...$items)` accepts alternating key/value arguments.
- `Map::singleton($key, $value)` creates a map with one entry.
- `Map::fromArray(array $items)` wraps an existing PHP array.
- `Map::fromIterable(iterable $items)` accepts any iterable; arrays skip the iterator conversion.

An odd number of arguments to `of()` throws `InvalidArgumentException`:

```php
Map::of('book', 10, 'pen'); // InvalidArgumentException
```

The constructor is private; use the factories.

## Checking for a Key

```php
$prices = Map::of('book', 10, 'pen', 3);

$hasBook    = $prices->has('book');    // true
$missingBag = $prices->missing('bag'); // true

$isEmpty    = Map::empty()->isEmpty();      // true
$isNotEmpty = $prices->isNotEmpty();        // true

$count = $prices->count(); // 2
$size  = $prices->size();  // 2, alias for count()
```

- `has($key)` returns `true` when the key is present.
- `missing($key)` returns `true` when the key is absent.
- `isEmpty()` / `isNotEmpty()` report whether the map has no entries.
- `count()` / `size()` return the number of entries.

`has()` uses `array_key_exists()`, so a key with a `null` value counts as present.

## Looking Up a Key

```php
$prices = Map::of('book', 10, 'pen', 3);

$bookPrice = $prices->get('book');                  // 10
$bagPrice  = $prices->getOrElse('bag', 25);         // 25
$bagPrice  = $prices->getOrElseGet('bag', static fn (): int => 25);
$maybeBook = $prices->getOrNull('book');            // 10
$maybeBag  = $prices->getOrNull('bag');             // null
```

- `get($key)` returns the value or throws `OutOfBoundsException` when the key is absent.
- `getOrNull($key)` returns the value or `null`.
- `getOrElse($key, $default)` returns the value or the given default.
- `getOrElseGet($key, callable $default)` returns the value or evaluates the default lazily.

All four use `array_key_exists()`, so a stored `null` is returned as `null` — not replaced by the fallback. Use `has()` when you need to distinguish a missing key from a key holding `null`.

## Adding and Removing Entries

```php
$prices = Map::of('book', 10);

$withBag    = $prices->put('bag', 25);       // ['book' => 10, 'bag' => 25]
$withoutPen = $prices->remove('pen');        // no-op if 'pen' is absent

$replaced = $prices->put('book', 12);        // ['book' => 12]
```

- `put($key, $value)` returns a new `Map` with the entry set. Overwrites an existing key.
- `remove($key)` returns a new `Map` without that entry; if the key is absent, returns the same instance.

## Reading Keys, Values, and Entries

```php
$prices = Map::of('book', 10, 'pen', 3);

$keys    = $prices->keys();    // ['book', 'pen']
$values  = $prices->values();  // [10, 3]
$entries = $prices->entries(); // [['book', 10], ['pen', 3]]
```

- `keys()` returns the keys in insertion order.
- `values()` returns the values in insertion order.
- `entries()` returns `[key, value]` pairs as lists.

`keys()` and `values()` are parallel: `$values[$i]` belongs to `$keys[$i]`.

## Transforming Entries

The callback signature for each method differs; check the parameter order carefully.

```php
$prices = Map::of('book', 10, 'pen', 3);

// map: (value, key)
$doubled = $prices->map(
    static fn (int $price, string $product): int => $price * 2,
);

// mapKeys: (key, value)
$upperCased = $prices->mapKeys(
    static fn (string $product, int $price): string => strtoupper($product),
);

// filter: (value, key)
$cheaperThan5 = $prices->filter(
    static fn (int $price, string $product): bool => $price < 5,
);

// filterKeys: (key, value)
$onlyBook = $prices->filterKeys(
    static fn (string $product, int $price): bool => $product === 'book',
);
```

- `map(callable $fn)` transforms values; callback receives `(value, key)`.
- `mapKeys(callable $fn)` transforms keys; callback receives `(key, value)`. If two keys collide, the later one wins.
- `filter(callable $predicate)` keeps entries where the predicate is `true`; callback receives `(value, key)`.
- `filterKeys(callable $predicate)` keeps entries based on the key; callback receives `(key, value)`.

`mapKeys()` can produce duplicate keys; the last entry written wins. No exception is thrown.

## Reducing, Merging, and Side Effects

```php
$prices = Map::of('book', 10, 'pen', 3);

// fold: (accumulator, value, key)
$total = $prices->fold(
    0,
    static fn (int $acc, int $price, string $product): int => $acc + $price,
); // 13

$merged = Map::of('book', 10)->merge(Map::of('pen', 3));
// ['book' => 10, 'pen' => 3]

$inspected = $prices->tap(
    static fn (int $price, string $product): void => error_log("{$product}: {$price}"),
);
```

- `fold($initial, callable $fn)` reduces entries; callback receives `(accumulator, value, key)`.
- `merge(self $other)` returns a new map where entries from `$other` overwrite matching keys.
- `tap(callable $fn)` runs the callback for side effects and returns the same instance. Callback receives `(value, key)`.

## Comparing Maps

```php
$a = Map::of('book', 10, 'pen', 3);
$b = Map::of('pen', 3, 'book', 10);  // same pairs, different insertion order
$c = Map::of('book', 10, 'pen', 4);

$a->equals($b); // true
$a->equals($c); // false
```

`equals(self $other)` compares entries by key and value using strict equality. Insertion order does not matter — `$a` and `$b` are equal because they hold the same key/value pairs.

## Interop

```php
$prices = Map::of('book', 10, 'pen', 3);

$array = $prices->toArray();
// ['book' => 10, 'pen' => 3]

foreach ($prices as $key => $value) {
    // iterate in insertion order
}

$json = json_encode($prices);
// {"book":10,"pen":3}
```

- `toArray()` returns the keyed PHP array.
- `getIterator()` yields entries in insertion order, preserving keys.
- `jsonSerialize()` produces the same keyed array, so `json_encode()` emits a JSON object.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(): self` | Create an empty map. |
| `of` | `static of(mixed ...$items): self` | Create from alternating key/value arguments; odd count throws `InvalidArgumentException`. |
| `singleton` | `static singleton(mixed $key, mixed $value): self` | Create a map with one entry. |
| `fromArray` | `static fromArray(array $items): self` | Wrap a PHP array. |
| `fromIterable` | `static fromIterable(iterable $items): self` | Wrap any iterable. |
| `has` | `has(mixed $key): bool` | Check whether a key is present. |
| `missing` | `missing(mixed $key): bool` | Check whether a key is absent. |
| `isEmpty` | `isEmpty(): bool` | Report whether the map has no entries. |
| `isNotEmpty` | `isNotEmpty(): bool` | Report whether the map has at least one entry. |
| `count` | `count(): int` | Return the number of entries. |
| `size` | `size(): int` | Alias for `count()`. |
| `get` | `get(mixed $key): mixed` | Return the value; throws `OutOfBoundsException` when absent. |
| `getOrNull` | `getOrNull(mixed $key): mixed` | Return the value or `null`. |
| `getOrElse` | `getOrElse(mixed $key, mixed $default): mixed` | Return the value or the given default. |
| `getOrElseGet` | `getOrElseGet(mixed $key, callable $default): mixed` | Return the value or evaluate the default lazily. |
| `put` | `put(mixed $key, mixed $value): self` | Set an entry; overwrites an existing key. |
| `remove` | `remove(mixed $key): self` | Remove an entry; no-op if absent. |
| `keys` | `keys(): array` | Return keys in insertion order. |
| `values` | `values(): array` | Return values in insertion order. |
| `entries` | `entries(): array` | Return `[key, value]` pairs. |
| `map` | `map(callable $fn): self` | Transform values; callback receives `(value, key)`. |
| `mapKeys` | `mapKeys(callable $fn): self` | Transform keys; callback receives `(key, value)`. |
| `filter` | `filter(callable $predicate): self` | Keep entries by value; callback receives `(value, key)`. |
| `filterKeys` | `filterKeys(callable $predicate): self` | Keep entries by key; callback receives `(key, value)`. |
| `fold` | `fold(mixed $initial, callable $fn): mixed` | Reduce entries; callback receives `(accumulator, value, key)`. |
| `merge` | `merge(self $other): self` | Merge; entries from `$other` overwrite matching keys. |
| `tap` | `tap(callable $fn): self` | Side effect per entry; returns the same instance. Callback receives `(value, key)`. |
| `equals` | `equals(self $other): bool` | Compare entries by key and value; order-independent. |
| `toArray` | `toArray(): array` | Return the keyed array. |
| `getIterator` | `getIterator(): Traversable` | Iterate entries in insertion order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize the keyed array. |

## Next Steps

- Use [Vec](vec.md) for ordered values without keys.
- Use [Set](set.md) when uniqueness matters more than keys.
- See [Map variants](map-variants.md) for `NonEmptyMap` and `SortedMap`.