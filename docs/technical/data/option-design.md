# Design: Option

## Purpose

`Option` represents either a present value or absence without requiring callers to interpret `null` at every boundary.

## Semantic Model

```text
Option<T>
├── Some<T>
└── None
```

`Option::some()` creates `Some`, `Option::none()` creates `None`, and `Option::from()` maps exactly `null` to `None`. Other values, including an empty array, become `Some`.

## Invariants and State Behavior

- `Some` reports `isSome() === true` and owns its value.
- `None` reports `isNone() === true` and has no contained application value.
- `map()`, `flatMap()`, and `filter()` run callbacks only for `Some`.
- `None` returns itself for these transformations; accepted `Some::filter()` also returns itself.
- `getOrElse()` returns the contained value for `Some` and the supplied fallback for `None`.

## Contract and Rationale

Branch-specific operations make absence explicit and keep optional workflows from spreading nullable checks. `Option` carries no error reason; that is the boundary where [Result](result-design.md) becomes more appropriate.

`flatMap()` is intended to receive another `Option`. The abstract signature expresses this return type, but `Some` delegates directly to the callback, so runtime misuse is not normalized by a custom check.

## Testing Implications

Tests should cover null versus non-null construction, inactive callbacks, fallback behavior, and preservation of `None` through transformations. Feature tests should verify a complete optional-input workflow rather than repeat each method-level test.

## Related Documents

- [Result](result-design.md)
- [Error Semantics](../error-semantics.md)
- [Immutability](../immutability.md)