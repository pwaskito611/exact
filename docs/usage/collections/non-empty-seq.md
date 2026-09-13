# NonEmptySeq

`NonEmptySeq` is an ordered sequence that requires at least one value when it is created.

## Basic Usage

```php
use Exact\Collection\NonEmptySeq;

$names = NonEmptySeq::of('Ada', 'Grace');

$first = $names->head();
$allNames = $names->append('Lin')->toArray();
```

`NonEmptySeq::fromArray([])` throws `InvalidArgumentException`. `head()` is therefore always available on the original non-empty value.

## Transforming and Filtering

`map()`, `append()`, and `prepend()` preserve `NonEmptySeq`. `filter()` returns an ordinary `Seq` because every value could be filtered out:

```php
$maybeNames = $names->filter(
    static fn (string $name): bool => str_starts_with($name, 'A'),
);
```

`tail()` returns `Seq`; a one-item `NonEmptySeq` has an empty tail.

## Next Steps

- Compare its guarantee with [Seq](seq.md).
- Use [Validated](../fundamentals/validated.md) when non-empty input is one validation rule among several.