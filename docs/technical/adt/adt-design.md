# Design: Algebraic Data Types

## Representation

`Variant` is a final readonly value containing a string `name` and a mixed `value` payload. `Variant::of()` constructs it; `name()`, `value()`, and `is()` expose the public contract.

`PatternMatch::on()` creates a `Matcher` around a variant. `Matcher` stores case handlers by string name and may store one default handler. The matcher is mutable during registration and returns itself from `case()` and `default()` for fluent setup.

## Contract

- A matching case receives the variant payload.
- A default receives the payload and the variant name.
- A matching case takes precedence over the default.
- Registering the same case name replaces the earlier handler.
- No matching case and no default throws `MatchException`.

## Exhaustiveness

Matching is runtime string lookup. There is no finite compiler-known set of variant names, no static exhaustiveness check, and `MatchException::notExhaustive()` is not used by `Matcher::run()` in the current implementation.

## Relationship to PHP Enums

PHP enums provide a closed set of declared cases, while `Variant` permits arbitrary string names and arbitrary payloads at runtime. `Variant` therefore supports open, data-carrying application states, but gives up enum-level compiler assistance.

## Rationale and Trade-offs

The abstraction keeps state naming and payload handling explicit without reflection or generated dispatch. The trade-off is that spelling, payload shape, and coverage are application responsibilities.

## Related Documents

- [Pattern Matching](pattern-matching.md)
- [Type Model](../type-model.md)
- [Error Semantics](../error-semantics.md)