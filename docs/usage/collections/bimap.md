# BiMap

`BiMap` is an immutable one-to-one map: each key has one value, and each value belongs to one key. Both keys and values must be PHP array keys (`int|string`), so the collection supports lookup in either direction.

## Quick Example

```php
use Exact\Collection\BiMap;

$countryCodes = BiMap::of('US', '840')
    ->put('CA', '124');

$numericCode = $countryCodes->get('US');
$country = $countryCodes->getKey('124');
$reverseLookup = $countryCodes->inverse()->get('840');
```

`put()` returns a new map. Reusing an existing key or value for a different mapping throws `InvalidArgumentException`; repeating the same mapping leaves the value unchanged. `get()` and `getKey()` throw `OutOfBoundsException` when there is no matching entry. Use `getOrNull()` or `getKeyOrNull()` for nullable lookup.

## When to Use BiMap

Use `BiMap` when reverse lookup must be unambiguous. For ordinary key-to-many-values data, use [MultiMap](multimap.md); for one-way lookup, use [Map](map.md).

The uniqueness invariant is enforced at insertion time:

- A key cannot be remapped to a different value.
- A value cannot be remapped to a different key.
- Re-inserting the exact same key/value pair is a no-op that returns the same instance.

This makes `getKey()` (reverse lookup) safe to rely on without additional checks.

## Creating a BiMap

```php
use Exact\Collection\BiMap;

$empty = BiMap::empty();
$oneEntry = BiMap::of('US', '840');

$twoEntries = $oneEntry->put('CA', '124');
```

- `BiMap::empty()` creates an empty map.
- `BiMap::of($key, $value)` creates a map with a single entry.
- `put($key, $value)` returns a new `BiMap` with the entry added.

The constructor is private; use the factories.

## Adding and Removing Entries

```php
$map = BiMap::empty()
    ->put('US', '840')
    ->put('CA', '124');

$withoutUs = $map->removeKey('US');
$withoutCa = $map->removeValue('124');
```

### `put()`

```php
$map = BiMap::of('US', '840');

$map->put('CA', '124');       // ok — new entry
$map->put('US', '840');       // ok — same mapping, returns $this
$map->put('US', '999');       // InvalidArgumentException
$map->put('MX', '840');       // InvalidArgumentException (840 already → US)
```

- `put($key, $value)` adds a new mapping and returns a new `BiMap`.
- Re-inserting the same key with the same value returns the same instance.
- Re-inserting a key with a different value throws `InvalidArgumentException`.
- Re-inserting a value with a different key throws `InvalidArgumentException`.

### `removeKey()` / `removeValue()`

```php
$map = BiMap::of('US', '840')->put('CA', '124');

$map->removeKey('US');       // BiMap with only CA → 124
$map->removeKey('MX');       // no-op, returns $this
$map->removeValue('124');    // removes the entry whose value is 124
$map->removeValue('999');    // no-op, returns $this
```

`removeKey($key)` and `removeValue($value)` return a new `BiMap` without the matched entry, or the same instance when nothing matched. Since the mapping is one-to-one, `removeValue($value)` is equivalent to `removeKey($this->getKey($value))`.

## Looking Up Values and Keys

```php
$map = BiMap::of('US', '840')->put('CA', '124');

$value = $map->get('US');          // '840'
$key   = $map->getKey('840');      // 'US'

$maybeValue = $map->getOrNull('MX');      // null
$maybeKey   = $map->getKeyOrNull('999');  // null
```

- `get($key)` returns the value for a key and throws `OutOfBoundsException` if the key is absent.
- `getKey($value)` returns the key for a value and throws `OutOfBoundsException` if the value is absent.
- `getOrNull($key)` / `getKeyOrNull($value)` return `null` instead of throwing.

## Checking Membership

```php
$map = BiMap::of('US', '840');

$hasKey   = $map->has('US');       // true
$hasValue = $map->hasValue('840'); // true

$map->has('MX');        // false
$map->hasValue('999');  // false
```

- `has($key)` checks key membership.
- `hasValue($value)` checks value membership.

