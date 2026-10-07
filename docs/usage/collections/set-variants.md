# Set Variants

Choose between `NonEmptySet` and `SortedSet` according to the invariant your domain needs.

## NonEmptySet

`NonEmptySet` preserves at least one distinct value. It is insertion-ordered and uses strict equality:

```php
use Exact\Collection\NonEmptySet;

$required = NonEmptySet::of('read', 'write')->add('admin');
$filtered = $required->filter(
    static fn (string $permission): bool => $permission !== 'admin',
);
```

Operations that can remove every value return `Set`. See [NonEmptySet](non-empty-set.md) for the full invariant and conversion behavior.

## SortedSet

`SortedSet` uses a comparator both to order values and to determine whether two values count as duplicates:

```php
use Exact\Collection\SortedSet;

$scores = SortedSet::of(
    static fn (int $left, int $right): int => $left <=> $right,
    8,
    3,
    5,
);
```

See [SortedSet](sorted-set.md) for set algebra, range selection, and performance characteristics.

Use [Set](set.md) when insertion order and strict duplicate checks are enough and neither a non-empty guarantee nor comparator ordering is needed.
