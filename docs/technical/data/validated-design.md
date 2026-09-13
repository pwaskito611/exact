# Design: Validated

## Purpose

`Validated` models `Valid` and `Invalid` values for validation workflows where independent invalid inputs may be combined.

## Contract

- `map()` transforms only `Valid`.
- `mapInvalid()` transforms only `Invalid`.
- `fold()` handles invalid first and valid second.
- Two valid values call `combineValid`.
- Two invalid values call `combineInvalid` with both payloads.
- A mixed pair returns the existing invalid payload without calling a combiner.

The invalid combiner is supplied by the caller. The current implementation does not impose a list, collection, or multi-error representation.

## Rationale and Trade-offs

Separating `Validated` from short-circuiting `Result` expresses a different workflow: report independent validation failures together. The callback-based combination is flexible, but it means error accumulation policy is not standardized by the library.

## Error and Access Semantics

`getValid()` on `Invalid` and `getInvalid()` on `Valid` throw `LogicException`. `get()` returns the payload regardless of branch and therefore does not itself narrow the state.

## Testing Implications

Tests should cover valid/invalid mapping, both-invalid combination, mixed propagation, and branch-specific access. They should not assume that `Validated` automatically accumulates into a predefined error collection.

## Related Documents

- [Result](result-design.md)
- [Error Semantics](../error-semantics.md)
- [Composition](../composition.md)