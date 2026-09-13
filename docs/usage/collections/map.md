# Map

`Map` stores values under string or integer keys.

## Basic Usage

```php
use Exact\Collection\Map;

$prices = Map::of('book', 10, 'pen', 3);

$bookPrice = $prices->get('book');
$hasPen = $prices->has('pen');
$withBag = $prices->put('bag', 25);
```

`Map::of()` accepts alternating key and value arguments. `Map::fromArray()` creates a map from an existing array.

## Transforming and Updating

`map()` receives both the value and its key:

```php
$doubled = $prices->map(
    static fn (int $price, string $product): int => $price * 2,
);

$withoutPen = $prices->remove('pen');
```

`getOrElse()` supplies a fallback for a missing key. Use `has()` when distinguishing a missing key from a present key whose value is `null`.

## Next Steps

- Use [Seq](seq.md) for ordered values without keys.
- Use [Set](set.md) when uniqueness matters more than keys.