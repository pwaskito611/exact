# Set

`Set` keeps strictly unique values in insertion order. It is immutable: every operation returns a new set.

## Quick Example

```php
use Exact\Collection\Set;

$roles = Set::of('editor', 'reviewer', 'editor')
    ->add('publisher');
$reviewTeam = $roles->intersect(Set::of('reviewer', 'publisher'));

$roleNames = $reviewTeam->map(static fn (string $role): string => strtoupper($role));
```

Repeated values are kept once. Set transformations return a `Set` and preserve insertion order.

## When to Use Set

Use `Set` when:

- Duplicates must be removed automatically.
- You want insertion order preserved among distinct values.
- You want set algebra (`union`, `intersect`, `difference`, `symmetricDifference`).

Use [Map](map.md) for key-value data, [Vec](vec.md) when duplicate values and order-preserving transformations are both meaningful, and [NonEmptySet](non-empty-set.md) when the set must always contain at least one value.

## Equality and Deduplication

`Set` uses **strict comparison** (`===`) everywhere — for `contains()`, for deduplication in `of()` / `fromArray()` / `fromIterable()`, and for `remove()` / `intersect()` / `difference()`.

```php
$set = Set::of(1, '1', 1.0, true);

$set->size();            // 4
$set->contains(1);       // true
$set->contains('1');     // true
$set->contains(1.0);     // true
$set->contains(true);    // true
```

Consequences of strict comparison:

- Scalars of different types are distinct, even when loosely equal.
- Objects are compared by identity — two separate instances with the same properties are different values.
- Arrays are compared recursively by value and key order.

This makes `Set` predictable but slightly different from PHP's `in_array()` with loose comparison. If you need value-based object equality, provide a normalization step before adding to the set.

## Creating a Set

```php
use Exact\Collection\Set;

$empty      = Set::empty();
$fromValues = Set::of('read', 'write', 'read'); // 'read' kept once
$fromArray  = Set::fromArray(['read', 'write', 'read']);
$fromIter   = Set::fromIterable(new ArrayIterator(['read', 'write', 'read']));
```

- `Set::empty()` creates an empty set.
- `Set::of(mixed ...$items)` deduplicates the given values, keeping the first occurrence of each.
- `Set::fromArray(array $items)` is equivalent to `Set::of(...$items)`.
- `Set::fromIterable(iterable $items)` spreads any iterable into `of()`.

Insertion order is preserved for the first occurrence of each distinct value.

## Checking Membership

```php
$permissions = Set::of('read', 'write');

$canWrite   = $permissions->contains('write');    // true
$canDelete  = $permissions->contains('delete');   // false

$count       = $permissions->size();    // 2
$alsoCount   = $permissions->count();   // 2, alias for size()
$empty       = Set::empty()->isEmpty(); // true
```

- `contains(mixed $value)` checks membership using strict comparison.
- `size()` returns the number of distinct values.
- `count()` is an alias for `size()`.
- `isEmpty()` reports whether the set has no values.

