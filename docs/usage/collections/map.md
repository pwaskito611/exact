# Map

`Map` stores values under string or integer keys.

## Quick Example

```php
use Exact\Collection\Map;

$prices = Map::of('book', 10, 'pen', 3);
$priceList = $prices
    ->map(static fn (int $price, string $product): string => "{$product}: {$price}")
    ->put('bag', 'bag: 25')
    ->remove('pen')
    ->toArray();
```

`put()` and `remove()` return updated maps; `$prices` remains unchanged.

## Creating a Map

Use `empty()` for no entries, `of()` for alternating key/value arguments, or `fromArray()` for an existing PHP array:

```php
$empty = Map::empty();
$fromArray = Map::fromArray(['book' => 10, 'pen' => 3]);
$isEmpty = $empty->isEmpty();
$entryCount = $fromArray->size();
```

An odd number of arguments to `of()` throws `InvalidArgumentException`.

## Looking Up a Key

`has()` checks whether a key exists. `get()` returns the value or `null`; `getOrElse()` returns the value or its fallback:

```php
$bookPrice = $prices->get('book');
$bagPrice = $prices->getOrElse('bag', 25);
$hasPen = $prices->has('pen');
```

Both `get()` and `getOrElse()` use PHP's null-coalescing behavior, so a stored `null` also produces `null` or the fallback. Use `has()` to distinguish a missing key from a present key containing `null`.

## Updating and Transforming Entries

`put()` sets a key, `remove()` drops it, and `map()` transforms each value. The `map()` callback receives the value first and the key second:

```php
$doubledPrices = $prices->map(
    static fn (int $price, string $product): int => $price * 2,
);
$withoutPen = $prices->remove('pen');
```

`size()` returns the entry count, `isEmpty()` checks whether there are no entries, and `toArray()` returns the keyed PHP array.

## Next Steps

- Use [Seq](seq.md) for ordered values without keys.
- Use [Set](set.md) when uniqueness matters more than keys.