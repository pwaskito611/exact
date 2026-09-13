# Design: Newtypes

## Contract

`Newtype` is an abstract readonly class with one protected value and a final `value()` accessor. Its final `equals()` method returns true only when the concrete classes are identical and the wrapped values are strictly equal.

## Invariants

- A newtype has one wrapped value.
- The wrapped property cannot be reassigned through the readonly object contract.
- Equality is same concrete class plus strict value equality.

The class does not validate the wrapped value or prevent the wrapped object itself from being mutable.

## Newtype versus ValueObject

| Concern | Newtype | ValueObject |
| --- | --- | --- |
| Shape | One wrapped value | Subclass-defined fields and semantics |
| Equality | Final, same class and strict value | Implemented by the subclass |
| Validation | Not provided by the base class | Not provided by the base class |
| Primary use | Distinguish identifiers/codes | Model a multi-field domain value |

## Rationale and Trade-offs

Newtype equality is predictable and prevents accidental equality across domain wrappers. It is less expressive than a value object when equality requires normalization or several fields.

## Testing Implications

Tests should cover same-class equality, different-class inequality, strict wrapped values, and extraction through `value()`.

## Related Documents

- [Value Object Design](value-object-design.md)
- [Type Model](../type-model.md)
- [Domain Modeling](../../usage/patterns/domain-modeling.md)