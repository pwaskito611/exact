# Design: Pattern Matching

The semantic flow is:

```text
Variant value
    ↓ name lookup
registered case or default
    ↓ payload handler
result value
```

## Supported Matching

The current matcher supports exact string-name cases and one fallback handler. It does not support structural patterns, guards, nested patterns, enum cases, or compile-time exhaustiveness.

## Failure Behavior

`Matcher::run()` throws `MatchException::noMatch()` when no registered case and no default apply. A default is an explicit runtime fallback; it does not prove that all possible states are known.

## Design Boundary

Pattern matching is dispatch, not validation. If a handler must report a domain failure, it can return `Result`; if an unknown state is a programmer error, omitting the default exposes it as an exception. The choice belongs to the caller.

## Testing Implications

Tests should cover matching payloads, default payload/name arguments, precedence, duplicate case replacement, and unmatched exceptions. Tests should not claim static exhaustiveness.

## Related Documents

- [ADT Design](adt-design.md)
- [Result Design](../data/result-design.md)
- [Error Semantics](../error-semantics.md)