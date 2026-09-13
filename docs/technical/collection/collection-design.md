# Design: Collections

## Eager Collections

`Seq`, `NonEmptySeq`, `Map`, and `Set` store PHP arrays and construct new values for transformations.

- `Seq` is ordered and may be empty.
- `NonEmptySeq` rejects an empty source and preserves non-emptiness through `map()`, `append()`, and `prepend()`. Its `filter()` and `tail()` return `Seq` because they may be empty.
- `Map` uses string or integer keys and preserves keys during `map()`.
- `Set` uses strict `in_array()` checks, preserves insertion order, and removes duplicates.

The collection classes do not implement a shared interface. Their contracts are intentionally type-specific.

## Lazy Collection

`LazySeq` stores a source callable and creates a generator when consumed. `map()`, `filter()`, and `take()` build deferred stages; `toList()` materializes consumed values into `Seq`.

Lazy evaluation is not memoization. Re-consuming a sequence can invoke its source again, and a one-shot generator source has no documented repeatability guarantee.

## Invariants and Errors

Empty `Seq::head()` and `tail()` throw `EmptyCollectionException`. Missing `Seq::get()` and `Tuple::at()` indexes throw `OutOfBoundsException`. Negative `LazySeq::take()` and empty `NonEmptySeq` construction throw `InvalidArgumentException`.

## Trade-offs

Array-backed eager collections are straightforward and interoperable with PHP arrays, but transformations allocate arrays. Lazy sequences reduce unnecessary source evaluation when bounded, but add generator and closure behavior and defer failures until consumption.

## Related Documents

- [Immutability](../immutability.md)
- [Performance](../performance.md)
- [Usage: Collections](../../usage/collections/overview.md)