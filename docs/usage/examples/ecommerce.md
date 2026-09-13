# E-commerce

An order total can combine lazy input, collection transformations, and `Result` values without exposing collection internals to the caller.

```php
use Exact\Collection\LazySeq;
use Exact\Data\Result\Result;

$items = LazySeq::defer(static function (): iterable {
    yield ['name' => 'book', 'price' => 10];
    yield ['name' => 'pen', 'price' => 3];
    yield ['name' => 'bag', 'price' => 25];
});

$total = $items
    ->filter(static fn (array $item): bool => $item['price'] >= 5)
    ->map(static fn (array $item): Result => Result::ok($item['price']))
    ->toList()
    ->foldLeft(
        Result::ok(0),
        static fn (Result $current, Result $price): Result => $current->flatMap(
            static fn (int $amount): Result => $price->map(
                static fn (int $value): int => $amount + $value,
            ),
        ),
    );

$amount = $total->getOrElse(0); // 35
```

`LazySeq` defers the source, `filter()` selects eligible items, and `Result` leaves room for a later price or stock failure without changing the collection pipeline shape.

## Next Steps

- See [LazySeq](../collections/lazy-seq.md).
- See [Seq](../collections/seq.md) for `foldLeft()`.
- See [Error Handling](../patterns/error-handling.md) for choosing between `Option`, `Result`, and `Validated`.