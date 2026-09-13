# Design: Result

## Purpose

`Result` represents a successful `Ok` value or an `Err` value carrying an error payload.

## Semantic Model

```text
Result<T, E>
├── Ok<T>
└── Err<E>
```

The type parameters are conceptual notation only; PHP does not enforce them in this project.

## Invariants and State Behavior

- `Ok::isOk()` is true and `Err::isErr()` is true.
- `map()` transforms only `Ok`.
- `mapErr()` transforms only `Err`.
- `flatMap()` continues only from `Ok` and requires the callback to return `Result`.
- `Err` short-circuits `map()` and `flatMap()` and remains available through `error()`.
- `fold()` handles error first and success second.
- `Ok::error()` and `Err::get()` throw `LogicException` because the requested branch is absent.

## Rationale

`Result` makes expected operation failure a value that can be composed. It avoids requiring exceptions for every expected failure while retaining an error payload. Exceptions remain available for invalid API access or programmer errors; see [Error Semantics](../error-semantics.md).

## Trade-offs

Every operation must preserve the branch contract, and callers eventually need to fold or otherwise extract the result. Error payload types are not statically enforced by this project.

## Testing Implications

Tests should verify branch short-circuiting, `mapErr()` isolation, `flatMap()` return validation, branch access errors, and final `fold()` behavior. Integration tests use `Result` as a boundary after validation and pattern matching.

## Related Documents

- [Option](option-design.md)
- [Validated](validated-design.md)
- [Error Semantics](../error-semantics.md)