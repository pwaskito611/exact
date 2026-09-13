# Set

`Set` stores unique values using strict comparison and preserves insertion order.

## Basic Usage

```php
use Exact\Collection\Set;

$tags = Set::of('php', 'functional', 'php')
    ->add('domain');

$tags->toArray(); // ['php', 'functional', 'domain']
```

## Combining Sets

```php
$backend = Set::of('php', 'sql');
$shared = $backend->intersect(Set::of('php', 'redis'));
$all = $backend->union(Set::of('redis'));
```

`remove()` returns a set without the strict-equal value. `map()` transforms values and removes duplicates in the mapped result.

## Next Steps

- Use [Map](map.md) for key-value data.
- Use [Seq](seq.md) when duplicate values and order-preserving transformations are both meaningful.