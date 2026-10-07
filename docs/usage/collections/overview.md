# Collections Overview

Exact includes 16 concrete collection types. Five common types cover ordered values, key-value data, uniqueness, and deferred iteration; the specialized types below add non-empty invariants, ordering, multiplicity, or bidirectional lookup. The old `Seq` and `NonEmptySeq` names are compatibility aliases for `Vec` and `NonEmptyVec`, not additional collection types.

| Type | Use it for |
| --- | --- |
| [`Vec`](vec.md) | An ordered, possibly empty sequence. |
| [`NonEmptyVec`](non-empty-vec.md) | An ordered sequence that must contain at least one value. |
| [`LazySeq`](lazy-seq.md) | Deferred sequence operations, including bounded processing of generators. |
| [`NonEmptyLazySeq`](non-empty-lazy-seq.md) | Deferred sequence operations with a non-empty contract. |
| [`Range`](range.md) | A lazy integer interval with a configurable step. |
| [`Deque`](deque.md) | An ordered collection with push and pop operations at both ends. |
| [`PriorityQueue`](priority-queue.md) | Values retrieved in comparator priority order. |
| [`Map`](map.md) | Key-value data indexed by string or integer keys. |
| [`NonEmptyMap`](non-empty-map.md) | A map that must retain at least one entry. |
| [`SortedMap`](sorted-map.md) | A map whose keys are kept ordered by a comparator. |
| [`BiMap`](bimap.md) | A one-to-one mapping with unique keys and values and reverse lookup. |
| [`MultiMap`](multimap.md) | Multiple values grouped under each key. |
| [`Set`](set.md) | Strictly unique values while preserving insertion order. |
| [`NonEmptySet`](non-empty-set.md) | A set that must retain at least one value. |
| [`SortedSet`](sorted-set.md) | Unique values kept in comparator order. |
| [`MultiSet`](multiset.md) | Values with tracked multiplicity, also known as a bag. |

Most eager transformations such as `map()`, `filter()`, `append()`, and `prepend()` return a new collection. See [Vec](vec.md), [Map](map.md), and [Set](set.md) for concrete workflows.

## Choosing a Collection

Use `Vec` when order matters and an empty result is valid. Use `NonEmptyVec` when the input must have a first value. Use `LazySeq` when source evaluation should be deferred or bounded by `take()`.

Use `Map` for keyed lookup, `MultiMap` when keys can repeat, and `BiMap` when reverse lookup must be unique. Choose `SortedMap` or `SortedSet` when comparator order is part of the result. Use `MultiSet` when duplicate counts matter, and `PriorityQueue` when the next item should be chosen by priority. `Deque` supports operations at both ends. `Range` represents integer intervals without first allocating an array.

Non-empty variants preserve a non-empty result for operations such as mapping and appending; operations that may remove every item return the corresponding ordinary collection. `NonEmptyLazySeq` applies the same intent to deferred sequences.

## Next Steps

- [Vec](vec.md)
- [NonEmptyVec](non-empty-vec.md)
- [LazySeq](lazy-seq.md)
- [Deque](deque.md)
- [Range](range.md)
- [PriorityQueue](priority-queue.md)
- [Map variants: NonEmptyMap, SortedMap, and BiMap](map-variants.md)
- [MultiMap](multimap.md)
- [Set variants: NonEmptySet and SortedSet](set-variants.md)
- [MultiSet](multiset.md)
- [NonEmptyLazySeq](non-empty-lazy-seq.md)