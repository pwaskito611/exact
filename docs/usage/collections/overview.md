# Collections Overview

Exact collections provide transformation operations that return new collection values. The main collection types are:

| Type | Use it for |
| --- | --- |
| `Vec` | An ordered, possibly empty sequence. |
| `NonEmptyVec` | An ordered sequence that must contain at least one value. |
| `Map` | Key-value data indexed by string or integer keys. |
| `Set` | Strictly unique values while preserving insertion order. |
| `LazySeq` | Deferred sequence operations, including bounded processing of generators. |

Most eager transformations such as `map()`, `filter()`, `append()`, and `prepend()` return a new collection. See [Vec](seq.md), [Map](map.md), and [Set](set.md) for concrete workflows.

## Choosing a Collection

Use `Vec` when order matters and an empty result is valid. Use `NonEmptyVec` when the input must have a first value. Use `LazySeq` when source evaluation should be deferred or bounded by `take()`.

Use `Map` for keyed lookup and `Set` when duplicate values should be removed with strict comparison.

## Next Steps

- [Vec](seq.md)
- [NonEmptyVec](non-empty-seq.md)
- [LazySeq](lazy-seq.md)