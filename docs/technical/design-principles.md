# Design Principles

These principles describe patterns visible in the current implementation. They are not a separate policy file, so future changes should be checked against the contracts and tests rather than treated as unconditional guarantees.

## Explicitness

`Option`, `Result`, `Either`, `Validated`, and `Variant` make important states visible in the value being passed around. A caller can select a branch with `fold()`, inspect a branch, or continue a branch-specific transformation.

The trade-off is additional wrapping and explicit unwrapping compared with raw nullable values or exceptions.

## Immutable-Style Values

Most data and eager collection classes are `readonly`, and transformations return new values. This makes a pipeline easier to reason about because a previous value is not changed by `map()`, `filter()`, `put()`, or `append()`.

This is shallow rather than a claim of deep immutability: objects stored inside values can still have their own mutable behavior. `Matcher` is intentionally mutable while cases are registered.

## Composition

The abstractions expose common transformation operations and callable-based functions. `Option`, `Either`, `Result`, and `Validated` selectively execute callbacks according to branch state; `Composition`, `Pipe`, and collections compose ordinary callables.

Composition improves reuse, but callback type contracts remain PHP callable contracts. Exact does not provide a separate compile-time generic type system.

## Type-Oriented Modeling

Abstract classes, final classes, `readonly`, union types, `mixed`, `never`, and PHPDoc array/callable shapes are used where they fit the API. The implementation uses PHP's runtime type checks and class identity rather than reflection or a framework container.

## Framework Independence

`composer.json` declares only PHP `>=8.2` as a runtime requirement. The package does not require a framework, service container, or application lifecycle, which keeps the abstractions usable in different PHP applications. The trade-off is that integration conventions are the application's responsibility.

## Minimal Magic

Operations are explicit method calls, callbacks, and named variants. There is no runtime reflection layer, generated registry, or hidden global state in the current source. This keeps behavior close to the value being transformed, at the cost of more explicit wiring.

## Related Documents

- [Immutability](immutability.md)
- [Type Model](type-model.md)
- [Architecture Decisions](decisions/README.md)