`Set` implements `Countable`, so `count($set)` (PHP's function) returns the same value as `$set->size()`.

## Adding and Removing Values

```php
$permissions = Set::of('read', 'write');

$withAdmin = $permissions->add('admin');    // Set ['read', 'write', 'admin']
$unchanged = $permissions->add('read');     // same instance — 'read' already present

$withoutRead = $permissions->remove('read');   // Set ['write']
$noOp        = $permissions->remove('delete'); // Set ['read', 'write'] — new instance
```

### `add(mixed $value)`

Returns a new `Set` with `$value` appended if it is not already present. When the value is already present, the same instance is returned — a no-op.

Because a `Set` is not typed as non-empty, `add()` cannot make it empty; the return type is always `Set`.

### `remove(mixed $value)`

Returns a new `Set` without the strictly equal value. Unlike `add()`, `remove()` always constructs a new instance even when the value is absent:

```php
$set = Set::of('a');
$set->remove('b') === $set; // false — a new instance is returned
```

Use `contains()` first if you need to avoid the allocation.

## Set Algebra

```php
$a = Set::of('read', 'write', 'admin');
$b = Set::of('write', 'execute');

$union        = $a->union($b);               // ['read', 'write', 'admin', 'execute']
$intersect    = $a->intersect($b);           // ['write']
$difference   = $a->difference($b);          // ['read', 'admin']
$symmetric    = $a->symmetricDifference($b); // ['read', 'admin', 'execute']
```

### `union(self $other)`

Returns a new `Set` containing all values from both sets. Order: all values from `$this` first, then values from `$other` that are not already present.

### `intersect(self $other)`

Returns values present in both sets, preserving the order of `$this`.

### `difference(self $other)`

Returns values in `$this` that are **not** in `$other`, preserving the order of `$this`.

### `symmetricDifference(self $other)`

Returns values present in exactly one of the two sets. Equivalent to `$this->difference($other)->union($other->difference($this))`. Order: `$this`-only values first, then `$other`-only values.

## Comparing Sets

```php
$a = Set::of('read', 'write');
$b = Set::of('write', 'read');
$c = Set::of('read', 'write', 'admin');
$d = Set::of('read', 'admin');

$a->equals($b);            // true  — same values, order irrelevant
$a->equals($c);            // false — different sizes
$a->isSubsetOf($c);        // true
$c->isSupersetOf($a);      // true
$a->isDisjoint(Set::of('execute')); // true
$a->isDisjoint(Set::of('read'));    // false
```

- `equals(self $other)` returns `true` when both sets have the same size and every value of `$this` is in `$other`. Order does not matter.
- `isSubsetOf(self $other)` returns `true` when every value of `$this` is in `$other`.
- `isSupersetOf(self $other)` is the mirror of `isSubsetOf()`.
- `isDisjoint(self $other)` returns `true` when the sets share no values.

All four are membership-based, not order-based.

## Transforming Values

```php
$roles = Set::of('READ', 'read', 'WRITE');

$lowerCased = $roles->map(
    static fn (string $role): string => strtolower($role),
);
// Set ['read', 'write'] — collapsed duplicates

$longRoles = $roles->filter(
    static fn (string $role): bool => strlen($role) > 4,
);
// Set ['WRITE']

$inspected = $roles->tap(
    static fn (string $role): void => error_log("role={$role}"),
);
```

- `map(callable $fn)` transforms every value and **deduplicates the results** using strict comparison. Returns `Set`.
- `filter(callable $predicate)` keeps values where the predicate is `true`. Returns `Set`.
- `tap(callable $fn)` runs the callback for side effects and returns the same instance.

### `map()` and deduplication

`map()` applies the callback to each value, then runs the results through `Set::of()`, which deduplicates. The resulting set may therefore be smaller than the input:

```php
Set::of('a', 'A')
    ->map(static fn (string $s): string => strtolower($s))
    ->size(); // 1 — both map to 'a'
```

`Set::of('a', 'A')` starts with two values because they are strictly different. After `strtolower`, both become `'a'`, and the result keeps only one.

## Reducing

```php
$permissions = Set::of('read', 'write', 'admin');

$joined = $permissions->fold(
    '',
    static fn (string $acc, string $perm): string => $acc === '' ? $perm : "{$acc}|{$perm}",
); // 'read|write|admin'
```

`fold($initial, callable $fn)` reduces values in insertion order. The callback receives `(accumulator, value)`.

`Set` has no `reduce()` — a set can be empty, so an initial value is required.

## Iterating and Converting

```php
$permissions = Set::of('read', 'write', 'admin');

foreach ($permissions as $permission) {
    // iterate in insertion order
}

$array = $permissions->toArray();
// ['read', 'write', 'admin']

$json = json_encode($permissions);
// ["read","write","admin"]
```

- `getIterator()` yields values with integer indexes starting at 0.
- `toArray()` returns the values as a list in insertion order.
- `jsonSerialize()` produces a JSON array.

## Complete Method Reference

| Method | Signature | Description |
|---|---|---|
| `empty` | `static empty(): self` | Create an empty set. |
| `of` | `static of(mixed ...$items): self` | Create from values; deduplicates using strict comparison. |
| `fromArray` | `static fromArray(array $items): self` | Create from an array; deduplicates. |
| `fromIterable` | `static fromIterable(iterable $items): self` | Create from any iterable; deduplicates. |
| `add` | `add(mixed $value): self` | Append if absent; returns the same instance when already present. |
| `remove` | `remove(mixed $value): self` | Remove the strictly equal value; always returns a new instance. |
| `contains` | `contains(mixed $value): bool` | Check membership using strict comparison. |
| `size` | `size(): int` | Number of distinct values. |
| `count` | `count(): int` | Alias for `size()`. |
| `isEmpty` | `isEmpty(): bool` | Report whether the set has no values. |
| `union` | `union(self $other): self` | All values from both sets. |
| `intersect` | `intersect(self $other): self` | Values present in both sets. |
| `difference` | `difference(self $other): self` | Values in `$this` not present in `$other`. |
| `symmetricDifference` | `symmetricDifference(self $other): self` | Values present in exactly one set. |
| `isSubsetOf` | `isSubsetOf(self $other): bool` | True when every value of `$this` is in `$other`. |
| `isSupersetOf` | `isSupersetOf(self $other): bool` | True when `$other` is a subset of `$this`. |
| `isDisjoint` | `isDisjoint(self $other): bool` | True when the sets share no values. |
| `equals` | `equals(self $other): bool` | Compare by membership; order ignored. |
| `map` | `map(callable $fn): self` | Transform every value; deduplicates the results. |
| `filter` | `filter(callable $predicate): self` | Keep values matching a predicate. |
| `fold` | `fold(mixed $initial, callable $fn): mixed` | Reduce over values; callback receives `(accumulator, value)`. |
| `tap` | `tap(callable $fn): self` | Side effect per value; returns the same instance. |
| `getIterator` | `getIterator(): Traversable` | Iterate values in insertion order. |
| `toArray` | `toArray(): array` | Return values as a list. |
| `jsonSerialize` | `jsonSerialize(): array` | Serialize values as a JSON array. |

## Next Steps

- Use [Map](map.md) for key-value data.
- Use [Vec](vec.md) when duplicate values and order-preserving transformations are both meaningful.
- Use [NonEmptySet](non-empty-set.md) when the set must always contain at least one value.
- See [Collections Overview](overview.md) to choose a collection.