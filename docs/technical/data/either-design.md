# Design: Either

## Purpose

`Either` models two meaningful alternatives as `Left` and `Right`. The current implementation stores a branch flag and payload in one final class.

## Contract

`map()` and `flatMap()` are right-biased: they transform or continue only the `Right` branch. `mapLeft()` transforms only `Left`. `fold()` handles left first and right second.

`getLeft()` and `getRight()` are branch-specific accessors and throw `LogicException` when called on the opposite branch. `swap()` creates the opposite branch with the same payload.

## Rationale and Trade-offs

Right bias makes `Either` convenient for a primary successful path while preserving a domain-specific left alternative. Unlike `Result`, the names do not intrinsically mean error and success; the application gives them meaning.

The branch is runtime state. There are no separate public `Left` and `Right` classes and no static exhaustiveness check.

## Testing Implications

Tests should verify right-biased mapping, left mapping, short-circuiting, branch access, folding, and swapping. Integration use is appropriate when a domain genuinely needs two named alternatives rather than generic success/error.

## Related Documents

- [Result](result-design.md)
- [ADT Design](../adt/adt-design.md)
- [Error Semantics](../error-semantics.md)