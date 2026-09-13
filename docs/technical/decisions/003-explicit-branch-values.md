# ADR-003: Explicit Branch Values

## Status

Accepted as the current repository design.

## Context

Exact provides separate abstractions for absence, success/error, two-sided alternatives, and validation: `Option`, `Result`, `Either`, and `Validated`.

## Decision

Represent expected computational branches as explicit values with branch-aware transformations. Reserve exceptions for invalid access, invalid arguments, type-contract violations, and unmatched runtime dispatch.

## Alternatives

- Represent all expected failure with exceptions.
- Use one universal result type for absence, validation, and domain alternatives.

## Decision Rationale

The current types have different semantics: `Result` short-circuits errors, `Validated` can combine two invalid values, `Either` is right-biased but domain-named, and `Option` carries no error payload. Keeping them distinct preserves those contracts.

## Consequences

Applications must choose and sometimes explicitly convert between abstractions. Error payload shape and recovery policy remain application-specific.

## Revisit Conditions

Revisit this decision if the library introduces a common error protocol or changes branch-combination semantics across these types.