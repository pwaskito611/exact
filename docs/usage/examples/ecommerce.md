# E-commerce

An order total can combine lazy input, collection transformations, and `Result` values without exposing collection internals to the caller.

```php
use Exact\Collection\LazySeq;

$items = LazySeq::defer(static function (): iterable {
    yield ['name' => 'book', 'price' => 10];
    yield ['name' => 'pen', 'price' => 3];
    yield ['name' => 'bag', 'price' => 25];
});

$total = $items
    ->filter(static fn (array $item): bool => $item['price'] >= 5)
    ->map(static fn (array $item): int => $item['price'])
    ->toList()
    ->foldLeft(0, static fn (int $amount, int $price): int => $amount + $price);

$amount = $total; // 35
```

`LazySeq` defers reading the items, `filter()` selects the products to total, and `foldLeft()` produces the final amount. When a price check can fail, model that operation with [Result](../fundamentals/result.md) as described in [Error Handling](../patterns/error-handling.md).

## Next Steps

- See [LazySeq](../collections/lazy-seq.md).
- See [Seq](../collections/seq.md) for `foldLeft()`.
- See [Error Handling](../patterns/error-handling.md) for choosing between `Option`, `Result`, and `Validated`.