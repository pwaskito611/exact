# Composition Across Modules

Exact's types and collections can be combined through explicit method calls and ordinary PHP callbacks.

## Branch Composition

`Option`, `Either`, and `Validated` conditionally execute callbacks based on their active state. Inactive branches are propagated or skipped. `Validated::combine()` is distinct because two invalid values can reach a caller-supplied combiner.

## Boundaries

Exact does not automatically flatten arbitrary nested abstractions. A callback must return the expected type for `flatMap()`, and a caller deliberately chooses how to convert a branch to another value, such as folding `Validated` into `Either` or returning an `Option` from a matcher case.

This explicit boundary prevents an implicit global error model, but it means domain workflows must choose where conversion occurs.

## Architectural Relationship

The modules communicate through values and callbacks rather than shared mutable services. An `Either` can carry a `Vec`, and a `Variant` handler can return an `Option` without coupling the underlying modules.

## Related Documents

- [Architecture](architecture.md)
- [Error Semantics](error-semantics.md)
- [Collection Design](collection/collection-design.md)