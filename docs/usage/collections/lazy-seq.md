# LazySeq

`LazySeq` defers source evaluation and transformation until `toList()` is called. It is useful for generators and bounded pipelines.

## Basic Usage

```php
use Exact\Collection\LazySeq;

$numbers = LazySeq::defer(function (): iterable {
    for ($number = 1; $number <= 1000; $number++) {
        yield $number;
    }
});

$firstThreeEvenSquares = $numbers
    ->filter(static fn (int $number): bool => $number % 2 === 0)
    ->map(static fn (int $number): int => $number ** 2)
    ->take(3)
    ->toList();
```

The resulting value is a `Seq` containing `[4, 16, 36]`. `take()` stops requesting values after the requested number has been produced. A negative count throws `InvalidArgumentException`.

## Creating from an Iterable

Use `LazySeq::from()` for an existing iterable:

```php
$lazy = LazySeq::from([1, 2, 3]);
$values = $lazy->toList()->toArray();
```

Use `defer()` when creating the source itself should wait until consumption.

## Next Steps

- Combine lazy processing with [Result](../fundamentals/result.md) in [E-commerce](../examples/ecommerce.md).
- Use [Function Composition](../functional/composition.md) for reusable pipeline functions.