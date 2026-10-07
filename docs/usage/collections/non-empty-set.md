# NonEmptySet

`NonEmptySet` is an insertion-ordered set that guarantees at least one distinct value. Membership and deduplication use strict comparison.

## Quick Example

```php
use Exact\Collection\NonEmptySet;

$required = NonEmptySet::of('read', 'write')
    ->add('admin');

$hasWrite = $required->contains('write');
$permissions = $required->toSet();
```

`map()`, `add()`, and `union()` preserve non-emptiness. `filter()`, `remove()`, and `intersect()` can produce no values, so they return an ordinary `Set`. `fromSet()` throws `InvalidArgumentException` if the input set is empty.

## When to Use NonEmptySet

Use `NonEmptySet` when the domain requires at least one distinct value:

- A permission set that must contain a default.
- A tag list where "no tags" is invalid.
- A pipeline stage that must produce at least one result.

Use [Set](set.md) when an empty result is valid, and [SortedSet](sorted-set.md) for comparator-ordered unique values.

## The Non-Empty Invariant

The invariant is enforced at construction: the private constructor throws `InvalidArgumentException` if the given array is empty. Operations preserve or drop the invariant explicitly:

- **Preserving**: `add()`, `map()`, `union()`, `tap()` return `NonEmptySet`.
- **Dropping**: `remove()`, `filter()`, `intersect()` return `Set` because the result may be empty.

This makes the non-empty guarantee a compile-time contract that the type system can track.

## Equality and Deduplication

`NonEmptySet` uses **strict comparison** (`===`) throughout, both for `contains()` and for deduplication in the factories:

- Scalars of different types are distinct: `1`, `'1'`, `1.0`, and `true` are four different elements.
- Objects are equal only if they are the same instance.
- Arrays are compared strictly and recursively by value and key order.

```php
$set = NonEmptySet::of(1, '1', 1.0, true);

$set->count();          // 4
$set->contains(1);      // true
$set->contains('1');    // true
$set->contains(1.0);    // true
```

Unlike [MultiSet](multiset.md), which uses `serialize()` for non-scalars, `NonEmptySet` compares objects by identity. Two separate instances of the same class with the same properties are **not** equal.

## Creating a NonEmptySet

```php
use Exact\Collection\NonEmptySet;
use Exact\Collection\Set;

$fromValues = NonEmptySet::of('read', 'write', 'read'); // 'read' ignored
$fromArray  = NonEmptySet::fromArray(['read', 'write', 'read']);
$fromSet    = NonEmptySet::fromSet(Set::of('read', 'write'));
```

- `NonEmptySet::of(mixed $head, mixed ...$tail)` requires at least one argument. The head is always kept; subsequent values are deduplicated.
- `NonEmptySet::fromArray(array $items)` deduplicates input values. An empty array throws `InvalidArgumentException`.
- `NonEmptySet::fromSet(Set $set)` converts a `Set`; throws `InvalidArgumentException` when the set is empty.

There is no `empty()` factory — the type exists precisely to prevent that state.

## Reading the First Element

```php
$set = NonEmptySet::of('read', 'write', 'admin');

$first = $set->head(); // 'read'
```

`head()` returns the first element in insertion order. Because the set is guaranteed non-empty, `head()` never throws.

The head is the value passed as `$head` to `of()`, or the first distinct element added via `fromArray()`. It remains the head unless removed — but `remove()` returns a `Set`, not a `NonEmptySet`, so the head can only change by constructing a new `NonEmptySet`.

## Checking Membership

```php
$set = NonEmptySet::of('read', 'write');

$hasRead   = $set->contains('read');   // true
$hasDelete = $set->contains('delete'); // false

$count = $set->count(); // 2
$size  = $set->size();  // 2, alias for count()
```

- `contains(mixed $value)` returns `true` when the value is in the set, using strict comparison.
- `count()` / `size()` return the number of distinct values.

`NonEmptySet` implements `Countable`, so `count($nonEmptySet)` returns the same value as `$nonEmptySet->count()`.

## Adding Values

```php
$set = NonEmptySet::of('read');

$withWrite = $set->add('write');  // NonEmptySet ['read', 'write']
$unchanged = $set->add('read');   // same instance
```

`add(mixed $value)` returns a new `NonEmptySet` with `$value` appended if it is not already present. If the value is present, the same instance is returned. Because adding cannot make the set empty, the return type stays `NonEmptySet`.

## Removing Values

```php
$set = NonEmptySet::of('read', 'write', 'admin');

$withoutWrite = $set->remove('write'); // Set ['read', 'admin']
$asSet        = $set->remove('read');  // Set ['write', 'admin']
```

`remove(mixed $value)` returns a [Set](set.md), not a `NonEmptySet`, because removing the last element would violate the invariant. Removing an absent value still returns a `Set` containing the same elements.

To convert back when you know the result is non-empty:

```php
$restored = NonEmptySet::fromSet($set->remove('write'));
```

## Set Algebra

```php
$a = NonEmptySet::of('read', 'write');
$b = NonEmptySet::of('write', 'admin');

$union    = $a->union($b);    // NonEmptySet ['read', 'write', 'admin']
$intersect = $a->intersect($b); // Set ['write']
```

### `union(self $other)`

Returns a `NonEmptySet` containing all distinct values from both sets, in the order they first appear in `$this` then `$other`. Because both inputs are non-empty, the union is non-empty, so the return type stays `NonEmptySet`.

### `intersect(self $other)`

