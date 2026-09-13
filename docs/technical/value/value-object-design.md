# Design: Value Objects

## Contract

`ValueObject` is an abstract readonly class with one required method: `equals(self $other): bool`. It does not store fields, validate construction, expose serialization, or define equality automatically.

Each subclass owns its domain equality rule. The base type therefore provides a semantic boundary, not a universal value comparison algorithm.

## Invariants

The only enforced invariant is that a concrete value object implements `equals()` with another `ValueObject`. Constructor validation and field immutability are responsibilities of the subclass, subject to PHP readonly rules.

## Rationale

Domain values often need equality over several fields or a domain-specific normalization rule. Requiring the subclass to define equality keeps that decision local and avoids guessing which fields are meaningful.

## Trade-offs

The abstraction is small and explicit, but two value objects can choose inconsistent equality semantics. There is no automatic hash implementation, serialization contract, or generic field comparison.

## Testing Implications

Tests should verify each concrete value object's equality rule and unequal-domain cases. They should not assume all subclasses compare fields in the same way.

## Related Documents

- [Newtype Design](newtype-design.md)
- [Type Model](../type-model.md)
- [Immutability](../immutability.md)