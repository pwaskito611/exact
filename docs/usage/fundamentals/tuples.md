# Tuples

`Tuple` and `Pair` group a fixed set of values without introducing a named domain class. Use `Tuple` for position-based values with one to three positions, and `Pair` when exactly two positions should be named `first` and `second`.

## Tuple

Use the arity-specific factories for clearer type inference:

```php
use Exact\Type\Tuple\Tuple;

$coordinates = Tuple::of2(10, 20);
$person = Tuple::of3('Ada', 37, true);
$coordinates = $coordinates->mapFirst(
    static fn (int $coordinate): int => $coordinate * 2,
);
$scaledY = $coordinates->mapAt(
    1,
    static fn (int $coordinate): int => $coordinate * 2,
);

$x = $coordinates->first();
$y = $coordinates->second();
$coordinateCount = $coordinates->count();
$values = $coordinates->toArray();
```

`of1()`, `of2()`, and `of3()` create one-, two-, and three-element tuples. `of()` is a variadic escape hatch and accepts zero or more values; `pair()` and `triple()` are aliases for `of2()` and `of3()`. Tuple positions can contain different types. The tuple is immutable: mapping methods return a new value and leave the original unchanged.

`first()`, `second()`, `third()`, and `last()` read positions and throw `OutOfBoundsException` when the position does not exist. `at($index)` uses a zero-based index and also throws when out of range; `atOrNull($index)` returns `null` for a missing position. `count()` reports the arity and `isEmpty()` checks whether a variadic tuple has no values.

### Transforming and Unpacking

`mapFirst()`, `mapSecond()`, and `mapThird()` transform a specific position. `mapAt()` transforms the element at a runtime index. `bimap()` transforms the first two positions with separate callbacks, while `swap()` reverses the first two; both require at least two elements and throw `LogicException` otherwise.

Use `fold()` to unpack all tuple values into a callback:

```php
$description = Tuple::of3('Ada', 37, true)->fold(
    static fn (string $name, int $age, bool $active): string =>
        "{$name} ({$age}): " . ($active ? 'active' : 'inactive'),
);

$swapped = Tuple::of2('key', 'value')->swap();
$transformed = Tuple::of2(2, 3)->bimap(
    static fn (int $value): int => $value * 10,
    static fn (int $value): int => $value * 100,
);
```

Tuples implement `Countable`, `IteratorAggregate`, and `JsonSerializable`. `toArray()` returns a list, iteration yields values in order, and JSON serialization uses that same list shape. `equals()` compares the complete ordered value list using strict equality.

## Pair

Use `Pair` when the two positions have distinct roles:

```php
use Exact\Type\Tuple\Pair;

$entry = Pair::of('key', 'value');
$key = $entry->first();
$value = $entry->second();
$upperKey = $entry->mapFirst(
    static fn (string $key): string => strtoupper($key),
);
$reversed = $entry->swap();
$parts = $reversed->toArray();
```

`Pair::of()` always creates exactly two positions and direct construction is not available. `mapFirst()` and `mapSecond()` transform one value, `bimap()` transforms both, and `fold()` passes both values to one callback. `swap()` creates a new pair with the positions reversed. `toArray()` returns a two-item numeric array, while JSON serialization uses the named shape `{"first": ..., "second": ...}`. `equals()` compares both values using strict equality.

## Method Reference

| Type | Method | Description |
|---|---|---|
| `Tuple` | `of1()`, `of2()`, `of3()`, `of()`, `pair()`, `triple()` | Construct a tuple of fixed arity. |
| `Tuple` | `first()`, `second()`, `third()`, `last()`, `at()`, `atOrNull()` | Read tuple positions. |
| `Tuple` | `count()`, `isEmpty()` | Inspect its arity. |
| `Tuple` | `mapFirst()`, `mapSecond()`, `mapThird()`, `mapAt()` | Transform one position. |
| `Tuple` | `bimap()`, `swap()` | Transform or swap the first two positions. |
| `Tuple` | `fold()`, `equals()`, `toArray()`, `getIterator()`, `jsonSerialize()` | Unpack, compare, or convert values. |
| `Pair` | `of()`, `first()`, `second()` | Construct and read a pair. |
| `Pair` | `mapFirst()`, `mapSecond()`, `bimap()`, `swap()` | Transform or swap pair positions. |
| `Pair` | `fold()`, `equals()`, `toArray()`, `jsonSerialize()` | Unpack, compare, or convert pair values. |

## Next Steps

- Use tuples inside [Collections](../collections/overview.md) when a small grouped value is sufficient.
- Use an application-owned domain class when values need named fields or domain-specific equality rules.
