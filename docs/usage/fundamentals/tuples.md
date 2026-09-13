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
$values = $scaled->toArray();
```

`Tuple::at()` uses zero-based indexes and throws `OutOfBoundsException` for a missing index. `count()` returns the number of values.

## Pair

Use `Pair` when exactly two named positions, `first` and `second`, are enough:

```php
use Exact\Data\Tuple\Pair;

$entry = Pair::of('key', 'value');
$reversed = $entry->swap();
```

Both types return new values from transformations; the original tuple or pair remains unchanged.

## Next Steps

- Use tuples inside [Collections](../collections/overview.md) when a small grouped value is sufficient.
- Use a [Value Object](../modeling/value-objects.md) when the values need domain-specific meaning or equality rules.