Returns a [Set](set.md) containing values present in both sets, preserving the order of `$this`. The intersection can be empty, so the return type is `Set`.

## Comparing Sets

```php
$a = NonEmptySet::of('read', 'write');
$b = NonEmptySet::of('write', 'read');
$c = NonEmptySet::of('read', 'write', 'admin');

$a->equals($b); // true  — same values, order irrelevant
$a->equals($c); // false — different sizes

$a->isSubsetOf($c); // true
$c->isSubsetOf($a); // false
```

- `equals(self $other)` returns `true` when both sets have the same size and each element of `$this` is in `$other`. **Insertion order does not matter** — unlike `NonEmptyMap::equals()`, `NonEmptySet` compares by membership, not by underlying array order.
- `isSubsetOf(self $other)` returns `true` when every element of `$this` is in `$other`.

`equals()` is symmetric; `isSubsetOf()` is not.

## Transforming Values

```php
$set = NonEmptySet::of('read', 'write');

$upperCased = $set->map(
    static fn (string $perm): string => strtoupper($perm),
);
// NonEmptySet ['READ', 'WRITE']

$longPerms = $set->filter(
    static fn (string $perm): bool => strlen($perm) > 4,
);
// Set ['write'] — or empty Set if nothing matches

$inspected = $set->tap(
    static fn (string $perm): void => error_log("perm={$perm}"),
);
```

- `map(callable $fn)` transforms every element and deduplicates the results with strict comparison. Returns `NonEmptySet` because mapping preserves the element count (though it can **reduce** it if two elements map to the same value).
- `filter(callable $predicate)` keeps elements where the predicate is `true`. Returns [Set](set.md) because the result may be empty.
- `tap(callable $fn)` runs the callback for side effects and returns the same instance.

### `map()` and deduplication

`map()` applies the callback to each element, then deduplicates the results using strict comparison:

```php
NonEmptySet::of('a', 'A')
    ->map(static fn (string $s): string => strtolower($s))
    ->count(); // 1 — both map to 'a'
```

If the transformation collapses multiple elements into the same result, the resulting set is smaller than the input. It is still non-empty because the input was non-empty.

## Reducing

```php
$set = NonEmptySet::of('read', 'write', 'admin');

$joined = $set->fold(
    '',
    static fn (string $acc, string $perm): string => $acc === '' ? $perm : "{$acc},{$perm}",
); // 'read,write,admin'
```

`fold($initial, callable $fn)` reduces elements in insertion order. The callback receives `(accumulator, value)`.

Unlike [Vec](vec.md), `NonEmptySet` has no `reduce()` — because the set already has a head, but the API favors `fold()` with an explicit initial value.

## Converting to Set

```php
use Exact\Collection\NonEmptySet;
use Exact\Collection\Set;

$set = NonEmptySet::of('read', 'write');

$plain = $set->toSet(); // Set ['read', 'write']
```

`toSet()` returns a `Set` view of the same elements. Use it at API boundaries where the non-empty guarantee is no longer needed.

## Interop

```php
$set = NonEmptySet::of('read', 'write', 'admin');

$array = $set->toArray();
// ['read', 'write', 'admin']

foreach ($set as $value) {
    // iterate in insertion order
}

$json = json_encode($set);
// ["read","write","admin"]
```

- `toArray()` returns the elements as a non-empty list in insertion order.
- `getIterator()` yields elements with integer indexes starting at 0.
- `jsonSerialize()` produces a JSON array.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `of` | `static of(mixed $head, mixed ...$tail): self` | Create a non-empty set; deduplicates `$tail` against `$head`. |
| `fromArray` | `static fromArray(array $items): self` | Create from a non-empty array; deduplicates; empty input throws `InvalidArgumentException`. |
| `fromSet` | `static fromSet(Set $set): self` | Convert a `Set`; empty input throws `InvalidArgumentException`. |
| `head` | `head(): mixed` | First element in insertion order. Never throws. |
| `contains` | `contains(mixed $value): bool` | Check membership using strict comparison. |
| `count` | `count(): int` | Number of distinct elements. |
| `size` | `size(): int` | Alias for `count()`. |
| `add` | `add(mixed $value): self` | Append if absent; returns the same instance when already present. |
| `remove` | `remove(mixed $value): Set` | Remove an element; returns `Set` because the result may be empty. |
| `union` | `union(self $other): self` | All distinct values from both sets. |
| `intersect` | `intersect(self $other): Set` | Values present in both; returns `Set`. |
| `equals` | `equals(self $other): bool` | Compare by membership; insertion order ignored. |
| `isSubsetOf` | `isSubsetOf(self $other): bool` | True when every element of `$this` is in `$other`. |
| `map` | `map(callable $fn): self` | Transform every element; deduplicates the results. |
| `filter` | `filter(callable $predicate): Set` | Keep elements matching a predicate; returns `Set`. |
| `fold` | `fold(mixed $initial, callable $fn): mixed` | Reduce over elements in insertion order; callback receives `(accumulator, value)`. |
| `tap` | `tap(callable $fn): self` | Side effect per element; returns the same instance. |
| `toSet` | `toSet(): Set` | Convert to a plain `Set`. |
| `toArray` | `toArray(): array` | Return elements as a non-empty list. |
| `getIterator` | `getIterator(): Traversable` | Iterate elements in insertion order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize elements as a JSON array. |

## Next Steps

- See [Set](set.md) when an empty result is valid.
- See [SortedSet](sorted-set.md) for comparator-ordered unique values.
- See [Collections Overview](overview.md) to choose a collection.