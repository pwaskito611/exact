# Set

`Set` keeps strictly unique values in insertion order.

## Quick Example

```php
use Exact\Collection\Set;

$roles = Set::of('editor', 'reviewer', 'editor')
    ->add('publisher');
$reviewTeam = $roles->intersect(Set::of('reviewer', 'publisher'));

$roleNames = $reviewTeam->map(static fn (string $role): string => strtoupper($role));
```

Repeated values are kept once. Set transformations return a `Set` and preserve insertion order.

## Creating and Checking a Set

Use `empty()` for no values or `of(...$values)` to build one. `contains()` uses strict comparison; `size()` counts unique values and `isEmpty()` checks for no values:

```php
$empty = Set::empty();
$permissions = Set::of('read', 'write');
$canWrite = $permissions->contains('write');
$permissionCount = $permissions->size();
$hasNoPermissions = $empty->isEmpty();
```

## Adding, Removing, and Combining Values

`add()` includes a value only if no strictly equal value exists. `remove()` drops the strictly equal value. `union()` combines values from both sets; `intersect()` keeps values present in both:

```php
$updated = $permissions->add('admin')->remove('read');
$all = $permissions->union(Set::of('admin'));
$shared = $permissions->intersect(Set::of('write', 'admin'));
```

`map()` transforms values and removes duplicates in its result. `toArray()` returns values in insertion order:

```php
$normalized = Set::of('READ', 'read')->map(static fn (string $role): string => strtolower($role));
$values = $normalized->toArray(); // ['read']
```

## Next Steps

- Use [Map](map.md) for key-value data.
- Use [Seq](seq.md) when duplicate values and order-preserving transformations are both meaningful.