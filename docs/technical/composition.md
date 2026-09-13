# Composition Across Modules

Exact has two related composition mechanisms.

## Branch Composition

`Option`, `Either`, `Result`, and `Validated` conditionally execute callbacks based on their active state. Inactive branches are propagated or skipped. `Validated::combine()` is distinct because two invalid values can reach a caller-supplied combiner.

## Callable Composition

`Composition`, `Pipe`, eager collections, and `LazySeq` apply ordinary PHP callables in an explicit order. Collections transform their own values; function utilities transform arbitrary values.

## Boundaries

Exact does not automatically flatten arbitrary nested abstractions. A callback must return the expected type for `flatMap()`, and a caller must deliberately convert one abstraction to another, such as folding `Validated` into `Result` or returning `Result` from a matcher case.

This explicit boundary prevents an implicit global error model, but it means domain workflows must choose where conversion occurs.

## Architectural Relationship

The modules communicate through values and callables rather than shared mutable services. A `Newtype` can be carried by `Result`, a `Result` can be stored in `Seq`, and a `Variant` handler can return a `Result` without coupling the underlying modules.

## Related Documents

- [Architecture](architecture.md)
- [Error Semantics](error-semantics.md)
- [Function Composition Design](function/composition-design.md)