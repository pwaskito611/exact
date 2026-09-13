# Error Semantics

Exact distinguishes expected computational states from invalid API operations.

## Expected States

| Abstraction | Meaning | Branch behavior |
| --- | --- | --- |
| `Option` | Value is present or absent | No error payload is carried. |
| `Result` | Operation succeeded or failed | `Err` carries a payload and short-circuits success transformations. |
| `Either` | Two domain alternatives | `Right` is the transformation-biased branch; `Left` remains meaningful. |
| `Validated` | Input is valid or invalid | Two invalid payloads can be combined by caller policy. |

These are values. They should be used when the condition is part of normal application flow.

## Programmer and Access Errors

Exceptions represent operations that violate an API precondition or request an unavailable branch:

- `LogicException` for wrong-branch access such as `Err::get()` or `Either::getRight()`.
- `InvalidArgumentException` for invalid construction or operation arguments.
- `TypeError` when a checked `flatMap()` callback returns the wrong abstraction.
- `OutOfBoundsException` for missing indexes.
- `EmptyCollectionException` for `Seq::head()` or `tail()` on empty input.
- `MatchException` for an unmatched `Matcher` without a default.

`InvalidStateException` exists as a runtime exception type but is not thrown by current production code.

## Propagation

Exact does not catch arbitrary callback exceptions or native PHP errors. They propagate normally. `Result` and related values handle only the branch values explicitly created by the caller.

## Design Boundary

The current implementation does not define a universal error hierarchy, error interface, serialization format, or recovery policy. Error payload shape remains application-specific.

## Related Documents

- [Result Design](data/result-design.md)
- [Validated Design](data/validated-design.md)
- [Pattern Matching](adt/pattern-matching.md)