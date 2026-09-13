# Immutability

## Current Strategy

Exact favors shallow, value-returning immutability:

- `Option`, `Result`, `Variant`, `Seq`, `NonEmptySeq`, `Map`, `Set`, `Newtype`, and `ValueObject` are readonly classes.
- `Either`, `Tuple`, `Pair`, `Pipe`, and `LazySeq` use readonly properties even though their classes are not all declared readonly.
- Transformations generally construct and return a new value.
- No-op branches may return the same instance, such as `None::map()`, `Err::map()`, or adding an existing value to `Set`.

`Matcher` is mutable during construction: `case()` and `default()` update its handler registry. This is a deliberate builder boundary rather than a data-value transformation.

## PHP Limitation

Readonly prevents property reassignment; it does not recursively freeze objects stored in a property. Arrays cannot be reassigned through a readonly property, but an object held inside a value may still be mutable. Exact therefore should not be described as deeply or transitively immutable.

## Consequences

Returning new collection and data values supports safe reuse of earlier pipeline stages and makes composition easier to reason about. The cost is object/array allocation and the need to explicitly carry the returned value.

## Evidence

The unit and feature tests assert original collection values remain unchanged after transformations. They do not establish deep immutability for arbitrary payload objects.

## Related Documents

- [Design Principles](design-principles.md)
- [Collection Design](collection/collection-design.md)
- [Performance](performance.md)