# NonEmptySeq

`NonEmptySeq` is an ordered sequence whose first item is guaranteed to exist.

## Quick Example

```php
use Exact\Collection\NonEmptySeq;

$statuses = NonEmptySeq::of('pending', 'paid');
$labels = $statuses
    ->map(static fn (string $status): string => strtoupper($status))
    ->prepend('order:')
    ->append('fulfilled')
    ->toArray();
```

`map()`, `prepend()`, and `append()` preserve the non-empty guarantee.

## Creating a Non-Empty Sequence

`of($head, ...$tail)` requires a first item. `fromArray()` is useful when values already come in an array:

```php
$requiredStatuses = NonEmptySeq::fromArray(['pending', 'paid']);
```

Passing an empty array to `fromArray()` throws `InvalidArgumentException`.

## Reading Items

`head()` always returns the first value and `size()` reports the number of items. `tail()` returns a regular `Seq`, because a one-item sequence has an empty tail:

```php
$first = $statuses->head();
$count = $statuses->size();
$remaining = NonEmptySeq::of('pending')->tail();
```

`toArray()` returns the zero-based values as a PHP array.

## Transforming and Filtering

`map()` preserves `NonEmptySeq`. `filter()` returns an ordinary `Seq`, since every item could be removed:

```php
$maybeStatuses = $statuses->filter(
    static fn (string $status): bool => $status === 'paid',
);

$withCancelled = $statuses->append('cancelled');
```

## Next Steps

- Compare this guarantee with [Seq](seq.md), whose result may be empty.
- Use [Validated](../fundamentals/validated.md) when non-empty input is one rule among several.

## Next Steps

- Compare its guarantee with [Seq](seq.md).
- Use [Validated](../fundamentals/validated.md) when non-empty input is one validation rule among several.