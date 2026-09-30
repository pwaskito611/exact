# LazySeq

`LazySeq` processes an iterable only when you consume it. Use it when a pipeline should stop after a bounded number of values.

`from()` accepts an iterable, while `defer()` accepts a callable that produces an iterable. Both create a lazy sequence; `map()`, `filter()`, and `take()` build further lazy sequences rather than consuming values immediately.

## Quick Example

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

The result is a `Seq` containing `[4, 16, 36]`. `map()`, `filter()`, and `take()` create another lazy pipeline; `toList()` consumes it. `take()` stops requesting source values when it has enough. A negative count throws `InvalidArgumentException`.

## Creating from an Iterable

## Choosing a Source

Use `from()` for an existing iterable and `defer()` when creating the iterable should wait until consumption:

```php
$lazy = LazySeq::from([1, 2, 3]);
$values = $lazy->toList()->toArray();
```

Every `toList()` requests the source again. A `defer()` factory can return a fresh generator each time; an iterable passed to `from()` may be one-shot, such as an already-started `Generator`. `take(0)` returns an empty `Seq` without requesting source values.

## Next Steps

- Combine lazy processing with [Result](../fundamentals/result.md) in [E-commerce](../examples/ecommerce.md).
- Use [Function Composition](../functional/composition.md) for reusable pipeline functions.