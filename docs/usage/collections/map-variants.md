# Map Variants

Exact provides several maps for requirements beyond ordinary key lookup.

## NonEmptyMap

Use `NonEmptyMap` when a map must retain at least one entry. `put()` and `map()` preserve that invariant; `filter()` and `remove()` return `Map`, because they may remove every entry.

```php
use Exact\Collection\NonEmptyMap;

$settings = NonEmptyMap::singleton('locale', 'en')
    ->put('currency', 'USD');

$settings->head(); // ['locale', 'en']
```

See the dedicated [NonEmptyMap guide](non-empty-map.md) for construction and conversion details.

## SortedMap

Use `SortedMap` when iteration order is part of the contract. Provide a key comparator when constructing it:

```php
use Exact\Collection\SortedMap;

$map = SortedMap::fromArray(
    ['b' => 2, 'a' => 1],
    static fn (int|string $left, int|string $right): int => $left <=> $right,
);
```

Keys remain comparator-ordered after updates. See [SortedMap](sorted-map.md) for inclusive key ranges and keyed transformations.

## BiMap

Use `BiMap` when both lookup directions must be unique:

```php
use Exact\Collection\BiMap;

$codes = BiMap::of('US', '840')->put('CA', '124');
$country = $codes->getKey('840');
```

Both keys and values must be integers or strings, and duplicate mappings to different counterparts throw `InvalidArgumentException`. See [BiMap](bimap.md) for reverse lookup and inversion.

## Choosing Between Them

Use [Map](map.md) for ordinary keyed values, `NonEmptyMap` for a non-empty guarantee, `SortedMap` for ordered keys, and `BiMap` for one-to-one reverse lookup.