`has()` and `hasValue()` are the non-throwing counterparts to `get()` and `getKey()` when only existence matters.

## Reading the Contents

```php
$map = BiMap::of('US', '840')->put('CA', '124');

$keys   = $map->keys();    // ['US', 'CA']
$values = $map->values();  // ['840', '124']
$count  = $map->count();   // 2
$size   = $map->size();    // 2, alias for count()
$empty  = $map->isEmpty(); // false
```

- `keys()` returns the keys in insertion order.
- `values()` returns the values in the same order as their keys.
- `count()` and `size()` return the number of entries.
- `isEmpty()` reports whether the map has no entries.

`keys()` and `values()` are parallel arrays: `$map->values()[$i]` is the value for `$map->keys()[$i]`.

## Inverting the Map

```php
$map = BiMap::of('US', '840')->put('CA', '124');

$inverse = $map->inverse();

$inverse->get('840');     // 'US'
$inverse->getKey('US');   // '840'
```

`inverse()` returns a new `BiMap` with keys and values swapped. Because the mapping is one-to-one, no data is lost, and `inverse()` is its own inverse: `$map->inverse()->inverse()->equals($map)` is `true`.

## Comparing Maps

```php
$a = BiMap::of('US', '840');
$b = BiMap::of('US', '840');
$c = BiMap::of('US', '999');

$a->equals($b); // true
$a->equals($c); // false
```

`equals(self $other)` compares the forward mapping with strict equality. It does not compare the insertion order of the underlying arrays—two maps with the same key/value pairs compare equal regardless of the order in which entries were added.

## Interop

```php
$map = BiMap::of('US', '840')->put('CA', '124');

$array = $map->toArray();
// ['US' => '840', 'CA' => '124']

foreach ($map as $key => $value) {
    // iterate entries in insertion order
}

$json = json_encode($map);
// {"US":"840","CA":"124"}
```

- `toArray()` returns the forward mapping as a PHP array.
- `getIterator()` yields entries in insertion order, so `BiMap` works with `foreach`.
- `jsonSerialize()` produces the forward mapping, so `json_encode()` emits a JSON object.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(): self` | Create an empty map. |
| `of` | `static of(mixed $key, mixed $value): self` | Create a map with one entry. |
| `put` | `put(mixed $key, mixed $value): self` | Add a mapping; throws `InvalidArgumentException` if key or value is already bound differently. |
| `removeKey` | `removeKey(mixed $key): self` | Remove the entry for a key; no-op if absent. |
| `removeValue` | `removeValue(mixed $value): self` | Remove the entry for a value; no-op if absent. |
| `get` | `get(mixed $key): mixed` | Return the value for a key; throws `OutOfBoundsException` if absent. |
| `getKey` | `getKey(mixed $value): mixed` | Return the key for a value; throws `OutOfBoundsException` if absent. |
| `getOrNull` | `getOrNull(mixed $key): mixed` | Return the value for a key or `null`. |
| `getKeyOrNull` | `getKeyOrNull(mixed $value): mixed` | Return the key for a value or `null`. |
| `has` | `has(mixed $key): bool` | Check whether a key is present. |
| `hasValue` | `hasValue(mixed $value): bool` | Check whether a value is present. |
| `keys` | `keys(): array` | Return the keys in insertion order. |
| `values` | `values(): array` | Return the values in insertion order. |
| `count` | `count(): int` | Return the number of entries. |
| `size` | `size(): int` | Alias for `count()`. |
| `isEmpty` | `isEmpty(): bool` | Report whether the map has no entries. |
| `inverse` | `inverse(): self` | Swap keys and values. |
| `equals` | `equals(self $other): bool` | Compare the forward mappings. |
| `toArray` | `toArray(): array` | Return the forward mapping as an array. |
| `getIterator` | `getIterator(): Traversable` | Iterate entries in insertion order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize the forward mapping. |

## Next Steps

- See [Map variants](map-variants.md) for `NonEmptyMap` and `SortedMap`.
- See [Collections Overview](overview.md) to choose a collection.
