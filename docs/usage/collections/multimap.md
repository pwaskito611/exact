# MultiMap

`MultiMap` groups multiple values under the same integer or string key. Insertion preserves the order of values for each key, and the collection is immutable.

## Quick Example

```php
use Exact\Collection\MultiMap;

$roles = MultiMap::empty()
    ->put('ada', 'admin')
    ->put('ada', 'reviewer')
    ->put('lin', 'editor');

$adaRoles = $roles->get('ada')->toArray();
// ['admin', 'reviewer']

$updated = $roles->removeValue('ada', 'admin');
```

`get()` returns a `Vec`, including an empty `Vec` for an absent key. `removeValue()` removes every strictly identical occurrence of that value under the key; `removeKey()` removes the entire group. `count()` counts keys, while `totalSize()` counts all values.

`map()` and `filter()` callbacks receive the value and its key. `fold()` receives the accumulator, value, and key. Use [Map](map.md) when every key should hold only one value.

## When to Use MultiMap

Use `MultiMap` when:

- A key may have zero, one, or many values.
- You want values under each key to keep their insertion order.
- You want an immutable collection — every `put()` / `removeValue()` / `removeKey()` returns a new instance.

Use [Map](map.md) when every key has exactly one value, and [BiMap](bimap.md) when you need one-to-one forward and reverse lookup.

## Creating a MultiMap

```php
use Exact\Collection\MultiMap;

$empty      = MultiMap::empty();
$single     = MultiMap::singleton('ada', 'admin');
```

- `MultiMap::empty()` creates a map with no keys.
- `MultiMap::singleton($key, $value)` creates a map with one key holding one value.

There is no `of()` or `fromArray()` factory; build up the map with `put()`.

## Adding Values

```php
$roles = MultiMap::empty()
    ->put('ada', 'admin')
    ->put('ada', 'reviewer')
    ->put('lin', 'editor');

$roles->get('ada')->toArray(); // ['admin', 'reviewer']
$roles->get('lin')->toArray(); // ['editor']
```

`put($key, $value)` appends `$value` to the list under `$key`. If `$key` is new, the list is created. If it already exists, the value is appended at the end.

`put()` never throws. Duplicates are allowed and preserved:

```php
MultiMap::empty()
    ->put('ada', 'admin')
    ->put('ada', 'admin')
    ->get('ada')
    ->toArray(); // ['admin', 'admin']
```

## Removing Values and Keys

```php
$roles = MultiMap::singleton('ada', 'admin')
    ->put('ada', 'reviewer')
    ->put('lin', 'editor');

$withoutAdmin    = $roles->removeValue('ada', 'admin'); // 'ada' → ['reviewer']
$withoutAda      = $roles->removeKey('ada');            // only 'lin' → ['editor']
```

### `removeValue($key, $value)`

Removes every occurrence of `$value` under `$key` using **strict comparison** (`!==`). If the key's list becomes empty, the key itself is removed. If the key is absent or `$value` is not present, the same instance is returned:

```php
$roles = MultiMap::singleton('ada', 'admin')->put('ada', 'admin');

$roles->removeValue('ada', 'admin')->has('ada'); // false — both occurrences removed
$roles->removeValue('lin', 'admin');             // no-op, same instance
```

Because `array_filter` preserves keys, the remaining values are re-indexed with `array_values()`. The resulting `Vec` therefore always has contiguous indexes starting at 0.

### `removeKey($key)`

Removes the entire group for `$key`, including all its values. If the key is absent, the same instance is returned.

## Looking Up a Key

```php
$roles = MultiMap::singleton('ada', 'admin')->put('ada', 'reviewer');

$adaRoles = $roles->get('ada');     // Vec ['admin', 'reviewer']
$missing  = $roles->get('lin');     // Vec [], empty — does not throw

$hasAda   = $roles->has('ada');     // true
$hasLin   = $roles->has('lin');     // false
```

- `get($key)` always returns a `Vec`. For an absent key, it returns an empty `Vec` — never `null`, never throws.
- `has($key)` returns `true` when the key is present.

Because `get()` returns an empty `Vec` for a missing key, `$map->get('lin')->isEmpty()` is the idiomatic check for absence when you also want the values.

## Reading Keys and Sizes

```php
$roles = MultiMap::singleton('ada', 'admin')
    ->put('ada', 'reviewer')
    ->put('lin', 'editor');

$keys       = $roles->keys();        // ['ada', 'lin']
$keyCount   = $roles->count();       // 2
$keyCount   = $roles->size();        // 2, alias for count()
$valueCount = $roles->totalSize();   // 3
$isEmpty    = MultiMap::empty()->isEmpty(); // true
```

