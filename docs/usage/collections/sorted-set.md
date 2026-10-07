# SortedSet

`SortedSet` stores unique values in comparator order. Equality and duplicate detection follow the comparator: values comparing as zero occupy the same set entry.

## Quick Example

```php
use Exact\Collection\SortedSet;

$scores = SortedSet::of(
    static fn (int $left, int $right): int => $left <=> $right,
    8,
    3,
    5,
    3,
);

$ordered = $scores->toArray(); // [3, 5, 8]
$middle = $scores->range(4, 8)->toArray(); // [5, 8]
```

`add()` and `remove()` return new sets. `union()`, `intersect()`, `difference()`, and `symmetricDifference()` provide set operations; `isSubsetOf()` and `isSupersetOf()` check relationships. `first()` / `last()` return `null` when empty. `map()` takes a comparator for the mapped value type, while `filter()` retains the current comparator.

Lookup uses binary search, while insertion is linear because it shifts array elements in the backing list. Prefer [Set](set.md) when sorted iteration is not required.

## When to Use SortedSet

Use `SortedSet` when:

- Values must remain unique **and** ordered by a comparator.
- You want range queries over values.
- Iteration, `first()`, and `last()` must follow the comparator order.

Use [Set](set.md) when sorted iteration is not required, [NonEmptySet](non-empty-set.md) when the set must contain at least one value, and [SortedMap](sorted-map.md) when the ordering applies to keys rather than values.

## Comparator Semantics

The comparator is a callable `(T, T): int` returning an integer like `<=>`:

- Negative — `$a` sorts before `$b`.
- Zero — `$a` and `$b` are **the same entry**.
- Positive — `$a` sorts after `$b`.

The zero case is the crucial one: two values that compare as `0` are treated as duplicates, and the second one is rejected by `add()`.

```php
$caseInsensitive = SortedSet::of(
    static fn (string $a, string $b): int => strcasecmp($a, $b),
    'Read',
    'READ',   // rejected — compares as 0 to 'Read'
    'Write',
);

$caseInsensitive->count();          // 2
$caseInsensitive->contains('read'); // true
```

The default comparator is `fn ($a, $b) => $a <=> $b`, used by `empty()` when no comparator is provided.

## Complexity

| Operation | Cost |
|---|---|
| `add()` | O(n) — binary search O(log n) plus `array_splice` O(n) |
| `remove()` | O(n) — same reason |
| `contains()` | O(log n) |
| `first()` / `last()` | O(1) |
| `range()` | O(n) |
| `union()` / `intersect()` / `difference()` | O(n log n) or worse |

`SortedSet` is suitable for small-to-medium sets. For large sets, use a hash-based [Set](set.md) with on-demand sorting.

## Creating a SortedSet

```php
use Exact\Collection\SortedSet;

$empty    = SortedSet::empty();                     // spaceship comparator
$custom   = SortedSet::empty(
    static fn (int $a, int $b): int => $b <=> $a,   // max-first
);

$fromValues = SortedSet::of(
    static fn (int $a, int $b): int => $a <=> $b,
    8, 3, 5, 3,                                     // duplicate 3 ignored
);

$fromArray = SortedSet::fromArray(
    [8, 3, 5, 3],
    static fn (int $a, int $b): int => $a <=> $b,
);

$fromIter = SortedSet::fromIterable(
    new ArrayIterator([8, 3, 5, 3]),
    static fn (int $a, int $b): int => $a <=> $b,
);
```

- `SortedSet::empty(?callable $comparator = null)` creates an empty set; defaults to the spaceship comparator.
- `SortedSet::of(callable $comparator, mixed ...$items)` takes the comparator **first**, then the values.
- `SortedSet::fromArray(array $items, callable $comparator)` takes items first, comparator second.
- `SortedSet::fromIterable(iterable $items, callable $comparator)` takes items first, comparator second.

### A note on the argument order

`of()` puts the comparator before the values, while `fromArray()` / `fromIterable()` put items first. This matches the factory styles of the broader codebase (variadic `of()` needs a leading anchor; iterable factories follow the usual `(items, comparator)` order), but it is worth remembering.

```php
// of(): comparator first
SortedSet::of($cmp, 1, 2, 3);

// fromArray() / fromIterable(): items first
SortedSet::fromArray([1, 2, 3], $cmp);
SortedSet::fromIterable([1, 2, 3], $cmp);
```

## Adding and Removing

```php
$set = SortedSet::of(
    static fn (int $a, int $b): int => $a <=> $b,
    3, 5,
);

$withEight = $set->add(8);      // [3, 5, 8]
$withOne   = $set->add(1);      // [1, 3, 5]
$unchanged = $set->add(5);      // same instance — 5 already present

$withoutFive = $set->remove(5); // [3]
$noOp        = $set->remove(10); // same instance — 10 absent
```

