# E-commerce

An order total can combine lazy input and collection transformations without exposing collection internals to the caller.

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
    ->toSeq()
    ->foldLeft(0, static fn (int $amount, int $price): int => $amount + $price);

$amount = $total; // 35
```

`LazySeq` defers reading the items, `filter()` selects the products to total, and `toSeq()` materializes the selected values as a `Vec` before `foldLeft()` produces the final amount. If a price check has a meaningful alternative, represent it with [Either](../fundamentals/either.md); use [Validated](../fundamentals/validated.md) to accumulate independent input errors.

## Next Steps

- See [LazySeq](../collections/lazy-seq.md).
- See [Vec](../collections/vec.md) for `foldLeft()`.
- See [Error Handling](../patterns/error-handling.md) for choosing between `Option`, `Either`, and `Validated`.