- `keys()` returns the keys in insertion order.
- `count()` / `size()` return the number of keys.
- `totalSize()` returns the total number of values across all keys.
- `isEmpty()` reports whether the map has no keys.

`count($multiMap)` (PHP's `count()` function) works too, because `MultiMap` implements `Countable`, and returns the same value as `count()`.

## Transforming Values

```php
$roles = MultiMap::singleton('ada', 'admin')->put('ada', 'reviewer');

$upperCased = $roles->map(
    static fn (string $role, string $user): string => strtoupper($role),
);
// 'ada' → ['ADMIN', 'REVIEWER']

$onlyAdmin = $roles->filter(
    static fn (string $role, string $user): bool => $role === 'admin',
);
// 'ada' → ['admin']

$lengths = $roles->fold(
    [],
    static fn (array $acc, string $role, string $user): array => [
        ...$acc,
        "{$user}:{$role}",
    ],
);
```

- `map(callable $fn)` transforms every value; the callback receives `(value, key)`.
- `filter(callable $predicate)` keeps values where the predicate is `true`; the callback receives `(value, key)`.
- `fold($initial, callable $fn)` reduces over all values; the callback receives `(accumulator, value, key)`.

All three iterate values in insertion order, key by key. `map()` and `filter()` return a new `MultiMap`; `fold()` returns the accumulated value.

Note that `filter()` does **not** remove a key when all its values are filtered out — the key simply disappears because no values were appended to the result under that key. This matches `removeValue()`'s behavior of removing empty groups.

## Interop

```php
$roles = MultiMap::singleton('ada', 'admin')->put('ada', 'reviewer');

$array = $roles->toArray();
// ['ada' => ['admin', 'reviewer']]

foreach ($roles as $key => $values) {
    // $key is a string, $values is a Vec
    // e.g. 'ada' => Vec ['admin', 'reviewer']
}

$json = json_encode($roles);
// {"ada":["admin","reviewer"]}
```

- `toArray()` returns raw arrays keyed by key: `array<TKey, list<TValue>>`.
- `getIterator()` yields `key => Vec` pairs, converting each group to a `Vec` on the fly.
- `jsonSerialize()` produces the same shape as `toArray()` — a JSON object whose values are arrays.

The difference between `toArray()` and `getIterator()` matters when you iterate: `toArray()` gives you plain arrays, while `getIterator()` gives you `Vec` instances so you can keep chaining collection methods.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(): self` | Create an empty multimap. |
| `singleton` | `static singleton(mixed $key, mixed $value): self` | Create a map with one key holding one value. |
| `put` | `put(mixed $key, mixed $value): self` | Append `$value` under `$key`; creates the group if needed. |
| `removeValue` | `removeValue(mixed $key, mixed $value): self` | Remove every strictly identical occurrence of `$value`; removes the key when its group becomes empty. |
| `removeKey` | `removeKey(mixed $key): self` | Remove the entire group for `$key`; no-op if absent. |
| `get` | `get(mixed $key): Vec` | Return the values as a `Vec`; returns an empty `Vec` for an absent key. |
| `has` | `has(mixed $key): bool` | Check whether a key is present. |
| `keys` | `keys(): array` | Return keys in insertion order. |
| `count` | `count(): int` | Number of keys. |
| `size` | `size(): int` | Alias for `count()`. |
| `totalSize` | `totalSize(): int` | Total number of values across all keys. |
| `isEmpty` | `isEmpty(): bool` | Report whether the map has no keys. |
| `map` | `map(callable $fn): self` | Transform every value; callback receives `(value, key)`. |
| `filter` | `filter(callable $predicate): self` | Keep values where the predicate is `true`; callback receives `(value, key)`. |
| `fold` | `fold(mixed $initial, callable $fn): mixed` | Reduce over all values; callback receives `(accumulator, value, key)`. |
| `toArray` | `toArray(): array` | Return `array<TKey, list<TValue>>`. |
| `getIterator` | `getIterator(): Traversable` | Yield `key => Vec` pairs in insertion order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize to `array<TKey, list<TValue>>`. |

## Next Steps

- Use [BiMap](bimap.md) for one-to-one forward and reverse lookup.
- Use [Map](map.md) when every key holds exactly one value.
- See [Collections Overview](overview.md).