### `add(mixed $value)`

Uses binary search to find the insertion point. If the value already exists (comparator returns `0` at some index), the same instance is returned. Otherwise, splices the value into the sorted array at the correct position. O(n).

### `remove(mixed $value)`

Uses binary search. If absent, the same instance is returned. Otherwise, splices the value out of the array. O(n).

Both operations return the same instance on no-op, preserving identity equality for callers who care.

## Checking Membership

```php
$set = SortedSet::of(
    static fn (int $a, int $b): int => $a <=> $b,
    3, 5, 8,
);

$hasFive  = $set->contains(5);   // true
$hasSeven = $set->contains(7);   // false

$count = $set->count();          // 3
$size  = $set->size();           // 3, alias for count()
$empty = SortedSet::empty()->isEmpty(); // true
```

- `contains(mixed $value)` uses binary search. O(log n).
- `count()` / `size()` return the number of distinct values.
- `isEmpty()` reports whether the set has no values.

`SortedSet` implements `Countable`, so `count($sortedSet)` (PHP's function) returns the same value as `$sortedSet->count()`.

## First and Last

```php
$set = SortedSet::of(
    static fn (int $a, int $b): int => $a <=> $b,
    3, 5, 8,
);

$smallest = $set->first(); // 3
$largest  = $set->last();  // 8

SortedSet::empty()->first(); // null
SortedSet::empty()->last();  // null
```

- `first()` returns the smallest value by the comparator, or `null` when empty.
- `last()` returns the largest value by the comparator, or `null` when empty.

`first()` and `last()` are O(1) because values are stored in comparator order.

## Set Algebra

```php
$a = SortedSet::of(
    static fn (int $x, int $y): int => $x <=> $y,
    1, 3, 5,
);
$b = SortedSet::of(
    static fn (int $x, int $y): int => $x <=> $y,
    3, 4, 5,
);

$union      = $a->union($b);               // [1, 3, 4, 5]
$intersect  = $a->intersect($b);           // [3, 5]
$difference = $a->difference($b);          // [1]
$symmetric  = $a->symmetricDifference($b); // [1, 4]
```

### `union(self $other)`

Returns the sorted union of both sets. Both sets must share the same comparator contract for the result to be sensible. `$this`'s comparator is retained.

### `intersect(self $other)`

Values present in both sets, in the order of `$this`. Uses `$other->contains()` (binary search on `$other`).

### `difference(self $other)`

Values in `$this` not present in `$other`, in the order of `$this`.

### `symmetricDifference(self $other)`

Values present in exactly one set. Implemented as `$this->difference($other)->union($other->difference($this))`.

## Comparing Sets

```php
$a = SortedSet::of(static fn (int $x, int $y): int => $x <=> $y, 1, 3, 5);
$b = SortedSet::of(static fn (int $x, int $y): int => $x <=> $y, 5, 1, 3);
$c = SortedSet::of(static fn (int $x, int $y): int => $x <=> $y, 1, 3);

$a->equals($b);        // true  — same values, comparator forces same order
$a->equals($c);        // false — different sizes
$a->isSubsetOf($b);    // true
$c->isSupersetOf($a);  // false
```

- `equals(self $other)` returns `true` when both sets have the same size and every value of `$this` is in `$other`.
- `isSubsetOf(self $other)` returns `true` when every value of `$this` is in `$other`.
- `isSupersetOf(self $other)` is `$other->isSubsetOf($this)`.

Because `SortedSet` stores values in comparator order, two sets built with the same comparator and holding the same values will always iterate the same way.

## Range Queries

```php
$set = SortedSet::of(
    static fn (int $a, int $b): int => $a <=> $b,
    1, 3, 5, 7, 9,
);

$middle = $set->range(3, 7); // [3, 5, 7]
$all    = $set->range(0, 10); // same as $set
$none   = $set->range(4, 4); // empty if 4 is not present
```

`range(mixed $low, mixed $high)` returns a new `SortedSet` containing only values `$v` for which `$low <= $v <= $high` under the comparator. **Both endpoints are inclusive.** If `$low` compares greater than `$high`, the result is empty.

The result uses the same comparator as the original set.

## Transforming Values

```php
$set = SortedSet::of(
    static fn (int $a, int $b): int => $a <=> $b,
    1, 2, 3,
);

$doubled = $set->map(
    static fn (int $n): int => $n * 2,
    static fn (int $a, int $b): int => $a <=> $b,
);
// [2, 4, 6]

$evens = $set->filter(
    static fn (int $n): bool => $n % 2 === 0,
);
// [2]

$inspected = $set->tap(
    static fn (int $n): void => error_log("n={$n}"),
);
```

- `map(callable $fn, callable $comparator)` transforms every value and **requires a comparator** for the mapped type. This is necessary because the mapped values may have a different type, and the original comparator may not apply.
- `filter(callable $predicate)` keeps values that satisfy the predicate and retains the current comparator.
- `tap(callable $fn)` runs the callback for side effects and returns the same instance.

### Why `map()` needs a comparator

Unlike `Set::map()`, which produces a `Set` that deduplicates by strict equality, `SortedSet::map()` produces values that may no longer be comparable by the original comparator. The new comparator defines both the sort order and the equality rule for the mapped values:

```php
$lengths = SortedSet::of(
    static fn (string $a, string $b): int => strcmp($a, $b),
    'banana', 'apple', 'cherry',
)->map(
    static fn (string $fruit): int => strlen($fruit),
    static fn (int $a, int $b): int => $a <=> $b,
);
// [5, 6] — 'apple' (5) and 'banana'/'cherry' (6 each, deduplicated)
```

If the mapped values collide under the new comparator, the later one is rejected, exactly like `add()` on the original set.

## Reducing

```php
$set = SortedSet::of(
    static fn (int $a, int $b): int => $a <=> $b,
    1, 2, 3,
);

$sum = $set->fold(
    0,
    static fn (int $acc, int $n): int => $acc + $n,
); // 6
```

`fold($initial, callable $fn)` reduces values in comparator order. The callback receives `(accumulator, value)`.

`SortedSet` has no `reduce()` — a set can be empty, so an initial value is required.

## Interop

```php
$set = SortedSet::of(
    static fn (int $a, int $b): int => $a <=> $b,
    3, 1, 2,
);

$array = $set->toArray();
// [1, 2, 3]

foreach ($set as $value) {
    // iterate in comparator order
}

$json = json_encode($set);
// [1,2,3]
```

- `toArray()` returns the values as a list in comparator order.
- `getIterator()` yields values with integer indexes starting at 0, in comparator order.
- `jsonSerialize()` produces a JSON array.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(?callable $comparator = null): self` | Create an empty set. Uses the spaceship comparator by default. |
| `of` | `static of(callable $comparator, mixed ...$items): self` | Create from values; comparator **first**. |
| `fromArray` | `static fromArray(array $items, callable $comparator): self` | Create from an array; items first, comparator second. |
| `fromIterable` | `static fromIterable(iterable $items, callable $comparator): self` | Create from any iterable; items first, comparator second. |
| `add` | `add(mixed $value): self` | Insert if absent; returns the same instance when already present. O(n). |
| `remove` | `remove(mixed $value): self` | Remove if present; returns the same instance when absent. O(n). |
| `contains` | `contains(mixed $value): bool` | Check membership using binary search. O(log n). |
| `first` | `first(): mixed` | Smallest value by comparator, or `null`. |
| `last` | `last(): mixed` | Largest value by comparator, or `null`. |
| `isEmpty` | `isEmpty(): bool` | Report whether the set has no values. |
| `count` | `count(): int` | Number of distinct values. |
| `size` | `size(): int` | Alias for `count()`. |
| `union` | `union(self $other): self` | Sorted union of both sets. |
| `intersect` | `intersect(self $other): self` | Values present in both sets. |
| `difference` | `difference(self $other): self` | Values in `$this` not present in `$other`. |
| `symmetricDifference` | `symmetricDifference(self $other): self` | Values present in exactly one set. |
| `isSubsetOf` | `isSubsetOf(self $other): bool` | True when every value of `$this` is in `$other`. |
| `isSupersetOf` | `isSupersetOf(self $other): bool` | True when `$other` is a subset of `$this`. |
| `range` | `range(mixed $low, mixed $high): self` | Values with `$low <= $v <= $high` under the comparator. |
| `map` | `map(callable $fn, callable $comparator): self` | Transform values; requires a comparator for the mapped type. |
| `filter` | `filter(callable $predicate): self` | Keep values matching a predicate; retains the comparator. |
| `fold` | `fold(mixed $initial, callable $fn): mixed` | Reduce in comparator order; callback receives `(accumulator, value)`. |
| `tap` | `tap(callable $fn): self` | Side effect per value; returns the same instance. |
| `equals` | `equals(self $other): bool` | Compare by size and membership; order ignored. |
| `toArray` | `toArray(): array` | Values as a list in comparator order. |
| `getIterator` | `getIterator(): Traversable` | Iterate values in comparator order. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize values as a JSON array. |

## Next Steps

- See [NonEmptySet](non-empty-set.md) for an enforced non-empty invariant.
- See [SortedMap](sorted-map.md) for comparator-ordered keys.
- See [Range](range.md) for lazy integer intervals.
- See [Collections Overview](overview.md) to choose a collection.