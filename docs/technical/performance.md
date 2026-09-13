# Performance Considerations

No benchmark suite, complexity contract, or memory budget is present in the repository. The following are implementation observations, not measured guarantees.

## Eager Allocation

Eager collection transformations build PHP arrays and new collection objects. `Seq::flatMap()`, `Map::map()`, `Set` operations, and mapping/filtering therefore allocate result storage. Data transformations also commonly create new branch objects.

## Lazy Evaluation

`LazySeq` uses generators and deferred closures. `filter()`, `map()`, and `take()` do not consume the source until `toList()` or another consuming operation is reached. `take()` can stop source evaluation early. `toList()` materializes the consumed values into a `Seq`.

Lazy sequences are not memoized. A source callable may run again on another consumption, while a one-shot generator may not be reusable; this behavior has no formal repeatability guarantee.

## Other Costs

`Set` uniqueness and membership use strict linear searches through the stored array. Function composition adds callable invocation layers. Object allocation and closure overhead are normal consequences of the value-oriented API.

## What Is Unknown

There are no repository measurements for throughput, allocations, memory, asymptotic behavior, or comparisons with native PHP arrays. Performance-sensitive changes require benchmarks appropriate to the target workload rather than assumptions from this document.

## Related Documents

- [Collection Design](collection/collection-design.md)
- [Immutability](immutability.md)