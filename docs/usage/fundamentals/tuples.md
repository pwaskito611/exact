# Tuples

`Tuple` and `Pair` group a fixed set of values without introducing a named domain class.

## Tuple

```php
use Exact\Data\Tuple\Tuple;

$coordinates = Tuple::of(10, 20);
$scaled = $coordinates->map(
    static fn (int $coordinate): int => $coordinate * 2,
);

$x = $scaled->at(0);
$coordinateCount = $coordinates->count();
$values = $scaled->toArray();
```

`Tuple::of()` accepts any number of values, including zero. `at()` uses zero-based indexes and throws `OutOfBoundsException` for a missing index; `count()` returns the number of values. `map()` calls its callback once per value and returns a new tuple, while `toArray()` exposes the values as a PHP array.

## Pair

Use `Pair` when exactly two named positions, `first` and `second`, are enough:

```php
use Exact\Data\Tuple\Pair;

$entry = Pair::of('key', 'value');
$key = $entry->first();
$value = $entry->second();
$reversed = $entry->swap();
$parts = $reversed->toArray();
```

`Pair::of()` always creates exactly two positions. `first()` and `second()` read them, `swap()` returns a new pair with their order reversed, and `toArray()` returns a two-item array. `Pair` cannot be constructed directly; use its factory. Neither tuples nor pairs expose mutable update operations, so these operations leave the original value unchanged.

## Next Steps

- Use tuples inside [Collections](../collections/overview.md) when a small grouped value is sufficient.
- Use a [Value Object](../modeling/value-objects.md) when the values need domain-specific meaning or equality rules.