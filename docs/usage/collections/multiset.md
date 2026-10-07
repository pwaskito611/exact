# MultiSet

`MultiSet` (also called a bag) tracks how many times each value occurs. Unlike [Set](set.md), adding an existing value increases its multiplicity rather than discarding the duplicate.

## Quick Example

```php
use Exact\Collection\MultiSet;

$inventory = MultiSet::of('pen', 'pen', 'book')
    ->add('pen', 2);

$penCount = $inventory->countOf('pen'); // 4
$distinct = $inventory->distinct()->toArray(); // ['pen', 'book']
$remaining = $inventory->remove('pen', 3);
```

`count()` reports the total number of occurrences; `distinctCount()` reports the number of distinct values. `toArray()` expands all occurrences, while `distinctValues()` returns one copy of each value. `union()` adds multiplicities, `intersect()` keeps the smaller count for each shared value, and `difference()` subtracts counts without going below zero.

`add()` rejects negative multiplicities with `InvalidArgumentException`; removing more occurrences than are present removes that value entirely.

## When to Use MultiSet

Use `MultiSet` when:

- You need to count occurrences of values, not just track presence.
- You want set-like algebra (union, intersect, difference) that respects multiplicities.
- You want to expand back into a list of all occurrences when needed.

Use [Set](set.md) when duplicates should be removed, and [Vec](vec.md) when order matters and duplicates are simply part of the sequence.

## Creating a MultiSet

```php
use Exact\Collection\MultiSet;

$empty      = MultiSet::empty();
$fromValues = MultiSet::of('pen', 'pen', 'book');
$fromIter   = MultiSet::fromIterable(new ArrayIterator(['pen', 'book', 'pen']));
```

- `MultiSet::empty()` creates an empty multiset.
- `MultiSet::of(...$items)` creates a multiset by adding each value once.
- `MultiSet::fromIterable(iterable $items)` builds a multiset from any iterable.

Both `of()` and `fromIterable()` call `add()` for each item, so multiplicity is accumulated as expected.

## Equality of Values

Values are compared by a string key derived from the value:

- For scalars and `null`: `gettype($value) . ':' . (string) $value`.
- For objects and arrays: `'object:' . serialize($value)`.

This means the multiset is **type-strict**:

```php
$m = MultiSet::of(1, '1', 1.0, true);

$m->countOf(1);    // 1 (integer)
$m->countOf('1');  // 1 (string)
$m->countOf(1.0);  // 1 (double)
$m->countOf(true); // 1 (boolean)

$m->distinctCount(); // 4
```

Two objects are considered equal only if their serialized forms match. Objects that contain resources or closures may serialize differently across runs; treat object identity in a multiset carefully.

## Adding and Removing

```php
$inventory = MultiSet::of('pen', 'book');

$morePens   = $inventory->add('pen', 2);       // pen → 3
$same       = $inventory->add('pen', 0);       // no-op, same instance
$withNotes  = $inventory->add('notebook');     // default times = 1

$lessPens   = $inventory->add('pen', 5)->remove('pen', 3); // pen → 3 again
$removed    = $inventory->remove('book', 10);  // book removed entirely
$unchanged  = $inventory->remove('eraser');    // not present, same instance
```

### `add($value, $times = 1)`

- `$times > 0` adds that many occurrences.
- `$times === 0` returns the same instance.
- `$times < 0` throws `InvalidArgumentException`.
- Adding a new value creates an entry; adding an existing value increases its count.

### `remove($value, $times = 1)`

- If the value is absent, returns the same instance.
- Otherwise subtracts `$times` from the count.
- If the resulting count is `<= 0`, the entry is removed entirely.

`remove()` does not validate the sign of `$times`. Passing a negative value will **increase** the count — the opposite of removal. Pass only non-negative values.

## Counting Occurrences

```php
$inventory = MultiSet::of('pen', 'pen', 'book');

$total     = $inventory->count();         // 3 (includes duplicates)
$distinct  = $inventory->distinctCount(); // 2
$penCount  = $inventory->countOf('pen');  // 2
$same      = $inventory->multiplicity('pen'); // 2, alias

$hasPen    = $inventory->contains('pen'); // true
$isEmpty   = MultiSet::empty()->isEmpty(); // true
```

- `count()` returns the total number of occurrences, including duplicates.
- `distinctCount()` returns the number of distinct values.
- `countOf($value)` returns the multiplicity of a value (`0` if absent).
- `multiplicity($value)` is an alias for `countOf()`.
- `contains($value)` reports whether a value is present at least once.
- `isEmpty()` reports whether the multiset has no entries.

`MultiSet` implements `Countable`, so PHP's `count($multiSet)` returns the same value as `$multiSet->count()`.

## Set Algebra

```php
$a = MultiSet::of('pen', 'pen', 'book');
$b = MultiSet::of('pen', 'notebook');

$union      = $a->union($b);      // pen → 3, book → 1, notebook → 1
$alsoUnion  = $a->addAll($b);     // alias for union()
$intersect  = $a->intersect($b);  // pen → 1
$difference = $a->difference($b); // pen → 1, book → 1
```

