# Design: Collections

## Eager Collections

`Vec`, `NonEmptyVec`, `Map`, and `Set` store PHP arrays and construct new values for transformations.

- `Vec` is ordered and may be empty.
- `NonEmptyVec` rejects an empty source and preserves non-emptiness through `map()`, `append()`, and `prepend()`. Its `filter()` and `tail()` return `Vec` because they may be empty.
- `Map` uses string or integer keys and preserves keys during `map()`.
- `Set` uses strict `in_array()` checks, preserves insertion order, and removes duplicates.

The collection classes do not implement a shared interface. Their contracts are intentionally type-specific.

## Lazy Collection

`LazySeq` stores a source callable and creates a generator when consumed. `map()`, `select()`/`project()`, `filter()`, `filterMap()`, `scan()`, `flatten()`, `distinctBy()`, and `take()` build deferred stages; `toSeq()` materializes consumed values into `Vec`. `groupBy()`, `sortBy()`, `minBy()`, and `maxBy()` are terminal/eager operations despite the lazy receiver.

Lazy evaluation is not memoization. Re-consuming a sequence can invoke its source again, and a one-shot generator source has no documented repeatability guarantee.

## Join and relational processing

`Vec` supports immutable data processing, indexing/grouping, and in-memory relational joins without introducing a separate ORM or SQL abstraction. `select()`/`project()` provide callback-driven projection; `filterMap()` keeps present mapped values, `partition()` splits into matching and non-matching Vecs, `scan()` emits a running accumulator, `flatten()` flattens nested supported iterables, `distinctBy()` removes repeated selector keys, `sortBy()` orders by a selector, `minBy()`/`maxBy()` select extrema, and `indexBy()`/`keyBy()` index values in a `Map`.

`Map` includes `filterMap()` and `partition()` for value transformations while retaining each original key.

- `innerJoin()` matches left and right records by key and yields a combined row for each match.
- `leftJoin()` preserves every left record and wraps missing right matches in `Option`.
- `rightJoin()` preserves every right record and wraps missing left matches in `Option`.
- `fullJoin()` keeps matched, left-only, and right-only rows, with each side represented as `Option`.
- `crossJoin()` computes the cartesian product and is documented as O(n × m).

These joins are eager and deterministic: output order follows the left collection order for left-preserving joins, the right collection order for right-preserving joins, and the left-then-right ordering for full joins. Duplicate keys are supported by iterating every matching pair, which preserves relational semantics without silently discarding duplicates.

The separate [`Query` module](../query/query-design.md) composes iterable collections into query pipelines. Collections do not depend on Query or expose Query-specific APIs.

## Invariants and Errors

Empty `Vec::head()` and `tail()` throw `EmptyCollectionException`. Missing `Vec::get()` and `Tuple::at()` indexes throw `OutOfBoundsException`. Negative `LazySeq::take()` and empty `NonEmptyVec` construction throw `InvalidArgumentException`.

## Trade-offs

Array-backed eager collections are straightforward and interoperable with PHP arrays, but transformations allocate arrays. Lazy sequences reduce unnecessary source evaluation when bounded, but add generator and closure behavior and defer failures until consumption.

## Related Documents

- [Immutability](../immutability.md)
- [Performance](../performance.md)
- [Usage: Collections](../../usage/collections/overview.md)