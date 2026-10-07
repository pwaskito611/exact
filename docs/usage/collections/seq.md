# Vec

`Vec` is an ordered collection that can be empty.

## Quick Example

```php
use Exact\Collection\Vec;

$featuredProducts = Vec::of('book', 'pen', 'bag')
    ->filter(static fn (string $product): bool => str_starts_with($product, 'b'))
    ->map(static fn (string $product): string => strtoupper($product))
    ->append('NOTEBOOK');

$products = $featuredProducts->toArray(); // ['BOOK', 'BAG', 'NOTEBOOK']
```

Each transformation returns a new `Vec`; the input remains unchanged.

## Creating a Sequence

Use `empty()` for no items, `of(...$items)` for individual values, or `fromArray($items)` for an existing array. The factories normalize indexes to a zero-based sequence:

```php
$empty = Vec::empty();
$catalog = Vec::fromArray(['book', 'pen']);
```

## Checking and Reading Items

`isEmpty()`, `isNotEmpty()`, and `size()` describe the collection. `head()` reads the first item, `tail()` returns the remaining `Vec`, and `get()` reads a zero-based index:

```php
$products = Vec::of('book', 'pen');
$hasProducts = $products->isNotEmpty();
$isEmpty = Vec::empty()->isEmpty();
$count = $products->size();
$first = $products->head();
$rest = $products->tail();
$second = $products->get(1);
$hasPen = $products->contains('pen');
```

`head()` and `tail()` throw `EmptyCollectionException` on an empty sequence. `get()` throws `OutOfBoundsException` for an index that does not exist. `contains()` uses strict comparison.

Use `each()` only when a callback needs a side effect; it returns `void`:

```php
$products->each(static function (string $product): void {
    error_log($product);
});
```

## Transforming and Reducing

`map()` transforms each item; `filter()` keeps items whose predicate returns `true`. `flatMap()` combines the `Vec` returned for each item:

```php
$expanded = Vec::of('book', 'pen')->flatMap(
    static fn (string $product): Vec => Vec::of($product, "gift-{$product}"),
);
```

The `flatMap()` callback must return a `Vec`, or the call throws `TypeError`. Use `foldLeft()` to reduce items to one result:

```php
$label = $products->foldLeft(
    'Catalog:',
    static fn (string $label, string $product): string => "{$label} {$product}",
);
```

## Adding Items

`append()` adds to the end and `prepend()` adds to the start. Both return a new `Vec`; `toArray()` returns its values as a zero-based PHP array:

```php
$original = Vec::of('pen');
$updated = $original->prepend('book')->append('bag');
```

## Next Steps

- Use [NonEmptyVec](non-empty-seq.md) when emptiness is not valid.
- Use [LazySeq](lazy-seq.md) for deferred input.
- Use [Function Composition](../functional/composition.md) for reusable transformations.