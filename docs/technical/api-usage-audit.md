# Public API Documentation Inventory

This inventory describes the current documented user-facing areas. The source under `src/` remains authoritative for the complete public API; this table is a guide to the usage documentation, not a generated method reference.

| API area | Main concepts | Usage documentation |
| --- | --- | --- |
| Types | `Option`, `Either`, `Validated`, `Tuple`, `Pair` | [Fundamentals](../usage/README.md#fundamentals) |
| Pattern matching | `Variant`, `PatternMatch`, `Matcher` | [Algebraic Data Types](../usage/modeling/adt.md), [Pattern Matching](../usage/patterns/pattern-matching.md) |
| Collections | `Vec`, `NonEmptyVec`, `LazySeq`, `NonEmptyLazySeq`, `Range`, `Deque`, `PriorityQueue`, `Map`, `NonEmptyMap`, `SortedMap`, `BiMap`, `MultiMap`, `Set`, `NonEmptySet`, `SortedSet`, `MultiSet` | [Collections](../usage/collections/overview.md) |
| Exceptions | Empty collection and unmatched-pattern behavior | [Error Handling](../usage/patterns/error-handling.md) and individual collection guides |

The library currently has no `Result`, `Value`, or `Function` module. Usage guides for those removed modules were removed so the published documentation describes supported APIs. Every concrete collection class currently in `src/Collection/` has a usage guide, except the legacy `Seq` and `NonEmptySeq` aliases, which are documented with `Vec` and `NonEmptyVec`.

## Documentation Maintenance

When a public contract changes, update the relevant usage guide and technical contract together. Verify that links and examples refer to APIs that still exist, and run the documented command (`composer test`) when code behavior is affected.