### `union(self $other)` / `addAll(self $other)`

Adds the multiplicities of `$other` to the current multiset. `addAll()` is an alias for `union()`.

### `intersect(self $other)`

Keeps only values present in both, with multiplicity equal to the **minimum** of the two counts. Values unique to either side are dropped.

### `difference(self $other)`

Subtracts the multiplicity of each value in `$other` from the current multiset. If a value's resulting count would drop to `0` or below, the entry is removed. Values in `$other` that are not present in the current multiset are ignored.

## Transforming Values

```php
$words = MultiSet::of('ada', 'ada', 'grace');

$upperCased = $words->map(static fn (string $name): string => strtoupper($name));
// ADA → 2, GRACE → 1

$longNames = $words->filter(static fn (string $name): bool => strlen($name) > 3);
// grace → 1
```

- `map(callable $fn)` transforms each distinct value, preserving its count.
- `filter(callable $predicate)` keeps values where the predicate is `true`, preserving their counts.

### A subtlety of `map()`

`map()` applies the callback to each distinct value once and re-adds the result with the original count. If the transformation collapses two distinct values into the same result, their counts are combined:

```php
MultiSet::of('a', 'A')
    ->map(static fn (string $s): string => strtolower($s))
    ->countOf('a'); // 2
```

Both `'a'` and `'A'` map to `'a'`, so their multiplicities add up in the result.

## Getting the Distinct Set

```php
use Exact\Collection\Set;

$inventory = MultiSet::of('pen', 'pen', 'book');

$set = $inventory->distinct();       // Set ['pen', 'book']
$array = $inventory->distinctValues(); // ['pen', 'book']
```

- `distinct()` returns a `Set` containing one copy of each distinct value.
- `distinctValues()` returns the same values as a plain array, preserving the order in which distinct values were first added.

## Comparing MultiSets

```php
$a = MultiSet::of('pen', 'pen', 'book');
$b = MultiSet::of('book', 'pen', 'pen');
$c = MultiSet::of('pen', 'book');

$a->equals($b); // true  — same values, same counts
$a->equals($c); // false — different counts
```

`equals(self $other)` compares distinct entries and their counts using strict equality. The order in which values were added does not matter.

## Interop

```php
$inventory = MultiSet::of('pen', 'pen', 'book');

$array = $inventory->toArray();
// ['pen', 'pen', 'book']

foreach ($inventory as $value) {
    // iterates each occurrence: pen, pen, book
}

$json = json_encode($inventory);
// {"string:pen":{"value":"pen","count":2},"string:book":{"value":"book","count":1}}
```

- `toArray()` expands all occurrences into a list.
- `getIterator()` yields each occurrence, so iteration count matches `count()`.
- `jsonSerialize()` returns the internal entry map — a JSON object keyed by the internal value key, with `{value, count}` per entry.

The JSON shape exposes internal keys (e.g. `"string:pen"`). Treat it as a serialization detail rather than a stable public contract.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(): self` | Create an empty multiset. |
| `of` | `static of(mixed ...$items): self` | Create from the given values, each added once. |
| `fromIterable` | `static fromIterable(iterable $items): self` | Create from an iterable, adding each item once. |
| `add` | `add(mixed $value, int $times = 1): self` | Add `$times` occurrences; negative throws `InvalidArgumentException`. |
| `remove` | `remove(mixed $value, int $times = 1): self` | Remove `$times` occurrences; removes the entry when the count drops to `0` or below. |
| `countOf` | `countOf(mixed $value): int` | Multiplicity of a value. |
| `multiplicity` | `multiplicity(mixed $value): int` | Alias for `countOf()`. |
| `contains` | `contains(mixed $value): bool` | Check whether a value is present. |
| `count` | `count(): int` | Total number of occurrences. |
| `distinctCount` | `distinctCount(): int` | Number of distinct values. |
| `isEmpty` | `isEmpty(): bool` | Report whether the multiset has no entries. |
| `union` | `union(self $other): self` | Add the multiplicities of `$other`. |
| `addAll` | `addAll(self $other): self` | Alias for `union()`. |
| `intersect` | `intersect(self $other): self` | Keep shared values with the minimum multiplicity. |
| `difference` | `difference(self $other): self` | Subtract the multiplicities of `$other`. |
| `map` | `map(callable $fn): self` | Transform distinct values, preserving counts; colliding results combine. |
| `filter` | `filter(callable $predicate): self` | Keep values that satisfy a predicate, preserving counts. |
| `distinct` | `distinct(): Set` | Return a `Set` of distinct values. |
| `equals` | `equals(self $other): bool` | Compare distinct values and their counts; order-independent. |
| `toArray` | `toArray(): array` | Expand all occurrences into a list. |
| `distinctValues` | `distinctValues(): array` | One copy of each distinct value, in insertion order. |
| `getIterator` | `getIterator(): Traversable` | Iterate each occurrence. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize the internal `{value, count}` entry map. |

## Next Steps

- Use [Set](set.md) when duplicates should be removed.
- Use [Vec](vec.md) when order matters and duplicates belong to the sequence itself.
- See [Collections Overview](overview.md).